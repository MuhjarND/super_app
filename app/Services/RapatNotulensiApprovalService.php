<?php

namespace App\Services;

use App\RapatNotulensi;
use App\RapatNotulensiApproval;
use App\RapatNotulensiApprovalHistory;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RapatNotulensiApprovalService
{
    protected $auditService;
    protected $signaturePadService;

    public function __construct(ActivityAuditService $auditService, SignaturePadService $signaturePadService)
    {
        $this->auditService = $auditService;
        $this->signaturePadService = $signaturePadService;
    }

    public function syncWorkflow(RapatNotulensi $notulensi, $isResubmission = false)
    {
        $notulensi->loadMissing('rapat.approver1.jabatan', 'rapat.approver2.jabatan', 'approval');

        if ($notulensi->tidak_membuat_notulen) {
            $notulensi->approval()->delete();
            $notulensi->update(['status' => 'tanpa_notulen', 'approval_ready' => false]);
            return;
        }

        $approver = $this->resolveApprover($notulensi);
        if (!$approver) {
            $notulensi->approval()->delete();
            $notulensi->update(['status' => 'selesai', 'approval_ready' => false]);
            return;
        }

        $approval = $notulensi->approval;

        if (!$approval) {
            RapatNotulensiApproval::create([
                'rapat_notulensi_id' => $notulensi->id,
                'approver_id' => $approver->id,
                'approver_name_snapshot' => $approver->name,
                'approver_jabatan_snapshot' => optional($approver->jabatan)->nama ?: $approver->jabatan_keterangan,
                'status' => 'pending',
            ]);
        } else {
            $payload = [
                'approver_id' => $approver->id,
                'approver_name_snapshot' => $approver->name,
                'approver_jabatan_snapshot' => optional($approver->jabatan)->nama ?: $approver->jabatan_keterangan,
            ];

            if ($isResubmission || (int) $approval->approver_id !== (int) $approver->id || in_array($approval->status, ['approved', 'rejected'], true)) {
                if ($isResubmission && $approval->status === 'rejected') {
                    $this->logHistory($approval, 'resubmitted', 'Notulen diperbarui dan diajukan ulang.');
                }

                $payload['status'] = 'pending';
                $payload['catatan'] = null;
                $payload['acted_at'] = null;
                $payload['signature_path'] = null;
                $payload['signature_mime'] = null;
                $payload['signature_size'] = null;
                $payload['notified_at'] = null;
            }

            $approval->update($payload);
        }

        $notulensi->update([
            'status' => 'pending_approval',
            'approval_ready' => true,
        ]);
    }

    public function approve(RapatNotulensiApproval $approval, User $actor, $catatan = null, $signatureData = null)
    {
        $previousStatus = $approval->status;

        DB::transaction(function () use ($approval, $actor, $catatan, $signatureData) {
            $approval->refresh();
            $this->guardDecision($approval, $actor);
            $signature = $this->signaturePadService->storeDataUri($signatureData, 'rapat/notulensi-approval-signatures');

            $approval->update([
                'status' => 'approved',
                'catatan' => $catatan,
                'acted_at' => Carbon::now('Asia/Jayapura'),
                'signature_path' => $signature['path'],
                'signature_mime' => $signature['mime'],
                'signature_size' => $signature['size'],
            ]);

            $this->logHistory($approval, 'approved', $catatan);
            $approval->notulensi()->update([
                'status' => 'selesai',
                'approval_ready' => true,
            ]);
        });

        $approval->loadMissing('notulensi.rapat', 'approver');
        $this->auditService->log('rapat', 'notulensi_approval_approved', $approval, [
            'subject_type' => 'rapat_notulensi',
            'subject_id' => optional($approval->notulensi)->id,
            'subject_title' => optional(optional($approval->notulensi)->rapat)->judul ?: optional($approval->notulensi)->judul,
            'target_user_id' => $approval->approver_id,
            'target_name' => optional($approval->approver)->name ?: $approval->approver_name_snapshot,
            'old_values_json' => ['status' => $previousStatus],
            'new_values_json' => ['status' => 'approved'],
            'note' => $catatan,
        ], $actor);
    }

    public function reject(RapatNotulensiApproval $approval, User $actor, $catatan)
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

            $this->logHistory($approval, 'rejected', $catatan);
            $approval->notulensi()->update([
                'status' => 'ditolak',
                'approval_ready' => false,
            ]);
        });

        $approval->loadMissing('notulensi.rapat', 'approver');
        $this->auditService->log('rapat', 'notulensi_approval_rejected', $approval, [
            'subject_type' => 'rapat_notulensi',
            'subject_id' => optional($approval->notulensi)->id,
            'subject_title' => optional(optional($approval->notulensi)->rapat)->judul ?: optional($approval->notulensi)->judul,
            'target_user_id' => $approval->approver_id,
            'target_name' => optional($approval->approver)->name ?: $approval->approver_name_snapshot,
            'old_values_json' => ['status' => $previousStatus],
            'new_values_json' => ['status' => 'rejected'],
            'note' => $catatan,
        ], $actor);
    }

    protected function resolveApprover(RapatNotulensi $notulensi)
    {
        $rapat = $notulensi->rapat;

        if ($rapat && $rapat->approver1) {
            return $rapat->approver1;
        }

        if ($rapat && $rapat->approver2) {
            return $rapat->approver2;
        }

        return null;
    }

    protected function logHistory(RapatNotulensiApproval $approval, $action, $catatan = null)
    {
        RapatNotulensiApprovalHistory::create([
            'rapat_notulensi_id' => $approval->rapat_notulensi_id,
            'rapat_notulensi_approval_id' => $approval->id,
            'approver_id' => $approval->approver_id,
            'approver_name_snapshot' => $approval->approver_name_snapshot,
            'approver_jabatan_snapshot' => $approval->approver_jabatan_snapshot,
            'action' => $action,
            'catatan' => $catatan,
            'acted_at' => Carbon::now('Asia/Jayapura'),
        ]);
    }

    protected function guardDecision(RapatNotulensiApproval $approval, User $actor)
    {
        if ((int) $approval->approver_id !== (int) $actor->id && !$actor->isMeetingAdmin()) {
            abort(403, 'Anda tidak berhak memproses approval notulen ini.');
        }

        if ($approval->status !== 'pending') {
            abort(422, 'Approval notulen ini tidak berada pada status pending.');
        }
    }
}
