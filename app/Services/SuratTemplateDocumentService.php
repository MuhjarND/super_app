<?php

namespace App\Services;

use App\SuratKeluarApproval;
use App\SuratKeluar;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SuratTemplateDocumentService
{
    public function attachGeneratedDocument(SuratKeluar $suratKeluar, array $payload, array $options = [])
    {
        $filename = 'surat-keluar/template/' . now()->format('Y/m') . '/' . Str::slug($payload['template_name'] ?? 'template-surat', '-') . '-' . $suratKeluar->id . '.pdf';

        if ($suratKeluar->file_path && Storage::disk('public')->exists($suratKeluar->file_path)) {
            Storage::disk('public')->delete($suratKeluar->file_path);
        }

        $pdf = PDF::loadView('surat-template.pdf.document', [
            'suratKeluar' => $suratKeluar,
            'templateName' => $payload['template_name'] ?? 'Template Surat',
            'templateSlug' => $payload['template_slug'] ?? null,
            'renderedBody' => $payload['rendered_body'] ?? '',
            'fieldValues' => $payload['field_values'] ?? [],
            'kopImage' => $this->resolveKopImage(),
            'signatoryTitle' => $this->resolveSignatoryTitle($suratKeluar),
            'approvalSignature' => $options['approval_signature'] ?? null,
        ])->setPaper('a4', 'portrait');

        Storage::disk('public')->put($filename, $pdf->output());

        $suratKeluar->update([
            'file_path' => $filename,
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
            'barcode' => $this->makeApprovalBarcode($approval),
            'name' => $approval->signer_name_snapshot ?: optional($approval->approver)->name ?: '-',
            'title' => $approval->signer_title_snapshot ?: optional(optional($approval->approver)->jabatan)->nama ?: '-',
            'nip' => optional($approval->approver)->nip ?: '-',
        ];
    }

    public function streamApprovalPreview(SuratKeluarApproval $approval)
    {
        $approval->loadMissing('suratKeluar');
        $suratKeluar = $approval->suratKeluar;

        $content = PDF::loadView('surat-template.pdf.document', [
            'suratKeluar' => $suratKeluar,
            'templateName' => $approval->template_name ?: 'Template Surat',
            'templateSlug' => $approval->template_slug,
            'renderedBody' => $approval->rendered_body ?: '',
            'fieldValues' => $approval->field_values ?: [],
            'kopImage' => $this->resolveKopImage(),
            'signatoryTitle' => $this->resolveSignatoryTitle($suratKeluar),
            'approvalSignature' => $approval->status === 'approved' ? $this->buildApprovalSignature($approval) : null,
        ])->setPaper('a4', 'portrait')->output();

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="surat-keluar-' . $suratKeluar->id . '.pdf"',
        ]);
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

    protected function makeApprovalBarcode(SuratKeluarApproval $approval)
    {
        $url = URL::signedRoute('surat-keluar.signature.verify', [
            'approval' => $approval->id,
        ]);

        $svg = QrCode::format('svg')->size(180)->margin(1)->errorCorrection('H')->generate($url);
        $logoPath = public_path('logo_qr.png');

        if (file_exists($logoPath)) {
            $mime = mime_content_type($logoPath) ?: 'image/png';
            $logoData = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
            $logoSvg = '<image x="69" y="69" width="42" height="42" href="' . $logoData . '" xlink:href="' . $logoData . '" preserveAspectRatio="xMidYMid meet" />';
            $svg = str_replace('</svg>', $logoSvg . '</svg>', $svg);
        }

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
