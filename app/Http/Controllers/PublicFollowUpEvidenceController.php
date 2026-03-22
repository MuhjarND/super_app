<?php

namespace App\Http\Controllers;

use App\RapatNotulensiTindakLanjut;

class PublicFollowUpEvidenceController extends Controller
{
    public function show($token)
    {
        $tindakLanjut = RapatNotulensiTindakLanjut::where('public_token', $token)->firstOrFail();
        abort_unless($tindakLanjut->eviden_path, 404);

        return response()->file(storage_path('app/public/' . $tindakLanjut->eviden_path));
    }
}
