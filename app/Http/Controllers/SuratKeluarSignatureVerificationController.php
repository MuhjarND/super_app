<?php

namespace App\Http\Controllers;

use App\SuratKeluarApproval;
use Illuminate\Http\Request;

class SuratKeluarSignatureVerificationController extends Controller
{
    public function show(Request $request, SuratKeluarApproval $approval)
    {
        abort_unless($request->hasValidSignature(), 403);

        $approval->loadMissing(['approver.jabatan', 'suratKeluar.kategoriSurat']);

        $data = [
            'valid' => $approval->status === 'approved',
            'document_number' => optional($approval->suratKeluar)->nomor_surat_formatted ?: '-',
            'document_type' => 'Surat Keluar',
            'document_title' => $approval->template_name ?: 'Surat Keluar',
            'status' => $approval->status_label,
            'signer_name' => $approval->signer_name_snapshot ?: optional($approval->approver)->name ?: '-',
            'signer_title' => $approval->signer_title_snapshot ?: optional(optional($approval->approver)->jabatan)->nama ?: '-',
            'acted_at' => optional($approval->acted_at)->translatedFormat('d F Y H:i') . ' WIT',
            'category' => optional(optional($approval->suratKeluar)->kategoriSurat)->kode ?: '-',
        ];

        return view('surat-keluar.signature.verify', compact('data'));
    }
}
