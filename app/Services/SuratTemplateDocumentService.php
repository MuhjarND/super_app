<?php

namespace App\Services;

use App\SuratKeluarApproval;
use App\SuratKeluar;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SuratTemplateDocumentService
{
    protected $qrCodeService;
    protected $pdfVerificationService;

    public function __construct(DocumentQrCodeService $qrCodeService, PdfVerificationService $pdfVerificationService)
    {
        $this->qrCodeService = $qrCodeService;
        $this->pdfVerificationService = $pdfVerificationService;
    }

    public function attachGeneratedDocument(SuratKeluar $suratKeluar, array $payload, array $options = [])
    {
        $filename = Str::slug($payload['template_name'] ?? 'template-surat', '-') . '-' . $suratKeluar->id . '.pdf';
        $verification = $this->pdfVerificationService->begin(
            'surat_keluar',
            $payload['template_slug'] ?? 'template_surat',
            $suratKeluar->id,
            ($payload['template_name'] ?? 'Template Surat') . ' - ' . ($suratKeluar->nomor_surat ?: $suratKeluar->id),
            $this->buildSuratSigners($options['approval_signature'] ?? null),
            ['nomor' => $suratKeluar->nomor_surat]
        );

        $pdf = PDF::loadView('surat-template.pdf.document', [
            'suratKeluar' => $suratKeluar,
            'templateName' => $payload['template_name'] ?? 'Template Surat',
            'templateSlug' => $payload['template_slug'] ?? null,
            'renderedBody' => $payload['rendered_body'] ?? '',
            'fieldValues' => $payload['field_values'] ?? [],
            'kopImage' => $this->resolveKopImage(),
            'signatoryTitle' => $this->resolveSignatoryTitle($suratKeluar),
            'approvalSignature' => $options['approval_signature'] ?? null,
            'pdfVerification' => $this->pdfVerificationService->viewData($verification),
        ])->setPaper('a4', 'portrait');

        $content = $pdf->output();
        $this->pdfVerificationService->finalize($verification, $content, $filename);

        $suratKeluar->update([
            'status' => $options['status'] ?? 'lengkap',
        ]);

        return $filename;
    }

    public function buildApprovalSignature(SuratKeluarApproval $approval)
    {
        $approval->loadMissing(['approver.jabatan', 'suratKeluar']);

        if ($approval->status !== 'approved') {
            return null;
        }

        return [
            'image' => $this->qrCodeService->dataUri(URL::signedRoute('surat-keluar.signature.verify', ['approval' => $approval->id]), 120),
            'name' => $approval->signer_name_snapshot ?: optional($approval->approver)->name ?: '-',
            'title' => $approval->signer_title_snapshot ?: optional(optional($approval->approver)->jabatan)->nama ?: '-',
            'nip' => optional($approval->approver)->nip ?: '-',
            'signed_at' => $approval->acted_at ? $approval->acted_at->copy()->timezone('Asia/Jayapura')->translatedFormat('d F Y H:i') . ' WIT' : '-',
        ];
    }

    public function streamApprovalPreview(SuratKeluarApproval $approval)
    {
        $approval->loadMissing('suratKeluar');
        $suratKeluar = $approval->suratKeluar;
        $verification = $this->pdfVerificationService->begin(
            'surat_keluar',
            $approval->template_slug ?: 'template_surat',
            $suratKeluar->id,
            ($approval->template_name ?: 'Template Surat') . ' - ' . ($suratKeluar->nomor_surat ?: $suratKeluar->id),
            $this->buildSuratSigners($approval->status === 'approved' ? $this->buildApprovalSignature($approval) : null),
            ['nomor' => $suratKeluar->nomor_surat]
        );

        $content = PDF::loadView('surat-template.pdf.document', [
            'suratKeluar' => $suratKeluar,
            'templateName' => $approval->template_name ?: 'Template Surat',
            'templateSlug' => $approval->template_slug,
            'renderedBody' => $approval->rendered_body ?: '',
            'fieldValues' => $approval->field_values ?: [],
            'kopImage' => $this->resolveKopImage(),
            'signatoryTitle' => $this->resolveSignatoryTitle($suratKeluar),
            'approvalSignature' => $approval->status === 'approved' ? $this->buildApprovalSignature($approval) : null,
            'pdfVerification' => $this->pdfVerificationService->viewData($verification),
        ])->setPaper('a4', 'portrait')->output();

        return $this->pdfVerificationService->response($content, $verification, 'surat-keluar-' . $suratKeluar->id . '.pdf');
    }

    public function createGeneratedTempFile(SuratKeluar $suratKeluar, $prefix = 'surat-keluar')
    {
        $suratKeluar->loadMissing('templateApproval');
        $approval = $suratKeluar->templateApproval;

        if (!$approval) {
            return null;
        }

        $verification = $this->pdfVerificationService->begin(
            'surat_keluar',
            $approval->template_slug ?: 'template_surat',
            $suratKeluar->id,
            ($approval->template_name ?: 'Template Surat') . ' - ' . ($suratKeluar->nomor_surat ?: $suratKeluar->id),
            $this->buildSuratSigners($approval->status === 'approved' ? $this->buildApprovalSignature($approval) : null),
            ['nomor' => $suratKeluar->nomor_surat]
        );

        $content = PDF::loadView('surat-template.pdf.document', [
            'suratKeluar' => $suratKeluar,
            'templateName' => $approval->template_name ?: 'Template Surat',
            'templateSlug' => $approval->template_slug,
            'renderedBody' => $approval->rendered_body ?: '',
            'fieldValues' => $approval->field_values ?: [],
            'kopImage' => $this->resolveKopImage(),
            'signatoryTitle' => $this->resolveSignatoryTitle($suratKeluar),
            'approvalSignature' => $approval->status === 'approved' ? $this->buildApprovalSignature($approval) : null,
            'pdfVerification' => $this->pdfVerificationService->viewData($verification),
        ])->setPaper('a4', 'portrait')->output();

        $this->pdfVerificationService->finalize($verification, $content, 'surat-keluar-' . $suratKeluar->id . '.pdf');

        $dir = storage_path('app/temp/surat-keluar');
        if (!is_dir($dir)) {
            File::makeDirectory($dir, 0755, true, true);
        }

        $path = $dir . DIRECTORY_SEPARATOR . $prefix . '-' . $suratKeluar->id . '-' . Str::uuid() . '.pdf';
        File::put($path, $content);

        return [
            'path' => $path,
            'temporary' => true,
        ];
    }

    protected function buildSuratSigners($approvalSignature = null)
    {
        if (!$approvalSignature) {
            return [];
        }

        return [[
            'name' => $approvalSignature['name'] ?? '-',
            'role' => 'Penandatangan Surat',
            'title' => $approvalSignature['title'] ?? '-',
            'signed_at' => $approvalSignature['signed_at'] ?? '-',
        ]];
    }

    protected function resolveKopImage()
    {
        $candidates = [
            public_path('kop_undangan.png'),
            public_path('kop_undangan.jpg'),
            public_path('kop_undangan.jpeg'),
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                $mime = mime_content_type($candidate) ?: 'image/png';
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($candidate));
            }
        }

        return null;
    }

    protected function resolveSignatoryTitle(SuratKeluar $suratKeluar)
    {
        $map = [
            'ketua' => 'Ketua,',
            'wakil_ketua' => 'Wakil Ketua,',
            'sekretaris' => 'Sekretaris,',
            'panitera' => 'Panitera,',
        ];

        return [
            'line1' => $map[$suratKeluar->nomenklatur_jabatan] ?? 'Pejabat Penandatangan,',
            'line2' => 'Pengadilan Tinggi Agama Papua Barat',
        ];
    }

}
