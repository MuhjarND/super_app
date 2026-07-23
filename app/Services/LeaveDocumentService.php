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
    protected $qrCodeService;
    protected $pdfVerificationService;

    public function __construct(DocumentQrCodeService $qrCodeService, PdfVerificationService $pdfVerificationService)
    {
        $this->qrCodeService = $qrCodeService;
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
            $this->nextLetterSequence($issueDate->year)
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
            'valid' => in_array($approval->status, ['approved', 'changed', 'deferred'], true),
            'document_number' => $leaveRequest->letter_number ?: $leaveRequest->request_number ?: '-',
            'document_type' => 'Formulir Permintaan dan Pemberian Cuti',
            'leave_type' => optional($leaveRequest->leaveType)->name ?: '-',
            'period' => $leaveRequest->period_label,
            'role_label' => $approval->role_label,
            'signer_name' => optional($approval->approver)->name ?: '-',
            'signer_title' => optional($approval->approver)->jabatan_keterangan ?: '-',
            'acted_at' => optional($approval->acted_at)->translatedFormat('d F Y H:i') . ' WIT',
            'status' => $approval->status_label,
            'verification_url' => $this->signatureVerificationUrl($leaveRequest, $approval),
        ];
    }

    public function buildApplicantSignatureVerificationData(LeaveRequest $leaveRequest)
    {
        $leaveRequest->loadMissing(['user.jabatan', 'leaveType']);

        $isSigned = (bool) $leaveRequest->submitted_at || !in_array($leaveRequest->status, ['draft'], true);

        return [
            'valid' => $isSigned,
            'document_number' => $leaveRequest->letter_number ?: $leaveRequest->request_number ?: '-',
            'document_type' => 'Formulir Permintaan dan Pemberian Cuti',
            'leave_type' => optional($leaveRequest->leaveType)->name ?: '-',
            'period' => $leaveRequest->period_label,
            'role_label' => 'Pemohon Cuti',
            'signer_name' => optional($leaveRequest->user)->name ?: '-',
            'signer_title' => null,
            'acted_at' => optional($leaveRequest->submitted_at ?: $leaveRequest->created_at)->translatedFormat('d F Y H:i') . ' WIT',
            'status' => $isSigned ? 'Ditandatangani Pemohon melalui QR' : 'Belum Ditandatangani',
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

        // Requests submitted before separate same-person approval steps were
        // supported have no PPK row. Preserve both QR positions for those PDFs.
        if (
            !$ppk
            && $atasan
            && (int) optional($leaveRequest->user)->atasan_langsung_id > 0
            && (int) optional($leaveRequest->user)->atasan_langsung_id === (int) optional($leaveRequest->user)->pejabat_berwenang_id
        ) {
            $ppk = $atasan;
        }

        $approvalSignatures = $this->buildApprovalSignatureRows($leaveRequest);

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
            'pemohonSignature' => $this->makeApplicantSignatureQr($leaveRequest),
            'verifierParaf' => $this->buildParaf($verifier),
            'atasanSignature' => $this->makeApprovalSignatureImage($atasan),
            'ppkSignature' => $this->makeApprovalSignatureImage($ppk),
            'approvalSignatures' => $approvalSignatures,
            'additionalApprovalSignatures' => $approvalSignatures->reject(function ($signature) {
                return in_array($signature['role_name'], ['atasan_langsung', 'ppk'], true);
            })->values(),
            'annualLeaveRows' => $this->buildAnnualLeaveRows($leaveRequest),
            'attachmentNames' => $leaveRequest->documents->pluck('original_name')->filter()->values(),
            'employeeTitle' => optional($leaveRequest->user)->jabatan_keterangan ?: '-',
            'employeeUnit' => optional($leaveRequest->user)->satuan_kerja ?: '-',
            'employeeRank' => optional($leaveRequest->user)->golongan_ruang ?: '-',
            'workPeriodText' => optional($leaveRequest->user)->masa_kerja
                ? ucwords(optional($leaveRequest->user)->masa_kerja)
                : '-',
        ];
    }

    protected function buildApprovalSignatureRows(LeaveRequest $leaveRequest)
    {
        return $leaveRequest->approvals
            ->filter(function ($approval) {
                return in_array($approval->status, ['approved', 'changed', 'deferred'], true)
                    && $approval->role_name !== 'verifikator_dokumen';
            })
            ->map(function ($approval) {
                return [
                    'role_name' => $approval->role_name,
                    'role_label' => $approval->role_label,
                    'signature' => $this->makeApprovalSignatureImage($approval),
                    'name' => optional($approval->approver)->name ?: '-',
                    'nip' => optional($approval->approver)->nip ?: '-',
                    'title' => optional($approval->approver)->jabatan_keterangan ?: '-',
                    'acted_at' => optional($approval->acted_at)->translatedFormat('d/m/Y'),
                ];
            })
            ->values();
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
            $remainingAtRequest = $this->resolveAnnualLeaveBalanceAtRequest(
                $balance,
                $leaveRequest,
                $year
            );

            $rows[] = [
                'year' => $year,
                'remaining' => $remainingAtRequest,
                'used' => $balance ? $balance->used_days : 0,
                'note' => $this->buildAnnualLeaveNote($balance, $leaveRequest, $year, $remainingAtRequest),
            ];
        }

        return $rows;
    }

    protected function resolveAnnualLeaveBalanceAtRequest($balance, LeaveRequest $leaveRequest, $year)
    {
        if (!$balance) {
            return 0;
        }

        $currentRemaining = max(0, (int) $balance->remaining_balance);
        $isCurrentAnnualRequest = (int) $leaveRequest->leave_type_id === (int) $balance->leave_type_id
            && (int) $year === (int) optional($leaveRequest->start_date)->year;

        if (!$isCurrentAnnualRequest) {
            return $currentRemaining;
        }

        $snapshot = collect($leaveRequest->approver_chain_snapshot ?: [])
            ->pluck('leave_balance_snapshot')
            ->filter()
            ->first(function ($item) use ($balance, $year) {
                return (int) data_get($item, 'leave_type_id') === (int) $balance->leave_type_id
                    && (int) data_get($item, 'year') === (int) $year;
            });

        if (!is_null(data_get($snapshot, 'remaining_balance'))) {
            return max(0, (int) data_get($snapshot, 'remaining_balance'));
        }

        $balanceDeductedStatuses = [
            LeaveRequest::STATUS_SUBMITTED,
            LeaveRequest::STATUS_UNDER_REVIEW,
            LeaveRequest::STATUS_VERIFIED,
            LeaveRequest::STATUS_APPROVED,
            LeaveRequest::STATUS_COMPLETED,
        ];

        if (!in_array($leaveRequest->status, $balanceDeductedStatuses, true)) {
            return $currentRemaining;
        }

        $takenDays = (int) ($leaveRequest->approved_days
            ?: $leaveRequest->requested_days
            ?: $leaveRequest->workday_count);

        return max(0, $currentRemaining + $takenDays);
    }

    protected function buildParaf($approval = null)
    {
        if (!$approval || $approval->status !== 'approved' || !$approval->approver) {
            return null;
        }

        return [
            'checked' => true,
            'name' => $approval->approver->name,
            'acted_at' => optional($approval->acted_at)->translatedFormat('d/m/Y'),
        ];
    }

    protected function makeApprovalSignatureImage($approval = null)
    {
        if (!$approval || !in_array($approval->status, ['approved', 'changed', 'deferred'], true)) {
            return null;
        }

        return $this->qrCodeService->dataUri(
            $this->signatureVerificationUrl($approval->leaveRequest, $approval),
            120
        );
    }

    protected function makeApplicantSignatureQr(LeaveRequest $leaveRequest)
    {
        $isSigned = (bool) $leaveRequest->submitted_at || !in_array($leaveRequest->status, ['draft'], true);
        if (!$isSigned) {
            return null;
        }

        return $this->qrCodeService->dataUri($this->applicantSignatureVerificationUrl($leaveRequest), 120);
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

        return sprintf('%d Tahun %d Bulan', $years, $months);
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

    protected function nextLetterSequence($year = null)
    {
        return SuratKeluar::nextNomorUrut($year);
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
            ])->setPaper('a4', 'portrait');
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
        $signers = collect();

        if ($leaveRequest->submitted_at || !in_array($leaveRequest->status, ['draft'], true)) {
            $signers->push([
                'name' => optional($leaveRequest->user)->name ?: '-',
                'role' => 'Pemohon Cuti',
                'title' => optional($leaveRequest->user)->jabatan_keterangan ?: '-',
                'signed_at' => $leaveRequest->submitted_at ? $leaveRequest->submitted_at->copy()->timezone('Asia/Jayapura')->translatedFormat('d F Y H:i') . ' WIT' : '-',
            ]);
        }

        return $signers->merge($leaveRequest->approvals
            ->filter(function ($approval) {
                return in_array($approval->status, ['approved', 'changed', 'deferred'], true);
            })
            ->map(function ($approval) {
                return [
                    'name' => optional($approval->approver)->name ?: '-',
                    'role' => $approval->role_label,
                    'title' => optional($approval->approver)->jabatan_keterangan ?: '-',
                    'signed_at' => $approval->acted_at ? $approval->acted_at->copy()->timezone('Asia/Jayapura')->translatedFormat('d F Y H:i') . ' WIT' : '-',
                ];
            })
            ->values())
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

    protected function buildAnnualLeaveNote($balance, LeaveRequest $leaveRequest = null, $year = null, $remainingAtRequest = null)
    {
        if (!$balance) {
            return '0';
        }

        $remainingAtRequest = is_null($remainingAtRequest)
            ? max(0, (int) $balance->remaining_balance)
            : max(0, (int) $remainingAtRequest);
        $isCurrentAnnualRequest = $leaveRequest
            && (int) $leaveRequest->leave_type_id === (int) $balance->leave_type_id
            && (int) $year === (int) optional($leaveRequest->start_date)->year;

        if ($isCurrentAnnualRequest) {
            $takenDays = (int) ($leaveRequest->approved_days
                ?: $leaveRequest->requested_days
                ?: $leaveRequest->workday_count);

            if ($takenDays > 0) {
                $remainingAfterRequest = max(0, $remainingAtRequest - $takenDays);

                return sprintf('Diambil %d hari sisa %d', $takenDays, $remainingAfterRequest);
            }
        }

        if ((int) $balance->used_days > 0) {
            return sprintf('Diambil %d hari sisa %d', (int) $balance->used_days, $remainingAtRequest);
        }

        if ((int) $balance->reserved_days > 0) {
            return sprintf('Diambil %d hari', (int) $balance->reserved_days);
        }

        return (string) $remainingAtRequest;
    }

    protected function prepareLeaveAttachmentPdf($document)
    {
        $absolutePath = Storage::disk('public')->path($document->file_path);

        if (!file_exists($absolutePath)) {
            return [
                'path' => $this->renderUnsupportedAttachmentNoticePdf($document->original_name, 'File lampiran tidak ditemukan di storage.'),
                'temporary' => true,
            ];
        }

        $mime = strtolower((string) $document->mime_type);
        $extension = strtolower((string) pathinfo($absolutePath, PATHINFO_EXTENSION));

        if ($mime === 'application/pdf' || $extension === 'pdf') {
            if (!$this->canMergePdf($absolutePath)) {
                return [
                    'path' => $this->renderUnsupportedAttachmentNoticePdf(
                        $document->original_name,
                        'Lampiran PDF ini tersimpan di sistem, tetapi memakai teknik kompresi PDF yang tidak didukung oleh parser PDF gratis pada server. Silakan buka lampiran asli dari daftar dokumen pendukung.'
                    ),
                    'temporary' => true,
                ];
            }

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

    protected function canMergePdf($path)
    {
        try {
            $pdf = new Fpdi();
            $pdf->setSourceFile($path);
            return true;
        } catch (\Throwable $exception) {
            return false;
        }
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
            try {
                $pageCount = $pdf->setSourceFile($sourceFile);

                for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                    $templateId = $pdf->importPage($pageNumber);
                    $size = $pdf->getTemplateSize($templateId);
                    $orientation = $size['width'] > $size['height'] ? 'L' : 'P';

                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($templateId);
                }
            } catch (\Throwable $exception) {
                $fallbackPath = $this->renderUnsupportedAttachmentNoticePdf(
                    basename((string) $sourceFile),
                    'Bagian PDF ini tidak dapat digabung otomatis karena format kompresi PDF tidak didukung oleh parser server.'
                );

                try {
                    $pageCount = $pdf->setSourceFile($fallbackPath);
                    for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                        $templateId = $pdf->importPage($pageNumber);
                        $size = $pdf->getTemplateSize($templateId);
                        $orientation = $size['width'] > $size['height'] ? 'L' : 'P';

                        $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);
                    }
                } finally {
                    if (file_exists($fallbackPath)) {
                        @unlink($fallbackPath);
                    }
                }
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
