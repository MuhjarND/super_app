<?php

namespace App\Services;

use App\Rapat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RapatAttendanceSignatureService
{
    protected $documentService;
    protected $qrCodeService;

    public function __construct(
        RapatDocumentService $documentService,
        DocumentQrCodeService $qrCodeService
    ) {
        $this->documentService = $documentService;
        $this->qrCodeService = $qrCodeService;
    }

    public function resolve(Rapat $rapat)
    {
        $rapat->loadMissing([
            'attendanceSigner.jabatan',
            'approver1.jabatan',
            'approver2.jabatan',
            'internalAttendances',
        ]);

        // Existing meetings continue to work by falling back to the invitation signatory.
        $signer = $rapat->attendanceSigner ?: $this->documentService->resolveDocumentSignatory($rapat);
        $attendance = $signer
            ? $rapat->internalAttendances->firstWhere('user_id', $signer->id)
            : null;
        $title = $signer
            ? trim((string) ($signer->jabatan_keterangan ?: optional($signer->jabatan)->nama))
            : '';
        $signatureAvailable = $attendance
            && $attendance->signature_path
            && Storage::disk('public')->exists($attendance->signature_path);
        $verificationUrl = $signatureAvailable
            ? $this->documentService->signatureVerificationUrl($rapat, ['signature' => 'attendance'])
            : null;
        $signatureImage = $verificationUrl
            ? $this->qrCodeService->dataUri($verificationUrl, 120)
            : null;

        return [
            'user' => $signer,
            'attendance' => $attendance,
            'configured' => (bool) $rapat->attendance_signer_id,
            'line1' => ($title !== '' ? rtrim($title, ',') : 'Pejabat Penanda Tangan') . ',',
            'line2' => 'Pengadilan Tinggi Agama Papua Barat',
            'name' => optional($signer)->name ?: '-',
            'nip' => optional($signer)->nip ?: null,
            'signed_at' => $attendance ? $attendance->attended_at : null,
            'image' => $signatureImage,
            'available' => (bool) $signatureAvailable,
            'verification_url' => $verificationUrl,
        ];
    }

    public function buildVerificationData(Rapat $rapat)
    {
        $rapat->loadMissing(['creator', 'kategoriSuratKode', 'suratKeluar']);
        $signature = $this->resolve($rapat);
        $signer = $signature['user'];

        return [
            'valid' => (bool) $signature['available'],
            'nomor' => optional($rapat->suratKeluar)->nomor_surat ?: ($rapat->nomor_undangan ?: '-'),
            'document_type' => 'Laporan Absensi Kegiatan',
            'judul' => $rapat->judul ?: '-',
            'status_label' => $signature['available'] ? 'Ditandatangani / Valid' : 'Belum ditandatangani',
            'signatory_name' => $signature['name'],
            'signatory_title' => optional($signer)->jabatan_keterangan ?: optional(optional($signer)->jabatan)->nama ?: '-',
            'signed_at' => $signature['signed_at']
                ? $signature['signed_at']->copy()->timezone('Asia/Jayapura')->translatedFormat('d F Y H:i') . ' WIT'
                : '-',
            'created_by' => optional($rapat->creator)->name ?: '-',
            'kategori' => optional($rapat->kategoriSuratKode)->kode
                ? ($rapat->kategoriSuratKode->kode . ' - ' . $rapat->kategoriSuratKode->nama)
                : '-',
            'token' => $rapat->token_qr ?: '-',
            'verification_url' => $signature['verification_url'],
        ];
    }

    public function assign(Rapat $rapat, $signerId)
    {
        $signerId = (int) $signerId;

        return DB::transaction(function () use ($rapat, $signerId) {
            if (!$rapat->pesertas()->where('users.id', $signerId)->exists()) {
                $lastOrder = (int) DB::table('rapat_peserta')
                    ->where('rapat_id', $rapat->id)
                    ->max('urutan');
                $rapat->pesertas()->attach($signerId, ['urutan' => $lastOrder + 1]);
            }

            $rapat->forceFill(['attendance_signer_id' => $signerId])->save();

            return $rapat->fresh(['attendanceSigner', 'pesertas']);
        });
    }
}
