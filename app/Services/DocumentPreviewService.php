<?php

namespace App\Services;

use App\Support\DocumentFilename;
use Illuminate\Support\Facades\Storage;

class DocumentPreviewService
{
    public function streamPublicFile($relativePath, $title = 'Preview Berkas', $downloadUrl = null)
    {
        abort_unless($relativePath && Storage::disk('public')->exists($relativePath), 404, 'File tidak ditemukan.');

        $path = Storage::disk('public')->path($relativePath);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $filename = DocumentFilename::fromTitle($title, $extension);

        if (in_array($extension, ['doc', 'docx'], true)) {
            return response()->file($path, [
                'Content-Type' => $this->wordMimeType($extension),
                'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
        ]);
    }

    protected function wordMimeType($extension)
    {
        if ($extension === 'doc') {
            return 'application/msword';
        }

        return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }
}
