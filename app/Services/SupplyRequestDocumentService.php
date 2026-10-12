<?php

namespace App\Services;

use App\SupplyRequest;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class SupplyRequestDocumentService
{
    public function makePdf($requests)
    {
        $requests = $requests instanceof Collection
            ? $requests->values()
            : collect([$requests]);

        // Load the saved handover signatures for both individual and batch
        // printing, including requests created before PDF printing existed.
        $requests = new EloquentCollection($requests->all());
        $requests->loadMissing(['requester', 'items', 'pickups']);
        $requestSignatures = $requests->map(function (SupplyRequest $request) {
            return $this->resolveRequestSignatures($request);
        })->all();

        return PDF::loadView('persediaan.supplies.requests.pdf', [
            'requests' => $requests,
            'kopImage' => $this->resolveKopImage(),
            'requestSignatures' => $requestSignatures,
        ])->setPaper('a4', 'portrait');
    }

    protected function resolveRequestSignatures(SupplyRequest $request)
    {
        // One signature is stored on every item in the same handover. Print
        // it only once, without substituting the user's profile signature.
        return $request->pickups->sortBy('id')
            ->pluck('receiver_signature_path')
            ->filter()
            ->unique()
            ->map(function ($path) {
                return app(SignaturePadService::class)->toDataUri($path);
            })
            ->filter()
            ->values()
            ->all();
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
