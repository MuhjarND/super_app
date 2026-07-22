<?php

namespace App\Services;

use App\Notifications\SuratTugasNotification;
use App\SuratKeluar;
use App\SuratKeluarApproval;
use App\SuratKeluarApprovalHistory;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class SuratKeluarApprovalService
{
    protected $documentService;
    protected $auditService;
    protected $whatsAppService;

    public function __construct(SuratTemplateDocumentService $documentService, ActivityAuditService $auditService, WhatsAppNotificationService $whatsAppService)
    {
        $this->documentService = $documentService;
        $this->auditService = $auditService;
        $this->whatsAppService = $whatsAppService;
    }

    public function syncForTemplate(SuratKeluar $suratKeluar, array $payload, User $approver, User $requester)
    {
        $approval = DB::transaction(function () use ($suratKeluar, $payload, $approver, $requester) {
            $parafUserId = data_get($payload, 'field_values.paraf.id');
            $approval = SuratKeluarApproval::updateOrCreate(
                ['surat_keluar_id' => $suratKeluar->id],
                [
                    'approver_id' => $approver->id,
                    'paraf_user_id' => $parafUserId,
                    'paraf_status' => $parafUserId ? 'pending' : 'not_required',
                    'paraf_note' => null,
                    'paraf_at' => null,
                    'requested_by' => $requester->id,
                    'template_slug' => $payload['template_slug'] ?? null,
                    'template_name' => $payload['template_name'] ?? null,
                    'rendered_body' => $payload['rendered_body'] ?? null,
                    'field_values' => $payload['field_values'] ?? [],
                    'signer_name_snapshot' => data_get($payload, 'field_values.penanda_tangan.nama', $approver->name),
                    'signer_title_snapshot' => data_get($payload, 'field_values.penanda_tangan.jabatan_ttd', optional($approver->jabatan)->nama ?: $approver->jabatan_keterangan),
                    'status' => 'pending',
                    'note' => null,
                    'acted_at' => null,
                    'signature_path' => null,
                    'signature_mime' => null,
                    'signature_size' => null,
                ]
            );

            $suratKeluar->update(['status' => 'draft']);

            return $approval;
        });

        if ($approval->paraf_user_id) {
            $this->notifyParafPending($approval->fresh(['suratKeluar', 'parafUser']));
        } else {
            $this->notifySignerPending($approval->fresh(['suratKeluar', 'approver']));
        }

        return $approval;
    }

    public function approveParaf(SuratKeluarApproval $approval, User $actor, $note = null)
    {
        DB::transaction(function () use ($approval, $actor, $note) {
            $approval->refresh();
            $this->guardParafDecision($approval, $actor);

            $approval->update([
                'paraf_status' => 'approved',
                'paraf_note' => $note,
                'paraf_at' => now(),
            ]);

            SuratKeluarApprovalHistory::create([
                'surat_keluar_approval_id' => $approval->id,
                'surat_keluar_id' => $approval->surat_keluar_id,
                'approver_id' => $actor->id,
                'action' => 'paraf_approved',
                'note' => $note,
                'signer_name_snapshot' => $actor->name,
                'signer_title_snapshot' => optional($actor->jabatan)->nama ?: $actor->jabatan_keterangan,
                'acted_at' => now(),
            ]);
        });

        $approval = $approval->fresh(['suratKeluar', 'approver', 'parafUser']);
        $this->auditService->log('persuratan', 'surat_tugas_paraf_approved', $approval, [
            'subject_type' => 'surat_keluar',
            'subject_id' => $approval->surat_keluar_id,
            'subject_title' => optional($approval->suratKeluar)->perihal ?: 'Surat Tugas',
            'target_user_id' => $approval->approver_id,
            'target_name' => optional($approval->approver)->name,
            'note' => $note,
        ], $actor);

        $this->notifySignerPending($approval);

        return $approval;
    }

    public function rejectParaf(SuratKeluarApproval $approval, User $actor, $note)
    {
        DB::transaction(function () use ($approval, $actor, $note) {
            $approval->refresh();
            $this->guardParafDecision($approval, $actor);

            $approval->update([
                'paraf_status' => 'rejected',
                'paraf_note' => $note,
                'paraf_at' => now(),
                'status' => 'rejected',
                'note' => $note,
                'acted_at' => now(),
            ]);

            $approval->suratKeluar()->update(['status' => 'draft']);

            SuratKeluarApprovalHistory::create([
                'surat_keluar_approval_id' => $approval->id,
                'surat_keluar_id' => $approval->surat_keluar_id,
                'approver_id' => $actor->id,
                'action' => 'paraf_rejected',
                'note' => $note,
                'signer_name_snapshot' => $actor->name,
                'signer_title_snapshot' => optional($actor->jabatan)->nama ?: $actor->jabatan_keterangan,
                'acted_at' => now(),
            ]);
        });

        $approval = $approval->fresh(['suratKeluar', 'requester', 'parafUser']);
        if ($approval->requester) {
            $approval->requester->notify(new SuratTugasNotification(
                $approval->suratKeluar,
                'Paraf Surat Tugas ditolak',
                'Surat Tugas perlu diperbaiki sesuai catatan petugas paraf.',
                route('surat-template.index'),
                'requester'
            ));
            $this->whatsAppService->notifySuratTugasRequester($approval, $approval->requester, false, $note);
        }

        return $approval;
    }

    public function approve(SuratKeluarApproval $approval, User $actor, $note = null, $signatureData = null)
    {
        $previousStatus = $approval->status;

        DB::transaction(function () use ($approval, $actor, $note) {
            $approval->refresh();
            $this->guardDecision($approval, $actor);

            $approval->update([
                'status' => 'approved',
                'note' => $note,
                'acted_at' => now(),
                'signature_path' => null,
                'signature_mime' => null,
                'signature_size' => null,
            ]);

            SuratKeluarApprovalHistory::create([
                'surat_keluar_approval_id' => $approval->id,
                'surat_keluar_id' => $approval->surat_keluar_id,
                'approver_id' => $actor->id,
                'action' => 'approved',
                'note' => $note,
                'signer_name_snapshot' => $approval->signer_name_snapshot,
                'signer_title_snapshot' => $approval->signer_title_snapshot,
                'acted_at' => now(),
            ]);

            $approval->suratKeluar()->update(['status' => 'lengkap']);
        });

        $approval->loadMissing('suratKeluar', 'requester', 'approver');
        $this->auditService->log('persuratan', 'surat_keluar_approved', $approval, [
            'subject_type' => 'surat_keluar',
            'subject_id' => optional($approval->suratKeluar)->id,
            'subject_title' => optional($approval->suratKeluar)->perihal ?: $approval->template_name,
            'target_user_id' => optional($approval->requester)->id,
            'target_name' => optional($approval->requester)->name,
            'old_values_json' => ['status' => $previousStatus],
            'new_values_json' => ['status' => 'approved'],
            'note' => $note,
        ], $actor);

        $this->notifyTemplateWorkflow($approval->fresh(['suratKeluar.penerimaInternal', 'requester', 'approver']), true, $note);
    }

    public function reject(SuratKeluarApproval $approval, User $actor, $note)
    {
        $previousStatus = $approval->status;

        DB::transaction(function () use ($approval, $actor, $note) {
            $approval->refresh();
            $this->guardDecision($approval, $actor);

            $approval->update([
                'status' => 'rejected',
                'note' => $note,
                'acted_at' => now(),
                'signature_path' => null,
                'signature_mime' => null,
                'signature_size' => null,
            ]);

            SuratKeluarApprovalHistory::create([
                'surat_keluar_approval_id' => $approval->id,
                'surat_keluar_id' => $approval->surat_keluar_id,
                'approver_id' => $actor->id,
                'action' => 'rejected',
                'note' => $note,
                'signer_name_snapshot' => $approval->signer_name_snapshot,
                'signer_title_snapshot' => $approval->signer_title_snapshot,
                'acted_at' => now(),
            ]);

            $approval->suratKeluar()->update([
                'status' => 'draft',
            ]);
        });

        $approval->loadMissing('suratKeluar', 'requester', 'approver');
        $this->auditService->log('persuratan', 'surat_keluar_rejected', $approval, [
            'subject_type' => 'surat_keluar',
            'subject_id' => optional($approval->suratKeluar)->id,
            'subject_title' => optional($approval->suratKeluar)->perihal ?: $approval->template_name,
            'target_user_id' => optional($approval->requester)->id,
            'target_name' => optional($approval->requester)->name,
            'old_values_json' => ['status' => $previousStatus],
            'new_values_json' => ['status' => 'rejected'],
            'note' => $note,
        ], $actor);

        $this->notifyTemplateWorkflow($approval->fresh(['suratKeluar.penerimaInternal', 'requester', 'approver']), false, $note);
    }

    protected function guardDecision(SuratKeluarApproval $approval, User $actor)
    {
        if (!$actor->canActAsAssignedUser($approval->approver_id) && !$actor->isSuperAdmin()) {
            abort(403, 'Anda tidak berhak memproses approval surat ini.');
        }

        if ($approval->status !== 'pending') {
            abort(422, 'Approval surat ini tidak berada pada status pending.');
        }

        if (!$approval->isParafReady()) {
            abort(422, 'Surat Tugas masih menunggu paraf.');
        }
    }

    protected function guardParafDecision(SuratKeluarApproval $approval, User $actor)
    {
        if (!$actor->canActAsAssignedUser($approval->paraf_user_id) && !$actor->isSuperAdmin()) {
            abort(403, 'Anda tidak berhak memberikan paraf pada surat ini.');
        }

        if ($approval->status !== 'pending' || $approval->paraf_status !== 'pending') {
            abort(422, 'Surat ini tidak berada pada tahap paraf.');
        }
    }

    protected function notifyParafPending(SuratKeluarApproval $approval)
    {
        if (!$approval->parafUser || !$approval->suratKeluar) {
            return;
        }

        $approval->parafUser->notify(new SuratTugasNotification(
            $approval->suratKeluar,
            'Surat Tugas memerlukan paraf',
            'Terdapat draft Surat Tugas yang perlu diperiksa dan diparaf sebelum diajukan kepada penanda tangan.',
            route('surat-keluar.approval.show', $approval),
            'paraf'
        ));
        $this->whatsAppService->notifySuratTugasParafPending($approval, $approval->parafUser);
    }

    protected function notifySignerPending(SuratKeluarApproval $approval)
    {
        if (!$approval->approver || !$approval->suratKeluar) {
            return;
        }

        $approval->approver->notify(new SuratTugasNotification(
            $approval->suratKeluar,
            'Surat Tugas menunggu persetujuan',
            'Draft Surat Tugas telah diparaf dan menunggu persetujuan serta tanda tangan Anda.',
            route('surat-keluar.approval.show', $approval),
            'approver'
        ));
        $this->whatsAppService->notifySuratTugasSignerPending($approval, $approval->approver);
    }

    protected function notifyTemplateWorkflow(SuratKeluarApproval $approval, $approved, $note = null)
    {
        if ((string) $approval->template_slug !== 'surat-tugas') {
            return;
        }

        $suratKeluar = $approval->suratKeluar;
        $fieldValues = $approval->field_values ?: [];

        if ($approval->requester) {
            try {
                $approval->requester->notify(new SuratTugasNotification(
                    $suratKeluar,
                    $approved ? 'Surat Tugas telah disetujui' : 'Surat Tugas perlu diperbaiki',
                    $approved
                        ? 'Surat Tugas yang diajukan telah disetujui dan siap ditindaklanjuti.'
                        : 'Surat Tugas yang diajukan belum dapat disetujui. Mohon meninjau catatan perbaikan.',
                    route('surat-keluar.index'),
                    'requester'
                ));
            } catch (\Throwable $e) {
                Log::warning('Surat tugas requester notification skipped', [
                    'approval_id' => $approval->id,
                    'user_id' => $approval->requested_by,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->whatsAppService->notifySuratTugasRequester($approval, $approval->requester, $approved, $note);
        }

        if (!$approved) {
            return;
        }

        foreach ($suratKeluar->penerimaInternal->unique('id') as $recipient) {
            try {
                $recipient->notify(new SuratTugasNotification(
                    $suratKeluar,
                    'Surat Tugas untuk Anda telah tersedia',
                    'Terdapat Surat Tugas yang menetapkan Bapak/Ibu sebagai petugas pelaksana.',
                    URL::signedRoute('surat-keluar.signature.verify', ['approval' => $approval->id]),
                    'recipient'
                ));
            } catch (\Throwable $e) {
                Log::warning('Surat tugas recipient notification skipped', [
                    'approval_id' => $approval->id,
                    'user_id' => $recipient->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->whatsAppService->notifySuratTugasRecipient($suratKeluar, $recipient, $fieldValues);
        }
    }
}
