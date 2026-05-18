<?php

namespace App\Services;

use App\LeaveApproval;
use App\LeaveBalance;
use App\LeaveRequest;
use App\LeaveType;
use App\KlasifikasiKode;
use App\SuratKeluar;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Optional;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class LeaveDocumentService
{
    protected $signaturePadService;
    protected $pdfVerificationService;

    public function __construct(SignaturePadService $signaturePadService, PdfVerificationService $pdfVerificationService)
    {
        $this->signaturePadService = $signaturePadService;
        $this->pdfVerificationService = $pdfVerificationService;
    }

    public function storeUploadedDocuments(LeaveRequest $leaveRequest, array $files = [])
    {
        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            $documentType = $this->resolveDocumentType($leaveRequest);
            $storedPath = $file->store('cuti/documents/' . $leaveRequest->id, 'public');

            $leaveRequest->documents()->create([
                'document_type' => $documentType,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $storedPath,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => (int) $file->getSize(),
            ]);
        }
    }

    public function streamDecisionLetter(LeaveRequest $leaveRequest)
    {
        $leaveRequest->loadMissing([
            'user.unit',
            'user.jabatan',
            'user.atasanLangsung.jabatan',
            'leaveType',
            'documents',
            'approvals.approver.jabatan',
            'creator',
        ]);
        $content = $this->buildDecisionPdfContent($leaveRequest);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $this->buildFilename($leaveRequest) . '"',
        ]);
    }

    public function syncSuratKeluar(LeaveRequest $leaveRequest, $markComplete = false)
    {
        $leaveRequest->loadMissing([
            'user.unit',
            'user.jabatan',
            'leaveType',
            'approvals.approver',
            'creator',
            'documents',
            'suratKeluar',
        ]);

        $this->ensureLetterNumber($leaveRequest);
        [$klasifikasi, $fungsi, $kegiatan] = $this->resolveLeaveClassification();

        $suratKeluar = $leaveRequest->suratKeluar;
        if (!$suratKeluar && $leaveRequest->letter_number) {
            $suratKeluar = SuratKeluar::where('nomor_surat', $leaveRequest->letter_number)->first();
        }

        $issueDate = $leaveRequest->created_at
            ? $leaveRequest->created_at->copy()->timezone('Asia/Jayapura')
            : now('Asia/Jayapura');
        $nomorUrut = $this->extractLetterSequence($leaveRequest->letter_number);

        $payload = [
            'nomor_surat' => $leaveRequest->letter_number,
            'nomor_urut' => $nomorUrut ?: $this->nextLetterSequence(),
            'tahun_surat' => $issueDate->year,
            'klasifikasi_kode_id' => $klasifikasi->id,
            'kategori_surat_id' => null,
            'kode_fungsi_id' => $fungsi->id,
            'kode_kegiatan_id' => $kegiatan->id,
            'kode_transaksi_id' => null,
            'nomenklatur_jabatan' => 'ketua',
            'opsi_penerima' => 'internal',
            'penerima_external' => null,
            'perihal' => $this->buildSuratKeluarPerihal($leaveRequest),
            'tanggal_surat' => $issueDate->toDateString(),
            'has_lampiran' => $leaveRequest->documents->isNotEmpty(),
            'status' => $markComplete ? 'lengkap' : 'draft',
            'created_by' => $leaveRequest->created_by ?: $leaveRequest->user_id,
        ];

        $payload['file_path'] = null;

        if ($suratKeluar) {
            $suratKeluar->update($payload);
        } else {
            $suratKeluar = SuratKeluar::create($payload);
        }

        $suratKeluar->penerimaInternal()->sync($this->resolveSuratKeluarRecipients($leaveRequest));

        return $suratKeluar->fresh(['creator', 'penerimaInternal', 'klasifikasiKode', 'kodeFungsi', 'kodeKegiatan', 'kodeTransaksi']);
    }

    public function ensureLetterNumber(LeaveRequest $leaveRequest)
    {
        if (!empty($leaveRequest->letter_number)) {
            return $leaveRequest->letter_number;
        }

        $issueDate = $leaveRequest->created_at
            ? $leaveRequest->created_at->copy()->timezone('Asia/Jayapura')
            : now('Asia/Jayapura');

        $number = SuratKeluar::generateNomorSurat(
            'ketua',
            'KP',
            '5',
            '3',
            null,
            $issueDate->year,
            $issueDate->month,
            $this->nextLetterSequence()
        );

        $leaveRequest->letter_number = $number['nomor'];
        $leaveRequest->save();

        return $leaveRequest->letter_number;
    }

    public function buildSignatureVerificationData(LeaveRequest $leaveRequest, LeaveApproval $approval)
    {
        $leaveRequest->loadMissing(['user.unit', 'leaveType']);
        $approval->loadMissing('approver.jabatan');

        return [
            'valid' => $approval->status === 'approved',
            'document_number' => $leaveRequest->letter_number ?: $leaveRequest->request_number ?: '-',
            'document_type' => 'Formulir Permintaan dan Pemberian Cuti',
            'leave_type' => optional($leaveRequest->leaveType)->name ?: '-',
            'period' => $leaveRequest->period_label,
            'role_label' => $approval->role_label,
            'signer_name' => optional($approval->approver)->name ?: '-',
            'signer_title' => optional(optional($approval->approver)->jabatan)->nama ?: (optional($approval->approver)->jabatan_keterangan ?: '-'),
            'acted_at' => optional($approval->acted_at)->translatedFormat('d F Y H:i') . ' WIT',
            'status' => $approval->status_label,
            'verification_url' => $this->signatureVerificationUrl($leaveRequest, $approval),
        ];
    }

    public function buildApplicantSignatureVerificationData(LeaveRequest $leaveRequest)
    {
        $leaveRequest->loadMissing(['user.jabatan', 'leaveType']);

        return [
            'valid' => !is_null($leaveRequest->id),
            'document_number' => $leaveRequest->letter_number ?: $leaveRequest->request_number ?: '-',
            'document_type' => 'Formulir Permintaan dan Pemberian Cuti',
            'leave_type' => optional($leaveRequest->leaveType)->name ?: '-',
            'period' => $leaveRequest->period_label,
            'role_label' => 'Pemohon Cuti',
            'signer_name' => optional($leaveRequest->user)->name ?: '-',
            'signer_title' => optional(optional($leaveRequest->user)->jabatan)->nama ?: (optional($leaveRequest->user)->jabatan_keterangan ?: '-'),
            'acted_at' => optional($leaveRequest->submitted_at ?: $leaveRequest->created_at)->translatedFormat('d F Y H:i') . ' WIT',
            'status' => $leaveRequest->status_label,
            'verification_url' => $this->applicantSignatureVerificationUrl($leaveRequest),
        ];
    }

    protected function resolveDocumentType(LeaveRequest $leaveRequest)
    {
        $code = optional($leaveRequest->leaveType)->code;

        if ($code === 'CS') {
            return 'surat_dokter';
        }

        if ($code === 'CAP') {
            return 'dokumen_alasan_penting';
        }

        return 'dokumen_pendukung';
    }

    protected function buildFilename(LeaveRequest $leaveRequest)
    {
        return 'form-cuti-final-' . Str::slug(optional($leaveRequest->user)->name ?: 'pegawai') . '.pdf';
    }

    protected function buildStorageFilename(LeaveRequest $leaveRequest)
    {
        return 'cuti/final/' . $this->buildFilename($leaveRequest);
    }

    protected function buildFormViewData(LeaveRequest $leaveRequest)
    {
        $approvals = $leaveRequest->approvals->keyBy('role_name');
        $verifier = $approvals->get('verifikator_dokumen');
        $atasan = $approvals->get('atasan_langsung');
        $ppk = $approvals->get('ppk');

        return [
            'allTypes' => [
                'CT' => 'Cuti Tahunan',
                'CB' => 'Cuti Besar',
                'CS' => 'Cuti Sakit',
                'CM' => 'Cuti Melahirkan',
                'CAP' => 'Cuti Karena Alasan Penting',
                'CLTN' => 'Cuti di Luar Tanggungan Negara',
            ],
            'selectedTypeCode' => optional($leaveRequest->leaveType)->code,
            'verifier' => $verifier,
            'atasan' => $atasan,
            'ppk' => $ppk,
            'pemohonSignature' => null,
            'verifierParaf' => $this->buildParaf($verifier),
            'atasanSignature' => $this->makeApprovalSignatureImage($atasan),
            'ppkSignature' => $this->makeApprovalSignatureImage($ppk),
            'annualLeaveRows' => $this->buildAnnualLeaveRows($leaveRequest),
            'attachmentNames' => $leaveRequest->documents->pluck('original_name')->filter()->values(),
            'workPeriodText' => $this->buildWorkPeriodText(optional($leaveRequest->user)->tmt_pns, $leaveRequest->start_date),
        ];
    }

    protected function buildAnnualLeaveRows(LeaveRequest $leaveRequest)
    {
        $tahunan = LeaveType::where('code', LeaveType::CODE_TAHUNAN)->first();
        $currentYear = (int) optional($leaveRequest->start_date)->year ?: now()->year;
        $years = [$currentYear - 2, $currentYear - 1, $currentYear];
        $rows = [];

        foreach ($years as $year) {
            $balance = $tahunan
                ? LeaveBalance::where('user_id', $leaveRequest->user_id)->where('leave_type_id', $tahunan->id)->where('year', $year)->first()
                : null;

            $rows[] = [
                'year' => $year,
                'remaining' => $balance ? $balance->remaining_balance : 0,
                'used' => $balance ? $balance->used_days : 0,
                'note' => $this->buildAnnualLeaveNote($balance),
            ];
        }

        return $rows;
    }

    protected function buildParaf($approval = null)
    {
        if (!$approval || $approval->status !== 'approved' || !$approval->approver) {
            return null;
        }

        $parts = preg_split('/\s+/', trim((string) $approval->approver->name));
        $initials = collect($parts)->map(function ($part) {
            return Str::upper(Str::substr($part, 0, 1));
        })->implode('');

        return [
            'initials' => Str::limit($initials, 4, ''),
            'name' => $approval->approver->name,
            'acted_at' => optional($approval->acted_at)->translatedFormat('d/m/Y'),
        ];
    }

    protected function makeApprovalSignatureImage($approval = null)
    {
        if (!$approval || $approval->status !== 'approved') {
            return null;
        }

        return $this->signaturePadService->toDataUri($approval->signature_path);
    }

    protected function signatureVerificationUrl(LeaveRequest $leaveRequest, LeaveApproval $approval)
    {
        return URL::signedRoute('cuti.signature.verify', [
            'leaveRequest' => $leaveRequest->id,
            'approval' => $approval->id,
        ]);
    }

    protected function applicantSignatureVerificationUrl(LeaveRequest $leaveRequest)
    {
        return URL::signedRoute('cuti.signature.verify-applicant', [
            'leaveRequest' => $leaveRequest->id,
        ]);
    }

    protected function buildWorkPeriodText($tmtPns, $referenceDate)
    {
        $start = $this->normalizeDate($tmtPns);
        $end = $this->normalizeDate($referenceDate);

        if (!$start || !$end) {
            return '-';
        }

        $start = $start->startOfDay();
        $end = $end->startOfDay();

        if ($start->gt($end)) {
            return '-';
        }

        $years = $start->diffInYears($end);
        $shifted = $start->copy()->addYears($years);
        $months = $shifted->diffInMonths($end);

        return sprintf('%d tahun %02d bulan', $years, $months);
    }

    protected function normalizeDate($value)
    {
        if ($value instanceof Optional) {
            $value = $value->toDateString() ?: $value->toDateTimeString() ?: null;
        }

        if ($value instanceof Carbon) {
            return $value->copy();
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }

    protected function nextLetterSequence()
    {
        $suratKeluarMax = (int) (SuratKeluar::max('nomor_urut') ?: 0);
        $leaveMax = 0;

        LeaveRequest::whereNotNull('letter_number')
            ->pluck('letter_number')
            ->each(function ($number) use (&$leaveMax) {
                if (preg_match('#^(\d+)/KPTA\.W31-A/KP5\.3/[IVXLCDM]+/\d{4}$#', (string) $number, $matches)) {
                    $leaveMax = max($leaveMax, (int) $matches[1]);
                }
            });

        return max($suratKeluarMax, $leaveMax) + 1;
    }

    protected function buildDecisionPdfContent(LeaveRequest $leaveRequest)
    {
        $tempFiles = [];
        $verification = $this->pdfVerificationService->begin(
            'cuti',
            'formulir_cuti',
            $leaveRequest->id,
            'Formulir Permintaan dan Pemberian Cuti - ' . (optional($leaveRequest->user)->name ?: 'Pegawai'),
            $this->buildApprovalSigners($leaveRequest),
            [
                'nomor' => $leaveRequest->letter_number ?: $leaveRequest->request_number,
                'periode' => $leaveRequest->period_label,
                'jenis_cuti' => optional($leaveRequest->leaveType)->name,
            ]
        );

        try {
            $baseTempPath = $this->makeTempPdfPath('cuti-form-' . $leaveRequest->id);
            $basePdf = PDF::loadView('cuti.pdf.decision', [
                'leaveRequest' => $leaveRequest,
                'formData' => $this->buildFormViewData($leaveRequest),
                'pdfVerification' => $this->pdfVerificationService->viewData($verification),
            ])->setPaper([0, 0, 595.2, 843.56]);
            File::put($baseTempPath, $basePdf->output());
            $tempFiles[] = $baseTempPath;

            $sourceFiles = [$baseTempPath];

            foreach ($leaveRequest->documents as $document) {
                $attachment = $this->prepareLeaveAttachmentPdf($document);
                $sourceFiles[] = $attachment['path'];
                if (!empty($attachment['temporary'])) {
                    $tempFiles[] = $attachment['path'];
                }
            }

            if (count($sourceFiles) === 1) {
                $content = File::get($baseTempPath);
                $this->pdfVerificationService->finalize($verification, $content, $this->buildFilename($leaveRequest));
                return $content;
            }

            $mergedTempPath = $this->makeTempPdfPath('cuti-merged-' . $leaveRequest->id);
            $this->mergePdfFiles($sourceFiles, $mergedTempPath);
            $tempFiles[] = $mergedTempPath;

            $content = File::get($mergedTempPath);
            $this->pdfVerificationService->finalize($verification, $content, $this->buildFilename($leaveRequest));
            return $content;
        } finally {
            foreach ($tempFiles as $tempFile) {
                if ($tempFile && file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
        }
    }

    protected function buildApprovalSigners(LeaveRequest $leaveRequest)
    {
        return $leaveRequest->approvals
            ->where('status', 'approved')
            ->map(function ($approval) {
                return [
                    'name' => optional($approval->approver)->name ?: '-',
                    'role' => $approval->role_label,
                    'title' => optional(optional($approval->approver)->jabatan)->nama ?: optional($approval->approver)->jabatan_keterangan,
                    'signed_at' => $approval->acted_at ? $approval->acted_at->copy()->timezone('Asia/Jayapura')->translatedFormat('d F Y H:i') . ' WIT' : '-',
                ];
            })
            ->values()
            ->all();
    }

    protected function resolveLeaveClassification()
    {
        $klasifikasi = KlasifikasiKode::where('kode', 'KP')->where('tipe', 'klasifikasi')->firstOrFail();
        $fungsi = KlasifikasiKode::where('kode', 'KP5')->where('tipe', 'fungsi')->firstOrFail();
        $kegiatan = KlasifikasiKode::where('kode', 'KP5.3')->where('tipe', 'kegiatan')->firstOrFail();

        return [$klasifikasi, $fungsi, $kegiatan];
    }

    protected function resolveSuratKeluarRecipients(LeaveRequest $leaveRequest)
    {
        return $leaveRequest->approvals
            ->pluck('approver_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function extractLetterSequence($letterNumber)
    {
        if (preg_match('#^(\d+)/#', (string) $letterNumber, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function buildSuratKeluarPerihal(LeaveRequest $leaveRequest)
    {
        $leaveType = optional($leaveRequest->leaveType)->name ?: 'Cuti';
        $pegawai = optional($leaveRequest->user)->name ?: 'Pegawai';

        return 'Permintaan dan Pemberian ' . $leaveType . ' - ' . $pegawai;
    }

    protected function buildAnnualLeaveNote($balance)
    {
        if (!$balance) {
            return '0';
        }

        if ((int) $balance->used_days > 0) {
            return sprintf('Diambil %d hari sisa %d', (int) $balance->used_days, (int) $balance->remaining_balance);
        }

        if ((int) $balance->reserved_days > 0) {
            return sprintf('Reservasi %d hari', (int) $balance->reserved_days);
        }

        return (string) (int) $balance->remaining_balance;
    }

    protected function prepareLeaveAttachmentPdf($document)
    {
        $absolutePath = storage_path('app/public/' . $document->file_path);

        if (!file_exists($absolutePath)) {
            return [
                'path' => $this->renderUnsupportedAttachmentNoticePdf($document->original_name, 'File lampiran tidak ditemukan di storage.'),
                'temporary' => true,
            ];
        }

        $mime = strtolower((string) $document->mime_type);
        $extension = strtolower((string) pathinfo($absolutePath, PATHINFO_EXTENSION));

        if ($mime === 'application/pdf' || $extension === 'pdf') {
            return ['path' => $absolutePath, 'temporary' => false];
        }

        if (in_array($mime, ['image/jpeg', 'image/png', 'image/jpg'], true) || in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return ['path' => $this->renderImageAttachmentAsPdf($absolutePath, $document->original_name), 'temporary' => true];
        }

        return [
            'path' => $this->renderUnsupportedAttachmentNoticePdf(
                $document->original_name,
                'Lampiran ini disimpan di sistem, tetapi format file tidak dapat digabung langsung ke PDF final.'
            ),
            'temporary' => true,
        ];
    }

    protected function renderImageAttachmentAsPdf($imagePath, $documentName)
    {
        $imageData = $this->fileToDataUri($imagePath);
        if (!$imageData) {
            throw new \RuntimeException('Gagal membaca file gambar lampiran cuti.');
        }

        $safeName = htmlspecialchars((string) $documentName, ENT_QUOTES, 'UTF-8');
        $html = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: A4 portrait; margin: 1.5cm; }
        body { margin: 0; font-family: Arial, sans-serif; color: #111; }
        .title { font-size: 14px; font-weight: bold; margin-bottom: 10px; }
        .name { font-size: 11px; margin-bottom: 14px; }
        .wrap { width: 100%; text-align: center; }
        .img { max-width: 100%; max-height: 23.5cm; }
    </style>
</head>
<body>
    <div class="title">LAMPIRAN DOKUMEN PENDUKUNG CUTI</div>
    <div class="name">' . $safeName . '</div>
    <div class="wrap">
        <img class="img" src="' . $imageData . '" alt="' . $safeName . '">
    </div>
</body>
</html>';

        $tempPath = $this->makeTempPdfPath('cuti-image');
        $pdf = PDF::loadHTML($html)->setPaper('a4', 'portrait');
        File::put($tempPath, $pdf->output());

        return $tempPath;
    }

    protected function renderUnsupportedAttachmentNoticePdf($documentName, $message)
    {
        $safeName = htmlspecialchars((string) $documentName, ENT_QUOTES, 'UTF-8');
        $safeMessage = htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8');
        $html = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: A4 portrait; margin: 2cm; }
        body { margin: 0; font-family: Arial, sans-serif; color: #111; }
        .title { font-size: 15px; font-weight: bold; margin-bottom: 12px; }
        .box { border: 1px solid #111; padding: 18px; border-radius: 6px; }
        .label { font-weight: bold; margin-bottom: 6px; }
        .value { margin-bottom: 14px; }
    </style>
</head>
<body>
    <div class="title">LAMPIRAN DOKUMEN PENDUKUNG CUTI</div>
    <div class="box">
        <div class="label">Nama Lampiran</div>
        <div class="value">' . $safeName . '</div>
        <div class="label">Keterangan</div>
        <div>' . $safeMessage . '</div>
    </div>
</body>
</html>';

        $tempPath = $this->makeTempPdfPath('cuti-attachment-note');
        $pdf = PDF::loadHTML($html)->setPaper('a4', 'portrait');
        File::put($tempPath, $pdf->output());

        return $tempPath;
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
        $directory = storage_path('app/temp/cuti');
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
