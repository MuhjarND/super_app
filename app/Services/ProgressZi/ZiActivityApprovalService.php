<?php

namespace App\Services\ProgressZi;

use App\Notifications\ProgressZiWorkflowNotification;
use App\User;
use App\ZiActivity;
use App\ZiActivityApproval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZiActivityApprovalService
{
    protected $auditService;
    protected $whatsAppService;

    public function __construct(\App\Services\ActivityAuditService $auditService, \App\Services\WhatsAppNotificationService $whatsAppService)
    {
        $this->auditService = $auditService;
        $this->whatsAppService = $whatsAppService;
    }

    public function submit(ZiActivity $activity, User $requester, $requestNotes = null)
    {
        if (!$this->isReadyForReview($activity)) {
            abort(422, 'Kegiatan belum siap direview pimpinan. Pastikan kegiatan sudah dijadwalkan dan eviden sudah tersedia.');
        }

        $existingPending = $activity->approvals()->where('status', 'pending')->latest('id')->first();
        if ($existingPending) {
            return $existingPending;
        }

        $approver = $this->resolveApprover();
        abort_unless($approver, 422, 'Belum ada user approval yang bisa menerima review pimpinan Progress ZI.');

        $approval = DB::transaction(function () use ($activity, $requester, $requestNotes, $approver) {
            $approval = $activity->approvals()->create([
                'approver_id' => $approver->id,
                'requested_by' => $requester->id,
                'status' => 'pending',
                'request_notes' => $requestNotes,
                'requested_at' => now(),
            ]);

            $activity->update([
                'status' => 'sedang_berjalan',
                'updated_by' => $requester->id,
            ]);

            return $approval;
        });

        $approval->loadMissing(['activity.area', 'activity.pic', 'activity.area.pics', 'requester', 'approver']);
        $this->notifyPendingReview($approval);

        return $approval;
    }

    public function approve(ZiActivityApproval $approval, User $approver, $reviewNotes = null)
    {
        abort_unless($approver->canActAsAssignedUser($approval->approver_id) || $approver->isSuperAdmin(), 403);
        abort_unless($approval->status === 'pending', 422, 'Approval ini sudah diproses.');
        $previousStatus = $approval->status;

        DB::transaction(function () use ($approval, $approver, $reviewNotes) {
            $approval->update([
                'status' => 'approved',
                'review_notes' => $reviewNotes,
                'acted_at' => now(),
            ]);

            $approval->activity->update([
                'status' => 'selesai',
                'updated_by' => $approver->id,
            ]);
        });

        $approval->loadMissing('activity.area', 'requester', 'approver');
        $this->auditService->log('progress_zi', 'zi_review_approved', $approval, [
            'subject_type' => 'zi_activity',
            'subject_id' => optional($approval->activity)->id,
            'subject_title' => optional($approval->activity)->name,
            'target_user_id' => optional($approval->requester)->id,
            'target_name' => optional($approval->requester)->name,
            'old_values_json' => ['status' => $previousStatus],
            'new_values_json' => ['status' => 'approved'],
            'note' => $reviewNotes,
        ], $approver);

        $approval = $approval->fresh(['activity.area.pics', 'activity.pic', 'approver', 'requester']);
        $this->notifyReviewed($approval, true, $reviewNotes);

        return $approval;
    }

    public function reject(ZiActivityApproval $approval, User $approver, $reviewNotes = null)
    {
        abort_unless($approver->canActAsAssignedUser($approval->approver_id) || $approver->isSuperAdmin(), 403);
        abort_unless($approval->status === 'pending', 422, 'Approval ini sudah diproses.');
        $previousStatus = $approval->status;

        DB::transaction(function () use ($approval, $approver, $reviewNotes) {
            $approval->update([
                'status' => 'rejected',
                'review_notes' => $reviewNotes,
                'acted_at' => now(),
            ]);

            $approval->activity->update([
                'status' => 'perlu_perbaikan',
                'updated_by' => $approver->id,
            ]);
        });

        $approval->loadMissing('activity.area', 'requester', 'approver');
        $this->auditService->log('progress_zi', 'zi_review_rejected', $approval, [
            'subject_type' => 'zi_activity',
            'subject_id' => optional($approval->activity)->id,
            'subject_title' => optional($approval->activity)->name,
            'target_user_id' => optional($approval->requester)->id,
            'target_name' => optional($approval->requester)->name,
            'old_values_json' => ['status' => $previousStatus],
            'new_values_json' => ['status' => 'rejected'],
            'note' => $reviewNotes,
        ], $approver);

        $approval = $approval->fresh(['activity.area.pics', 'activity.pic', 'approver', 'requester']);
        $this->notifyReviewed($approval, false, $reviewNotes);

        return $approval;
    }

    public function isReadyForReview(ZiActivity $activity)
    {
        $activity->loadMissing('realizations.evidences', 'latestApproval');

        if ($activity->realizations->isEmpty()) {
            return false;
        }

        return $activity->realizations->sum(function ($realization) {
            return $realization->evidences->count();
        }) > 0;
    }

    protected function resolveApprover()
    {
        return User::active()
            ->withRoleOrDelegatedJabatan(['approval', 'super_admin'])
            ->ordered()
            ->first();
    }

    protected function notifyPendingReview(ZiActivityApproval $approval)
    {
        if ($approval->approver) {
            try {
                $approval->approver->notify(new ProgressZiWorkflowNotification(
                    $approval->activity,
                    'Review pimpinan Progress ZI menunggu tindakan Anda',
                    'Terdapat kegiatan Progress ZI yang memerlukan review pimpinan.',
                    route('progress-zi.approvals.show', $approval),
                    'approver',
                    'pending'
                ));
            } catch (\Throwable $e) {
                Log::warning('Progress ZI pending notification skipped', [
                    'approval_id' => $approval->id,
                    'user_id' => $approval->approver_id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->whatsAppService->notifyProgressZiApprovalPending($approval);
        }
    }

    protected function notifyReviewed(ZiActivityApproval $approval, $approved, $reviewNotes = null)
    {
        $activity = $approval->activity;
        $targets = collect([
            $approval->requester,
            $activity->pic,
        ])->filter();

        if ($activity->area && $activity->area->relationLoaded('pics')) {
            $targets = $targets->merge($activity->area->pics);
        }

        $targets = $targets->unique('id')->values();
        $title = $approved ? 'Review pimpinan Progress ZI disetujui' : 'Review pimpinan Progress ZI perlu perbaikan';
        $message = $approved
            ? 'Kegiatan Progress ZI telah disetujui dan dinyatakan selesai.'
            : 'Kegiatan Progress ZI dikembalikan untuk perbaikan. Mohon meninjau catatan review pimpinan.';

        foreach ($targets as $target) {
            try {
                $target->notify(new ProgressZiWorkflowNotification(
                    $activity,
                    $title,
                    $message,
                    route('progress-zi.activities.show', $activity),
                    'pic',
                    $approval->status
                ));
            } catch (\Throwable $e) {
                Log::warning('Progress ZI reviewed notification skipped', [
                    'approval_id' => $approval->id,
                    'user_id' => $target->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->whatsAppService->notifyProgressZiStatus($activity, $target, $title, $message, $reviewNotes);
        }
    }
}
