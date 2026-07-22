<?php

namespace App\Services;

use App\PdfVerification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfVerificationService
{
    protected $qrCodeService;

    public function __construct(DocumentQrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    public function begin($module, $documentType, $documentId, $title, array $signers = [], array $metadata = [])
    {
        return PdfVerification::create([
            'token' => (string) Str::uuid(),
            'module' => $module,
            'document_type' => $documentType,
            'document_id' => $documentId ? (string) $documentId : null,
            'title' => Str::limit((string) $title, 250, ''),
            'signers' => $signers,
            'metadata' => $metadata,
            'created_by' => auth()->id(),
        ]);
    }

    public function viewData(PdfVerification $verification)
    {
        $url = route('pdf-verification.show', $verification->token);

        return [
            'token' => $verification->token,
            'url' => $url,
            'qr' => $this->qrCodeService->dataUri($url, 120),
            'logo' => $this->qrCodeService->logoDataUri(),
        ];
    }

    public function finalize(PdfVerification $verification, $pdfContent, $filename = null)
    {
        $filename = $filename ?: ('dokumen-' . $verification->token . '.pdf');
        $path = 'pdf-verifications/' . now('Asia/Jayapura')->format('Y/m') . '/' . $verification->token . '.pdf';

        Storage::disk('public')->put($path, $pdfContent);

        $verification->update([
            'file_path' => $path,
            'original_filename' => $filename,
            'file_hash' => hash('sha256', $pdfContent),
            'file_size' => strlen($pdfContent),
            'finalized_at' => now('Asia/Jayapura'),
        ]);

        return $verification->fresh();
    }

    public function response($pdfContent, PdfVerification $verification, $filename, $disposition = 'inline')
    {
        $this->finalize($verification, $pdfContent, $filename);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
        ]);
    }
}
