<?php

namespace App\Services;

use App\Rapat;
use Illuminate\Support\Facades\DB;

class RapatAttendanceSignatureService
{
    protected $documentService;
    protected $signaturePadService;

    public function __construct(
        RapatDocumentService $documentService,
        SignaturePadService $signaturePadService
    ) {
        $this->documentService = $documentService;
        $this->signaturePadService = $signaturePadService;
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
        $signatureImage = $attendance
            ? $this->signaturePadService->toDataUri($attendance->signature_path)
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
            'available' => !empty($signatureImage),
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
