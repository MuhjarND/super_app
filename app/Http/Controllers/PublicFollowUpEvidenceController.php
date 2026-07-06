<?php

namespace App\Http\Controllers;

use App\RapatNotulensiTindakLanjut;
use Illuminate\Support\Facades\Storage;

class PublicFollowUpEvidenceController extends Controller
{
    public function show($token)
    {
        $tindakLanjut = RapatNotulensiTindakLanjut::where('public_token', $token)->firstOrFail();
        abort_unless($tindakLanjut->eviden_path, 404);

        return response()->file(Storage::disk('public')->path($tindakLanjut->eviden_path));
    }
}
