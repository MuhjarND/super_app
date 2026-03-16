<?php

namespace App\Http\Controllers;

use App\Rapat;
use App\Services\RapatDocumentService;

class RapatSignatureVerificationController extends Controller
{
    protected $documentService;

    public function __construct(RapatDocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    public function show($token)
    {
        $rapat = Rapat::with([
            'creator',
            'approver1.jabatan',
            'approver2.jabatan',
            'approvals.approver.jabatan',
            'kategoriSuratKode.parent.parent.parent',
            'suratKeluar',
        ])->where('token_qr', $token)->firstOrFail();

        $verification = $this->documentService->buildSignatureVerificationData($rapat);

        return view('rapat.verification.show', compact('rapat', 'verification'));
    }
}
