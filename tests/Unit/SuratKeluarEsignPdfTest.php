<?php

namespace Tests\Unit;

use App\SuratKeluar;
use App\SuratKeluarApproval;
use App\Services\PdfVerificationService;
use App\Services\SuratKeluarEsignService;
use FPDF;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class SuratKeluarEsignPdfTest extends TestCase
{
    public function test_configured_tte_images_are_available(): void
    {
        $service = new SuratKeluarEsignService(app(PdfVerificationService::class));

        foreach (['ketua', 'wakil_ketua', 'panitera', 'plt_sekretaris'] as $key) {
            $tte = $service->tteDefinition($key);
            $this->assertNotNull($tte);
            $this->assertFileExists($tte['path']);
        }
    }

    public function test_stamp_preserves_pages_and_adds_esign_box_to_selected_page(): void
    {
        $sourcePath = tempnam(sys_get_temp_dir(), 'papeda-source-');
        $resultPath = tempnam(sys_get_temp_dir(), 'papeda-result-');

        try {
            $source = new FPDF();
            $source->AddPage();
            $source->SetFont('Arial', '', 12);
            $source->Text(20, 30, 'HALAMAN SATU - SURAT KELUAR');
            $source->AddPage();
            $source->Text(20, 30, 'HALAMAN DUA - LAMPIRAN');
            $source->Output('F', $sourcePath);

            $service = new SuratKeluarEsignService(app(PdfVerificationService::class));
            $tte = $service->tteDefinition('ketua');
            $content = $service->stampPdfContent(
                $sourcePath,
                ['page' => 2, 'x' => 55, 'y' => 72, 'width' => 40, 'height' => 16],
                $tte['path']
            );

            file_put_contents($resultPath, $content);
            $reader = new Fpdi();
            $this->assertSame(2, $reader->setSourceFile($resultPath));
            $this->assertStringStartsWith('%PDF-', $content);
            $this->assertGreaterThan(filesize($sourcePath), strlen($content));
        } finally {
            @unlink($sourcePath);
            @unlink($resultPath);
        }
    }

    public function test_stamp_rejects_box_outside_pdf_page(): void
    {
        $sourcePath = tempnam(sys_get_temp_dir(), 'papeda-source-');

        try {
            $source = new FPDF();
            $source->AddPage();
            $source->Output('F', $sourcePath);

            $service = new SuratKeluarEsignService(app(PdfVerificationService::class));
            $tte = $service->tteDefinition('ketua');

            $this->expectException(\InvalidArgumentException::class);
            $service->stampPdfContent(
                $sourcePath,
                ['page' => 1, 'x' => 80, 'y' => 80, 'width' => 40, 'height' => 25],
                $tte['path']
            );
        } finally {
            @unlink($sourcePath);
        }
    }

    public function test_pending_approval_preview_includes_selected_tte(): void
    {
        Storage::fake('public');
        $source = new FPDF();
        $source->AddPage();
        $source->SetFont('Arial', '', 12);
        $source->Text(20, 30, 'DOKUMEN MENUNGGU E-SIGN');
        $sourceContent = $source->Output('S');
        Storage::disk('public')->put('surat-keluar/source.pdf', $sourceContent);

        $suratKeluar = new SuratKeluar([
            'nomor_surat' => '001/TEST/IX/2026',
            'perihal' => 'Pengujian E-Sign',
            'file_path' => 'surat-keluar/source.pdf',
        ]);
        $approval = new SuratKeluarApproval([
            'approver_id' => 1,
            'template_slug' => SuratKeluarEsignService::TEMPLATE_SLUG,
            'status' => 'pending',
            'field_values' => [
                'esign_placement' => ['page' => 1, 'x' => 55, 'y' => 72, 'width' => 40, 'height' => 16],
                'tte' => ['key' => 'ketua'],
            ],
        ]);
        $approval->setRelation('suratKeluar', $suratKeluar);

        $response = (new SuratKeluarEsignService(app(PdfVerificationService::class)))
            ->streamApprovalPreview($approval);

        $this->assertSame(200, $response->getStatusCode());
        $previewContent = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $previewContent);
        $this->assertGreaterThan(strlen($sourceContent), strlen($previewContent));
    }

    public function test_surat_keluar_has_e_sign_status_badge(): void
    {
        $suratKeluar = new SuratKeluar(['status' => 'e-sign']);
        $suratKeluar->setRelation('templateApproval', null);

        $this->assertStringContainsString('E-Sign', $suratKeluar->status_badge);
    }
}
