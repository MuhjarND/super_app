<?php

namespace App\Http\Controllers;

use App\PdfVerification;
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

        return response()->file(Storage::disk('public')->path($verification->file_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . ($verification->original_filename ?: 'dokumen-terverifikasi.pdf') . '"',
        ]);
    }
}
