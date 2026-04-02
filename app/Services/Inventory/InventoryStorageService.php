<?php

namespace App\Services\Inventory;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class InventoryStorageService
{
    const PHOTO_DIR = 'inventory/photos';
    const ATTACHMENT_DIR = 'inventory/attachments';

    public function storePhoto(UploadedFile $file)
    {
        return [
            'path' => $file->store(self::PHOTO_DIR, 'public'),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ];
    }

    public function storeAttachment(UploadedFile $file)
    {
        return [
            'path' => $file->store(self::ATTACHMENT_DIR, 'public'),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ];
    }

    public function replacePublicFile($oldPath, UploadedFile $file, $type = 'attachment')
    {
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $type === 'photo' ? $this->storePhoto($file) : $this->storeAttachment($file);
    }

    public function delete($path)
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    public function absolutePath($path)
    {
        if (!$path || !Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->path($path);
    }
}
