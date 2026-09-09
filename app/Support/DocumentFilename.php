<?php

namespace App\Support;

use Illuminate\Support\Str;

class DocumentFilename
{
    public static function fromLetter($number, $subject, $extension = 'pdf')
    {
        return static::fromTitle(static::letterTitle($number, $subject), $extension);
    }

    public static function letterTitle($number, $subject)
    {
        $number = trim((string) $number);
        $subject = trim(preg_replace('/\s+/u', ' ', (string) $subject)) ?: 'Tanpa Perihal';
        $leadingNumber = preg_split('#[\\/]#', $number, 2)[0] ?? '';
        $leadingNumber = trim($leadingNumber) ?: 'Tanpa Nomor';

        return $leadingNumber . ' - ' . $subject;
    }

    public static function fromTitle($title, $extension = 'pdf')
    {
        $title = trim(preg_replace('/\s+/u', ' ', (string) $title)) ?: 'Dokumen';
        $extension = strtolower(trim((string) $extension, ". \t\n\r\0\x0B")) ?: 'pdf';

        $baseName = Str::ascii($title);
        $baseName = preg_replace('/[<>:"\/\\|?*\x00-\x1F]+/', '-', $baseName);
        $baseName = preg_replace('/\s*-\s*-+\s*/', ' - ', $baseName);
        $baseName = trim(preg_replace('/\s+/', ' ', $baseName), " .-");
        $baseName = Str::limit($baseName ?: 'Dokumen', 180, '');

        return $baseName . '.' . preg_replace('/[^a-z0-9]+/', '', $extension);
    }
}
