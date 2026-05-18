<?php

namespace App\Services;

use App\Notifications\SuratTugasNotification;
use App\SuratKeluar;
use App\SuratKeluarApproval;
use App\SuratKeluarApprovalHistory;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SuratKeluarApprovalService
{
    protected $documentService;
    protected $auditService;
    protected $whatsAppService;
    protected $signaturePadService;

    public function __construct(SuratTemplateDocumentService $documentService, ActivityAuditService $auditService, WhatsAppNotificationService $whatsAppService, SignaturePadService $signaturePadService)
    {
        $this->documentService = $documentService;
        $this->auditService = $auditService;
        $this->whatsAppService = $whatsAppService;
        $this->signaturePadService = $signaturePadService;
    }

    public function syncForTemplate(SuratKeluar $suratKeluar, array $payload, User $approver, User $requester)
    {
        return DB::transaction(function () use ($suratKeluar, $payload, $approver, $requester) {
            $approval = SuratKeluarApproval::updateOrCreate(
                ['surat_keluar_id' => $suratKeluar->id],
                [
                    'approver_id' => $approver->id,
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
    }

    public function approve(SuratKeluarApproval $approval, User $actor, $note = null, $signatureData = null)
    {
        $previousStatus = $approval->status;

        DB::transaction(function () use ($approval, $actor, $note, $signatureData) {
            $approval->refresh();
            $this->guardDecision($approval, $actor);
            $signature = $this->signaturePadService->storeDataUri($signatureData, 'surat-keluar/approval-signatures');

            $approval->update([
                'status' => 'approved',
                'note' => $note,
                'acted_at' => now(),
                'signature_path' => $signature['path'],
                'signature_mime' => $signature['mime'],
                'signature_size' => $signature['size'],
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
        if ((int) $approval->approver_id !== (int) $actor->id && !$actor->isSuperAdmin()) {
            abort(403, 'Anda tidak berhak memproses approval surat ini.');
        }

        if ($approval->status !== 'pending') {
            abort(422, 'Approval surat ini tidak berada pada status pending.');
        }
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
                    route('surat-keluar.signature.verify', $approval),
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
