<?php

namespace App\Services;

use App\SuratKeluar;
use App\SuratKeluarApproval;
use App\SuratKeluarApprovalHistory;
use App\User;
use Illuminate\Support\Facades\DB;

class SuratKeluarApprovalService
{
    protected $documentService;

    public function __construct(SuratTemplateDocumentService $documentService)
    {
        $this->documentService = $documentService;
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
                ]
            );

            $this->documentService->attachGeneratedDocument($suratKeluar, $payload, [
                'status' => 'draft',
            ]);

            return $approval;
        });
    }

    public function approve(SuratKeluarApproval $approval, User $actor, $note = null)
    {
        DB::transaction(function () use ($approval, $actor, $note) {
            $approval->refresh();
            $this->guardDecision($approval, $actor);

            $approval->update([
                'status' => 'approved',
                'note' => $note,
                'acted_at' => now(),
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

            $this->documentService->attachGeneratedDocument($approval->suratKeluar, [
                'template_name' => $approval->template_name,
                'template_slug' => $approval->template_slug,
                'rendered_body' => $approval->rendered_body,
                'field_values' => $approval->field_values ?: [],
            ], [
                'status' => 'lengkap',
                'approval_signature' => $this->documentService->buildApprovalSignature($approval->fresh(['approver', 'suratKeluar'])),
            ]);
        });
    }

    public function reject(SuratKeluarApproval $approval, User $actor, $note)
    {
        DB::transaction(function () use ($approval, $actor, $note) {
            $approval->refresh();
            $this->guardDecision($approval, $actor);

            $approval->update([
                'status' => 'rejected',
                'note' => $note,
                'acted_at' => now(),
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
}
