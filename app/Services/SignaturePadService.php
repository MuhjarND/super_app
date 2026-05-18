<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SignaturePadService
{
    public function storeDataUri($dataUri, $directory = 'signatures')
    {
        if (!$dataUri || !is_string($dataUri)) {
            throw ValidationException::withMessages(['signature_data' => 'Tanda tangan wajib diisi.']);
        }

        if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $dataUri, $matches)) {
            throw ValidationException::withMessages(['signature_data' => 'Format tanda tangan tidak valid.']);
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $binary = base64_decode(preg_replace('/^data:image\/(png|jpeg|jpg);base64,/', '', $dataUri), true);

        if ($binary === false || strlen($binary) < 300) {
            throw ValidationException::withMessages(['signature_data' => 'Tanda tangan tidak valid atau masih kosong.']);
        }

        $path = trim($directory, '/') . '/' . now()->format('Y/m') . '/' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->put($path, $binary);

        return [
            'path' => $path,
            'mime' => 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension),
            'size' => strlen($binary),
        ];
    }

    public function toDataUri($path)
    {
        if (!$path || !Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(Storage::disk('public')->get($path));
    }
}
