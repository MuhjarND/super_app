<?php

namespace App\Services;

use App\SupplyRequest;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Collection;

class SupplyRequestDocumentService
{
    public function makePdf($requests)
    {
        $requests = $requests instanceof Collection
            ? $requests->values()
            : collect([$requests]);

        return PDF::loadView('persediaan.supplies.requests.pdf', [
            'requests' => $requests,
            'kopImage' => $this->resolveKopImage(),
        ])->setPaper('a4', 'portrait');
    }

    public function filename(SupplyRequest $request = null, $month = null, $userId = null)
    {
        if ($request) {
            return 'Formulir-Permintaan-ATK-' . $this->slug($request->request_number) . '.pdf';
        }

        $suffix = $month ? $month : 'semua-periode';
        if ($userId) {
            $suffix .= '-pegawai-' . (int) $userId;
        }

        return 'Formulir-Permintaan-ATK-' . $suffix . '.pdf';
    }

    protected function resolveKopImage()
    {
        $path = public_path('kop_undangan.png');
        if (!is_file($path)) {
            throw new \RuntimeException('Kop surat public/kop_undangan.png tidak ditemukan.');
        }

        $mime = function_exists('mime_content_type') ? mime_content_type($path) : 'image/png';
        $mime = $mime ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    protected function slug($value)
    {
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', (string) $value);

        return trim($slug, '-') ?: 'pengajuan';
    }
}
