<?php

namespace App\Services\LegacyPersuratan;

use App\AppSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class LegacyPersuratanSyncService
{
    protected $importService;
    protected $remoteFileSyncService;

    public function __construct(
        LegacyPersuratanImportService $importService,
        LegacyPersuratanRemoteFileSyncService $remoteFileSyncService
    )
    {
        $this->importService = $importService;
        $this->remoteFileSyncService = $remoteFileSyncService;
    }

    public function sync()
    {
        $connection = config('legacy_persuratan.connection', 'legacy_persuratan');
        $database = config('legacy_persuratan.database', 'simisol');
        $publicPath = config('legacy_persuratan.public_path');

        if (!$publicPath || !is_dir($publicPath)) {
            throw new \RuntimeException('Folder public aplikasi SIMISOL tidak ditemukan.');
        }

        $connectionConfig = Config::get('database.connections.' . $connection);
        if (!$connectionConfig) {
            throw new \RuntimeException('Koneksi database SIMISOL belum dikonfigurasi.');
        }

        Config::set(
            'database.connections.' . $connection,
            array_merge($connectionConfig, ['database' => $database])
        );

        DB::purge($connection);
        DB::connection($connection)->getPdo();

        try {
            $summary = $this->importService->import($connection, $publicPath, false);
            $summary = array_merge($summary, $this->remoteFileSyncService->sync($connection));
        } finally {
            DB::disconnect($connection);
        }

        $syncedAt = now('Asia/Jayapura');
        AppSetting::putValue('legacy_persuratan_last_synced_at', $syncedAt->toDateTimeString());
        AppSetting::putValue('legacy_persuratan_last_sync_summary', json_encode($summary));

        return [
            'summary' => $summary,
            'synced_at' => $syncedAt,
            'source_url' => config('legacy_persuratan.source_url'),
        ];
    }
}
