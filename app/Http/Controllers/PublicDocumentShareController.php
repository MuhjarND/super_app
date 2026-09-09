<?php

namespace App\Http\Controllers;

use App\Services\DocumentPreviewService;
use App\Services\DocumentShareLinkService;
use App\SuratKeluar;
use App\SuratMasuk;
use App\Support\DocumentFilename;

class PublicDocumentShareController extends Controller
{
    protected $shareLinkService;
    protected $documentPreviewService;

    public function __construct(DocumentShareLinkService $shareLinkService, DocumentPreviewService $documentPreviewService)
    {
        $this->shareLinkService = $shareLinkService;
        $this->documentPreviewService = $documentPreviewService;
    }

    public function show($token)
    {
        $data = $this->shareLinkService->resolve($token);
        abort_unless($data, 404, 'Tautan dokumen tidak valid.');
        abort_if($data['expired'], 410, 'Tautan dokumen sudah kedaluwarsa.');

        if ($data['type'] === DocumentShareLinkService::TYPE_INCOMING) {
            $surat = SuratMasuk::findOrFail($data['document_id']);
            if ($surat->file_path) {
                return $this->documentPreviewService->streamPublicFile(
                    $surat->file_path,
                    DocumentFilename::letterTitle($surat->nomor_surat, $surat->perihal)
                );
            }

            return view('public.document-share', [
                'jenis' => 'Surat Masuk',
                'nomor' => $surat->nomor_surat,
                'perihal' => $surat->perihal,
                'tanggal' => optional($surat->tanggal_surat)->translatedFormat('d F Y'),
            ]);
        }

        $surat = SuratKeluar::findOrFail($data['document_id']);
        if ($surat->hasAvailableFile()) {
            return app(SuratKeluarController::class)->streamAvailableFile($surat);
        }

        return view('public.document-share', [
            'jenis' => 'Surat Keluar',
            'nomor' => $surat->nomor_surat_formatted,
            'perihal' => $surat->perihal,
            'tanggal' => optional($surat->tanggal_surat)->translatedFormat('d F Y'),
        ]);
    }
}
