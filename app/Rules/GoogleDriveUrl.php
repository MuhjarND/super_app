<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class GoogleDriveUrl implements Rule
{
    public function passes($attribute, $value)
    {
        if (!is_string($value) || strlen($value) > 2048 || preg_match('/[\s\\\\]/', $value)) {
            return false;
        }

        $parts = parse_url($value);
        if (!$parts || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return false;
        }

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        if ($host === 'drive.google.com') {
            if (preg_match('~^/(?:file/(?:u/\d+/)?d/|(?:drive/(?:u/\d+/)?)?folders/)[A-Za-z0-9_-]+(?:/|$)~', $path)) {
                return true;
            }
            parse_str($parts['query'] ?? '', $query);
            return in_array($path, ['/open', '/file', '/uc'], true)
                && isset($query['id']) && is_string($query['id'])
                && preg_match('/^[A-Za-z0-9_-]+$/', $query['id']) === 1;
        }

        return $host === 'docs.google.com'
            && preg_match('~^/(?:document|spreadsheets|presentation|drawings|forms)/(?:u/\d+/)?d/(?:e/)?[A-Za-z0-9_-]+(?:/|$)~', $path) === 1;
    }

    public function message()
    {
        return 'Masukkan tautan HTTPS file atau folder Google Drive yang valid (drive.google.com atau docs.google.com).';
    }
}
