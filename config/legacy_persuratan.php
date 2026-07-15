<?php

return [
    'connection' => env('LEGACY_PERSURATAN_DB_CONNECTION', 'legacy_persuratan'),
    'database' => env('LEGACY_PERSURATAN_SYNC_DB_DATABASE', 'simisol'),
    'public_path' => env(
        'LEGACY_PERSURATAN_SYNC_PUBLIC_PATH',
        dirname(base_path()) . DIRECTORY_SEPARATOR . 'surat' . DIRECTORY_SEPARATOR . 'public'
    ),
    'source_url' => env('LEGACY_PERSURATAN_SYNC_SOURCE_URL', 'https://simisol.pta-papuabarat.go.id'),
    'remote_file_connect_timeout' => env('LEGACY_PERSURATAN_REMOTE_CONNECT_TIMEOUT', 10),
    'remote_file_timeout' => env('LEGACY_PERSURATAN_REMOTE_TIMEOUT', 90),
    'remote_file_attempts' => env('LEGACY_PERSURATAN_REMOTE_ATTEMPTS', 2),
    'remote_file_max_bytes' => env('LEGACY_PERSURATAN_REMOTE_MAX_BYTES', 26214400),
];
