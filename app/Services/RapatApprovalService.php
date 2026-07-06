<?php

namespace App\Services;

use App\Rapat;
use App\RapatApproval;
use App\RapatApprovalHistory;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RapatApprovalService
{
    protected $whatsAppService;
    protected $documentService;
    protected $auditService;
    protected $signaturePadService;

    public function __construct(WhatsAppNotificationService $whatsAppService, RapatDocumentService $documentService, ActivityAuditService $auditService, SignaturePadService $signaturePadService)
    {
        $this->whatsAppService = $whatsAppService;
        $this->documentService = $documentService;
        $this->auditService = $auditService;
        $this->signaturePadService = $signaturePadService;
    }

    public function syncWorkflow(Rapat $rapat, $fallbackStatus = 'draft', $isResubmission = false)
    {
        $approverSteps = $this->buildApproverSteps($rapat);

        if ($approverSteps->isEmpty()) {
            $rapat->approvals()->delete();
            $rapat->status = in_array($fallbackStatus, ['draft', 'terjadwal', 'dibatalkan', 'selesai']) ? $fallbackStatus : 'draft';
            $rapat->save();
            $this->documentService->syncSuratKeluar($rapat->fresh(), $this->documentService->shouldUseSignedDocument($rapat));

            return;
        }

        $existingApprovals = $rapat->approvals()->get()->keyBy('step_order');

        foreach ($approverSteps as $stepData) {
            $approval = $existingApprovals->get($stepData['step_order']);
            $hasChangedApprover = !$approval || (int) $approval->approver_id !== (int) $stepData['approver_id'];

            if ($approval && $hasChangedApprover) {
                $approval->update([
                    'approver_id' => $stepData['approver_id'],
                    'approver_name_snapshot' => $stepData['approver_name_snapshot'],
                    'approver_jabatan_snapshot' => $stepData['approver_jabatan_snapshot'],
                    'status' => 'waiting',
                    'catatan' => null,
                    'acted_at' => null,
                    'signature_path' => null,
                    'signature_mime' => null,
                    'signature_size' => null,
                    'notified_at' => null,
                ]);
                continue;
            }

            if (!$approval) {
                RapatApproval::create([
                    'rapat_id' => $rapat->id,
                    'step_order' => $stepData['step_order'],
                    'approver_id' => $stepData['approver_id'],
                    'approver_name_snapshot' => $stepData['approver_name_snapshot'],
                    'approver_jabatan_snapshot' => $stepData['approver_jabatan_snapshot'],
                    'status' => 'waiting',
                    'notified_at' => null,
                ]);
                continue;
            }

            if ($isResubmission && $approval->status === 'rejected') {
                $this->logHistory($approval, 'resubmitted', 'Dokumen diperbarui dan diajukan ulang.');
                $approval->update([
                    'status' => 'waiting',
                    'catatan' => null,
                    'acted_at' => null,
                    'signature_path' => null,
                    'signature_mime' => null,
                    'signature_size' => null,
                    'notified_at' => null,
                ]);
            }
        }

        $rapat->approvals()
            ->whereNotIn('step_order', $approverSteps->pluck('step_order')->all())
            ->delete();

        $this->normalizeStatuses($rapat);
        $this->refreshRapatStatus($rapat, $fallbackStatus);
    }

    public function approve(RapatApproval $approval, User $actor, $catatan = null, $signatureData = null)
    {
        $previousStatus = $approval->status;

        DB::transaction(function () use ($approval, $actor, $catatan, $signatureData) {
            $approval->refresh();
            $this->guardDecision($approval, $actor);
            $signature = $this->signaturePadService->resolveForUser($actor, 'rapat/approval-signatures', $signatureData);

            $approval->update([
                'status' => 'approved',
                'catatan' => $catatan,
                'acted_at' => Carbon::now('Asia/Jayapura'),
                'signature_path' => $signature['path'],
                'signature_mime' => $signature['mime'],
                'signature_size' => $signature['size'],
            ]);

            $this->logHistory($approval, 'approved', $catatan);
            $this->advanceWorkflow($approval->rapat, $approval->step_order);
            $approval->rapat->refresh();

            if ($approval->rapat->status === 'disetujui') {
                $this->documentService->syncSuratKeluar($approval->rapat->fresh(), true);
                return;
            }

            $this->documentService->syncSuratKeluar($approval->rapat->fresh(), false);
        });

        $approval->rapat->refresh();
        $this->auditService->log('rapat', 'rapat_approval_approved', $approval->fresh('rapat'), [
            'subject_type' => 'rapat',
            'subject_id' => optional($approval->rapat)->id,
            'subject_title' => optional($approval->rapat)->judul,
            'target_user_id' => $approval->approver_id,
            'target_name' => optional($approval->approver)->name ?: $approval->approver_name_snapshot,
            'old_values_json' => ['status' => $previousStatus],
            'new_values_json' => ['status' => 'approved'],
            'note' => $catatan,
        ], $actor);

        if ($approval->rapat->status === 'disetujui') {
            $this->whatsAppService->notifyRapatParticipants($approval->rapat);
            return;
        }

        $this->notifyCurrentPendingApprover($approval->rapat);
    }

    public function reject(RapatApproval $approval, User $actor, $catatan)
    {
        $previousStatus = $approval->status;

        DB::transaction(function () use ($approval, $actor, $catatan) {
            $approval->refresh();
            $this->guardDecision($approval, $actor);

            $approval->update([
                'status' => 'rejected',
                'catatan' => $catatan,
                'acted_at' => Carbon::now('Asia/Jayapura'),
                'signature_path' => null,
                'signature_mime' => null,
                'signature_size' => null,
            ]);

            $approval->rapat->approvals()
                ->where('step_order', '>', $approval->step_order)
                ->whereIn('status', ['pending', 'waiting'])
                ->update([
                    'status' => 'waiting',
                    'catatan' => null,
                    'acted_at' => null,
                    'signature_path' => null,
                    'signature_mime' => null,
                    'signature_size' => null,
                ]);

            $this->logHistory($approval, 'rejected', $catatan);
            $approval->rapat->update(['status' => 'ditolak']);
            $this->documentService->syncSuratKeluar($approval->rapat->fresh(), false);
        });

        $this->auditService->log('rapat', 'rapat_approval_rejected', $approval->fresh('rapat'), [
            'subject_type' => 'rapat',
            'subject_id' => optional($approval->rapat)->id,
            'subject_title' => optional($approval->rapat)->judul,
            'target_user_id' => $approval->approver_id,
            'target_name' => optional($approval->approver)->name ?: $approval->approver_name_snapshot,
            'old_values_json' => ['status' => $previousStatus],
            'new_values_json' => ['status' => 'rejected'],
            'note' => $catatan,
        ], $actor);

        $this->whatsAppService->notifyRapatRejected($approval->rapat->fresh('creator'), $catatan);
    }

    public function refreshRapatStatus(Rapat $rapat, $fallbackStatus = 'draft')
    {
        $approvals = $rapat->approvals()->orderBy('step_order')->get();

        if ($approvals->isEmpty()) {
            $rapat->status = in_array($fallbackStatus, ['draft', 'terjadwal', 'dibatalkan', 'selesai']) ? $fallbackStatus : 'draft';
            $rapat->save();

            return;
        }

        if ($approvals->contains('status', 'rejected')) {
            $rapat->status = 'ditolak';
        } elseif ($approvals->every(function ($approval) {
            return $approval->status === 'approved';
        })) {
            $rapat->status = 'disetujui';
        } else {
            $rapat->status = 'pending_approval';
        }

        $rapat->save();
    }

    protected function buildApproverSteps(Rapat $rapat)
    {
        $approverSequence = [];

        if ($rapat->approver_2_id) {
            $approverSequence[] = $rapat->approver_2_id;
        }

        if ($rapat->approver_1_id) {
            $approverSequence[] = $rapat->approver_1_id;
        }

        return collect($approverSequence)
            ->filter()
            ->values()
            ->map(function ($approverId, $index) {
                $approver = User::with('jabatan')->find($approverId);

                return [
                    'step_order' => $index + 1,
                    'approver_id' => $approver->id,
                    'approver_name_snapshot' => $approver->name,
                    'approver_jabatan_snapshot' => optional($approver->jabatan)->nama ?: $approver->jabatan_keterangan,
                ];
            });
    }

    protected function normalizeStatuses(Rapat $rapat)
    {
        $approvals = $rapat->approvals()->orderBy('step_order')->get();
        $previousApproved = true;

        foreach ($approvals as $approval) {
            if ($approval->status === 'approved' && $previousApproved) {
                continue;
            }

            if ($approval->status === 'rejected') {
                $previousApproved = false;
                continue;
            }

            $desiredStatus = $previousApproved ? 'pending' : 'waiting';

            if ($approval->status !== $desiredStatus) {
                $approval->update([
                    'status' => $desiredStatus,
                    'catatan' => null,
                    'acted_at' => null,
                    'signature_path' => null,
                    'signature_mime' => null,
                    'signature_size' => null,
                    'notified_at' => $desiredStatus === 'pending' ? null : $approval->notified_at,
                ]);
            }

            if ($desiredStatus === 'pending') {
                $previousApproved = false;
            }
        }
    }

    protected function advanceWorkflow(Rapat $rapat, $completedStep)
    {
        $nextApproval = $rapat->approvals()
            ->where('step_order', '>', $completedStep)
            ->where('status', 'waiting')
            ->orderBy('step_order')
            ->first();

        if ($nextApproval) {
            $nextApproval->update([
                'status' => 'pending',
                'catatan' => null,
                'acted_at' => null,
                'signature_path' => null,
                'signature_mime' => null,
                'signature_size' => null,
                'notified_at' => null,
            ]);
        }

        $this->refreshRapatStatus($rapat, 'pending_approval');
    }

    public function notifyCurrentPendingApprover(Rapat $rapat)
    {
        $pendingApproval = $rapat->approvals()
            ->with(['approver', 'rapat.creator', 'rapat.kategoriSuratKode'])
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();

        if (!$pendingApproval) {
            return false;
        }

        return $this->whatsAppService->notifyRapatApprovalPending($pendingApproval);
    }

    protected function logHistory(RapatApproval $approval, $action, $catatan = null)
    {
        RapatApprovalHistory::create([
            'rapat_id' => $approval->rapat_id,
            'rapat_approval_id' => $approval->id,
            'approver_id' => $approval->approver_id,
            'step_order' => $approval->step_order,
            'approver_name_snapshot' => $approval->approver_name_snapshot,
            'approver_jabatan_snapshot' => $approval->approver_jabatan_snapshot,
            'action' => $action,
            'catatan' => $catatan,
            'acted_at' => Carbon::now('Asia/Jayapura'),
        ]);
    }

    protected function guardDecision(RapatApproval $approval, User $actor)
    {
        if (!$actor->canActAsAssignedUser($approval->approver_id) && !$actor->isMeetingAdmin()) {
            abort(403, 'Anda tidak berhak memproses approval ini.');
        }

        if ($approval->status !== 'pending') {
            abort(422, 'Approval ini tidak berada pada status pending.');
        }
    }
}
