<?php

namespace App\Http\Controllers;

use App\PdfVerification;
use App\LeaveRequest;
use App\Rapat;
use App\SuratKeluar;
use App\Support\DocumentFilename;
use Illuminate\Support\Facades\Storage;

class PdfVerificationController extends Controller
{
    public function show($token)
    {
        $verification = PdfVerification::where('token', $token)->firstOrFail();

        return view('pdf-verification.show', compact('verification'));
    }

    public function preview($token)
    {
        $verification = PdfVerification::where('token', $token)->firstOrFail();

        abort_unless($verification->file_path && Storage::disk('public')->exists($verification->file_path), 404);

        $letterNumber = data_get($verification->metadata, 'nomor')
            ?: data_get($verification->metadata, 'nomor_surat')
            ?: data_get($verification->metadata, 'letter_number');
        $extension = pathinfo((string) $verification->original_filename, PATHINFO_EXTENSION) ?: 'pdf';
        $filename = $letterNumber
            ? DocumentFilename::fromLetter($letterNumber, $this->resolveLetterSubject($verification), $extension)
            : ($verification->original_filename ?: 'dokumen-terverifikasi.pdf');

        return response()->file(Storage::disk('public')->path($verification->file_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    protected function resolveLetterSubject(PdfVerification $verification)
    {
        $metadataSubject = trim((string) data_get($verification->metadata, 'perihal'));
        if ($metadataSubject !== '') {
            return $metadataSubject;
        }

        if ($verification->module === 'surat_keluar') {
            return optional(SuratKeluar::find($verification->document_id))->perihal;
        }

        if ($verification->module === 'rapat') {
            $suratKeluarId = data_get($verification->metadata, 'surat_keluar_id');
            if ($suratKeluarId) {
                return optional(SuratKeluar::find($suratKeluarId))->perihal;
            }

            $rapat = Rapat::with('suratKeluar')->find($verification->document_id);
            return optional(optional($rapat)->suratKeluar)->perihal ?: optional($rapat)->judul;
        }

        if ($verification->module === 'cuti') {
            $leaveRequest = LeaveRequest::with('suratKeluar')->find($verification->document_id);
            return optional(optional($leaveRequest)->suratKeluar)->perihal;
        }

        return null;
    }
}
