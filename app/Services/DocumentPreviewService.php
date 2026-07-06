<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class DocumentPreviewService
{
    public function streamPublicFile($relativePath, $title = 'Preview Berkas', $downloadUrl = null)
    {
        abort_unless($relativePath && Storage::disk('public')->exists($relativePath), 404, 'File tidak ditemukan.');

        $path = Storage::disk('public')->path($relativePath);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['doc', 'docx'], true)) {
            return response()->file($path, [
                'Content-Type' => $this->wordMimeType($extension),
                'Content-Disposition' => 'inline; filename="' . addslashes(basename($path)) . '"',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return response()->file($path);
    }

    protected function wordMimeType($extension)
    {
        if ($extension === 'doc') {
            return 'application/msword';
        }

        return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }
}
