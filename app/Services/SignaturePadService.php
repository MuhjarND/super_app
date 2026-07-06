<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;

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

    public function storeUploadedFile(UploadedFile $file, $directory = 'signatures')
    {
        if (!in_array(strtolower($file->getClientOriginalExtension()), ['png', 'jpg', 'jpeg'], true)) {
            throw ValidationException::withMessages(['profile_signature_file' => 'Tanda tangan harus berupa PNG, JPG, atau JPEG.']);
        }

        $path = $file->store(trim($directory, '/'), 'public');

        return [
            'path' => $path,
            'mime' => $file->getClientMimeType() ?: Storage::disk('public')->mimeType($path) ?: 'image/png',
            'size' => $file->getSize(),
        ];
    }

    public function resolveForUser($user, $directory = 'signatures', $signatureData = null)
    {
        if ($signatureData) {
            return $this->storeDataUri($signatureData, $directory);
        }

        if (!$user || empty($user->profile_signature_path) || !Storage::disk('public')->exists($user->profile_signature_path)) {
            throw ValidationException::withMessages([
                'signature_data' => 'Tanda tangan profil belum tersedia. Silakan simpan tanda tangan pada Profil Saya terlebih dahulu.',
            ]);
        }

        return $this->copyStoredSignature($user->profile_signature_path, $directory);
    }

    public function copyStoredSignature($sourcePath, $directory = 'signatures')
    {
        if (!$sourcePath || !Storage::disk('public')->exists($sourcePath)) {
            throw ValidationException::withMessages(['signature_data' => 'Tanda tangan tidak ditemukan.']);
        }

        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) ?: 'png';
        if (!in_array($extension, ['png', 'jpg', 'jpeg'], true)) {
            $extension = 'png';
        }

        $targetPath = trim($directory, '/') . '/' . now()->format('Y/m') . '/' . Str::uuid() . '.' . ($extension === 'jpeg' ? 'jpg' : $extension);
        $binary = Storage::disk('public')->get($sourcePath);
        Storage::disk('public')->put($targetPath, $binary);

        $mime = Storage::disk('public')->mimeType($targetPath) ?: 'image/' . ($extension === 'jpg' || $extension === 'jpeg' ? 'jpeg' : 'png');

        return [
            'path' => $targetPath,
            'mime' => $mime,
            'size' => strlen($binary),
        ];
    }

    public function toDataUri($path)
    {
        if (!$path || !Storage::disk('public')->exists($path)) {
            return null;
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';
        $binary = Storage::disk('public')->get($path);

        return 'data:' . $mime . ';base64,' . base64_encode($this->boldenImageBinary($binary, $mime));
    }

    public function dataUriFromPublicPath($path)
    {
        if (!$path || !is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';
        $binary = file_get_contents($path);

        return 'data:' . $mime . ';base64,' . base64_encode($this->boldenImageBinary($binary, $mime));
    }

    protected function boldenImageBinary($binary, $mime)
    {
        if (!function_exists('imagecreatefromstring') || !$binary) {
            return $binary;
        }

        $source = @imagecreatefromstring($binary);
        if (!$source) {
            return $binary;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $canvas = imagecreatetruecolor($width, $height);

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
        imagealphablending($canvas, true);

        foreach ([[0, 0], [1, 0], [0, 1], [1, 1], [-1, 0], [0, -1]] as $offset) {
            imagecopy($canvas, $source, $offset[0], $offset[1], 0, 0, $width, $height);
        }

        ob_start();
        if (stripos($mime, 'jpeg') !== false || stripos($mime, 'jpg') !== false) {
            imagejpeg($canvas, null, 92);
        } else {
            imagepng($canvas);
        }
        $output = ob_get_clean();

        imagedestroy($source);
        imagedestroy($canvas);

        return $output ?: $binary;
    }
}
