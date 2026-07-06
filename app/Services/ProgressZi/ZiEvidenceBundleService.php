<?php

namespace App\Services\ProgressZi;

use App\LeaveRequest;
use App\Rapat;
use App\RapatLaporan;
use App\RapatNotulensi;
use App\Services\RapatDocumentService;
use App\Services\SuratTemplateDocumentService;
use App\SuratKeluar;
use App\SuratMasuk;
use App\ZiActivity;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class ZiEvidenceBundleService
{
    protected $rapatDocumentService;

    public function __construct(RapatDocumentService $rapatDocumentService)
    {
        $this->rapatDocumentService = $rapatDocumentService;
    }

    public function createBundle(ZiActivity $activity)
    {
        $activity->loadMissing([
            'area',
            'guidelineSubPoint.point',
            'realizations.evidences',
        ]);

        $evidences = $activity->realizations->flatMap(function ($realization) {
            return $realization->evidences;
        })->values();

        $sourceFiles = [];
        $tempFiles = [];

        $coverPath = $this->renderBundleCoverPdf($activity, $evidences);
        $sourceFiles[] = $coverPath;
        $tempFiles[] = $coverPath;

        foreach ($evidences as $evidence) {
            $resolved = $this->resolveEvidencePdfPath($evidence);
            if (!$resolved) {
                continue;
            }

            $sourceFiles[] = $resolved['path'];
            if (!empty($resolved['temporary'])) {
                $tempFiles[] = $resolved['path'];
            }
        }

        $bundlePath = $this->makeTempPdfPath('zi-evidence-bundle-' . $activity->id);
        $this->mergePdfFiles($sourceFiles, $bundlePath);

        foreach ($tempFiles as $tempFile) {
            if ($tempFile !== $bundlePath && $tempFile && file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }

        return [
            'path' => $bundlePath,
            'download_name' => 'bundle-eviden-progress-zi-' . Str::slug($activity->name ?: 'sub-poin') . '.pdf',
        ];
    }

    protected function resolveEvidencePdfPath($evidence)
    {
        if ($evidence->file_path) {
            return $this->resolveStorageAttachmentPath(
                $evidence->file_path,
                $evidence->file_name ?: $evidence->title
            );
        }

        switch ($evidence->source_reference_type) {
            case 'surat_masuk':
                $suratMasuk = SuratMasuk::find($evidence->source_reference_id);
                return $suratMasuk && $suratMasuk->file_path
                    ? $this->resolveStorageAttachmentPath($suratMasuk->file_path, $suratMasuk->nomor_surat ?: $evidence->title)
                    : $this->renderNoticePdf($evidence->title, 'File surat masuk tidak tersedia di storage.');
            case 'surat_keluar':
                $suratKeluar = SuratKeluar::find($evidence->source_reference_id);
                if ($suratKeluar && $suratKeluar->file_path) {
                    return $this->resolveStorageAttachmentPath($suratKeluar->file_path, $suratKeluar->nomor_surat ?: $evidence->title);
                }

                if ($suratKeluar && $suratKeluar->templateApproval) {
                    return app(SuratTemplateDocumentService::class)->createGeneratedTempFile($suratKeluar, 'zi-surat-keluar');
                }

                return $this->renderNoticePdf($evidence->title, 'File surat keluar tidak tersedia di storage.');
            case 'rapat':
                $rapat = Rapat::find($evidence->source_reference_id);
                if ($rapat) {
                    return $this->rapatDocumentService->createUndanganTempFile($rapat, 'zi-undangan-rapat');
                }

                return $this->renderNoticePdf($evidence->title, 'Undangan rapat belum tersedia sebagai file PDF lokal.');
            case 'rapat_notulensi':
                $notulensi = RapatNotulensi::find($evidence->source_reference_id);
                if ($notulensi && $notulensi->file_path && Storage::disk('public')->exists($notulensi->file_path) && $notulensi->file_mime === 'application/pdf') {
                    return [
                        'path' => Storage::disk('public')->path($notulensi->file_path),
                        'temporary' => false,
                    ];
                }

                return $this->renderNoticePdf($evidence->title, 'Notulensi tersedia di sistem, tetapi file PDF lokal belum tersedia untuk digabung.');
            case 'rapat_laporan':
                $laporan = RapatLaporan::find($evidence->source_reference_id);
                if ($laporan && $laporan->file_path && Storage::disk('public')->exists($laporan->file_path)) {
                    return [
                        'path' => Storage::disk('public')->path($laporan->file_path),
                        'temporary' => false,
                    ];
                }

                return $this->renderNoticePdf($evidence->title, 'Laporan tindak lanjut belum tersedia sebagai file PDF lokal.');
            case 'leave_request':
                $leaveRequest = LeaveRequest::find($evidence->source_reference_id);
                return $this->renderNoticePdf(
                    $evidence->title,
                    $leaveRequest ? 'Dokumen cuti tersedia melalui preview sistem, tetapi file PDF lokal tidak disimpan sebagai lampiran bundel.' : 'Dokumen cuti tidak ditemukan.'
                );
            default:
                return $this->renderNoticePdf($evidence->title, 'Eviden ini belum memiliki file yang dapat digabung otomatis.');
        }
    }

    protected function resolveStorageAttachmentPath($relativePath, $documentName)
    {
        if (!$relativePath || !Storage::disk('public')->exists($relativePath)) {
            return $this->renderNoticePdf($documentName, 'File eviden tidak ditemukan di storage.');
        }

        $absolutePath = Storage::disk('public')->path($relativePath);
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            return [
                'path' => $absolutePath,
                'temporary' => false,
            ];
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return [
                'path' => $this->renderImageAttachmentAsPdf($absolutePath, $documentName),
                'temporary' => true,
            ];
        }

        return $this->renderNoticePdf($documentName, 'Format file tersimpan di sistem, tetapi belum dapat digabung langsung ke bundel PDF.');
    }

    protected function renderBundleCoverPdf(ZiActivity $activity, $evidences)
    {
        $html = view('progress-zi.pdf.evidence-bundle-cover', [
            'activity' => $activity,
            'evidences' => $evidences,
        ])->render();

        $tempPath = $this->makeTempPdfPath('zi-bundle-cover-' . $activity->id);
        File::put($tempPath, PDF::loadHTML($html)->setPaper('a4', 'portrait')->output());

        return $tempPath;
    }

    protected function renderImageAttachmentAsPdf($imagePath, $documentName)
    {
        $imageData = $this->fileToDataUri($imagePath);
        if (!$imageData) {
            throw new \RuntimeException('Gagal membaca file gambar eviden Progress ZI.');
        }

        $safeName = htmlspecialchars((string) $documentName, ENT_QUOTES, 'UTF-8');
        $html = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><style>@page { size: A4 portrait; margin: 1.5cm; } body { margin:0; font-family: Arial, sans-serif; color:#111; } .title { font-size:14px; font-weight:bold; margin-bottom:10px; } .name { font-size:11px; margin-bottom:14px; } .wrap { width:100%; text-align:center; } .img { max-width:100%; max-height:23.5cm; }</style></head><body><div class="title">LAMPIRAN EVIDEN PROGRESS ZI</div><div class="name">' . $safeName . '</div><div class="wrap"><img class="img" src="' . $imageData . '" alt="' . $safeName . '"></div></body></html>';

        $tempPath = $this->makeTempPdfPath('zi-image');
        File::put($tempPath, PDF::loadHTML($html)->setPaper('a4', 'portrait')->output());

        return $tempPath;
    }

    protected function renderNoticePdf($documentName, $message)
    {
        $safeName = htmlspecialchars((string) $documentName, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8');
        $html = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><style>@page { size: A4 portrait; margin: 2cm; } body { margin:0; font-family: Arial, sans-serif; color:#111; } .title { font-size:15px; font-weight:bold; margin-bottom:12px; } .box { border:1px solid #111; padding:18px; border-radius:6px; } .label { font-weight:bold; margin-bottom:6px; } .value { margin-bottom:14px; }</style></head><body><div class="title">EVIDEN PROGRESS ZI</div><div class="box"><div class="label">Nama Eviden</div><div class="value">' . $safeName . '</div><div class="label">Keterangan</div><div>' . $safeMessage . '</div></div></body></html>';

        $tempPath = $this->makeTempPdfPath('zi-evidence-note');
        File::put($tempPath, PDF::loadHTML($html)->setPaper('a4', 'portrait')->output());

        return [
            'path' => $tempPath,
            'temporary' => true,
        ];
    }

    protected function mergePdfFiles(array $sourceFiles, $outputPath)
    {
        $pdf = new Fpdi();

        foreach ($sourceFiles as $sourceFile) {
            $pageCount = $pdf->setSourceFile($sourceFile);

            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $templateId = $pdf->importPage($pageNumber);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';

                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }
        }

        $pdf->Output('F', $outputPath);
    }

    protected function makeTempPdfPath($prefix)
    {
        $directory = storage_path('app/temp/progress-zi');
        if (!is_dir($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        return $directory . DIRECTORY_SEPARATOR . $prefix . '-' . Str::uuid() . '.pdf';
    }

    protected function fileToDataUri($path)
    {
        if (!$path || !file_exists($path)) {
            return null;
        }

        $mime = File::mimeType($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }
}
