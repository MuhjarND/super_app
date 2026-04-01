<?php

namespace App\Services\ProgressZi;

use App\User;
use App\ZiActivity;
use App\ZiActivityApproval;
use Illuminate\Support\Facades\DB;

class ZiActivityApprovalService
{
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

        return DB::transaction(function () use ($activity, $requester, $requestNotes, $approver) {
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
    }

    public function approve(ZiActivityApproval $approval, User $approver, $reviewNotes = null)
    {
        abort_unless((int) $approval->approver_id === (int) $approver->id || $approver->isSuperAdmin(), 403);
        abort_unless($approval->status === 'pending', 422, 'Approval ini sudah diproses.');

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

        return $approval->fresh(['activity', 'approver', 'requester']);
    }

    public function reject(ZiActivityApproval $approval, User $approver, $reviewNotes = null)
    {
        abort_unless((int) $approval->approver_id === (int) $approver->id || $approver->isSuperAdmin(), 403);
        abort_unless($approval->status === 'pending', 422, 'Approval ini sudah diproses.');

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

        return $approval->fresh(['activity', 'approver', 'requester']);
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
        return User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['approval', 'super_admin']);
        })
            ->orderBy('hirarki')
            ->orderBy('name')
            ->first();
    }
}
