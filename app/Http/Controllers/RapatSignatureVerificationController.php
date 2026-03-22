<?php

namespace App\Http\Controllers;

use App\Rapat;
use Illuminate\Http\Request;
use App\Services\RapatDocumentService;

class RapatSignatureVerificationController extends Controller
{
    protected $documentService;

    public function __construct(RapatDocumentService $documentService)
    {
        $this->documentService = $documentService;
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
        ])->where('token_qr', $token)->firstOrFail();

        $signatureType = in_array($request->query('signature'), ['notulis', 'notulensi_approval'], true)
            ? $request->query('signature')
            : 'approval';
        $notulensi = null;

        if (in_array($signatureType, ['notulis', 'notulensi_approval'], true)
            && $rapat->notulensi
            && (int) $request->query('notulensi') === (int) $rapat->notulensi->id) {
            $notulensi = $rapat->notulensi;
        }

        $verification = $this->documentService->buildSignatureVerificationData($rapat, $signatureType, $notulensi);

        return view('rapat.verification.show', compact('rapat', 'verification'));
    }
}
