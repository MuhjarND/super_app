<?php

namespace App\Http\Controllers;

use App\Rapat;
use Illuminate\Http\Request;
use App\Services\RapatAttendanceSignatureService;
use App\Services\RapatDocumentService;

class RapatSignatureVerificationController extends Controller
{
    protected $documentService;
    protected $attendanceSignatureService;

    public function __construct(
        RapatDocumentService $documentService,
        RapatAttendanceSignatureService $attendanceSignatureService
    )
    {
        $this->documentService = $documentService;
        $this->attendanceSignatureService = $attendanceSignatureService;
    }

    public function show(Request $request, $token)
    {
        $rapat = Rapat::with([
            'creator',
            'approver1.jabatan',
            'approver2.jabatan',
            'approvals.approver.jabatan',
            'kategoriSuratKode.parent.parent.parent',
            'suratKeluar',
            'notulensi.notulis.jabatan',
            'notulensi.approval.approver.jabatan',
            'attendanceSigner.jabatan',
            'internalAttendances',
        ])->where('token_qr', $token)->firstOrFail();

        $signatureType = in_array($request->query('signature'), ['notulis', 'notulensi_approval', 'attendance'], true)
            ? $request->query('signature')
            : 'approval';
        $notulensi = null;

        if (in_array($signatureType, ['notulis', 'notulensi_approval'], true)
            && $rapat->notulensi
            && (int) $request->query('notulensi') === (int) $rapat->notulensi->id) {
            $notulensi = $rapat->notulensi;
        }

        $verification = $signatureType === 'attendance'
            ? $this->attendanceSignatureService->buildVerificationData($rapat)
            : $this->documentService->buildSignatureVerificationData($rapat, $signatureType, $notulensi);

        return view('rapat.verification.show', compact('rapat', 'verification'));
    }
}
