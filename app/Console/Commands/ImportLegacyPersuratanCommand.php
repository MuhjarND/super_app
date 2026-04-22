<?php

namespace App\Console\Commands;

use App\Services\LegacyPersuratan\LegacyPersuratanImportService;
use App\Services\LegacyPersuratan\LegacySqlDumpLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ImportLegacyPersuratanCommand extends Command
{
    protected $signature = 'persuratan:import-legacy 
                            {--connection=legacy_persuratan : Nama koneksi legacy}
                            {--dump= : Path dump SQL legacy}
                            {--legacy-db= : Nama database sementara untuk dump legacy}
                            {--legacy-public= : Path folder public aplikasi lama}
                            {--skip-load-dump : Lewati proses load dump SQL}
                            {--skip-files : Lewati copy file lampiran}
                            {--keep-legacy-db : Jangan drop/reload database legacy sementara}';

    protected $description = 'Import data persuratan dari aplikasi lama ke modul persuratan aplikasi baru';

    public function handle(LegacySqlDumpLoader $dumpLoader, LegacyPersuratanImportService $importService)
    {
        $connection = $this->option('connection');
        $dumpPath = $this->option('dump') ?: base_path('migrasi/surat/db/simisol.sql');
        $legacyDbName = $this->option('legacy-db') ?: env('LEGACY_PERSURATAN_DB_DATABASE', 'legacy_persuratan_import');
        $legacyPublicPath = $this->option('legacy-public') ?: base_path('migrasi/surat/public');

        $this->configureLegacyConnection($connection, $legacyDbName);

        if (!$this->option('skip-load-dump')) {
            $this->info('Memuat dump SQL legacy ke database sementara: ' . $legacyDbName);

            $dumpLoader->loadIntoDatabase(
                $dumpPath,
                Config::get('database.connections.mysql'),
                $legacyDbName,
                !$this->option('keep-legacy-db')
            );
        }

        DB::purge($connection);

        $this->info('Mengimpor data persuratan legacy...');
        $summary = $importService->import(
            $connection,
            $legacyPublicPath,
            $this->option('skip-files')
        );

        $this->table(
            ['Item', 'Nilai'],
            collect($summary)->map(function ($value, $key) {
                return [$key, $value];
            })->values()->all()
        );

        $this->info('Import persuratan legacy selesai.');

        return 0;
    }

    protected function configureLegacyConnection($connection, $legacyDbName)
    {
        $base = Config::get('database.connections.' . $connection, []);

        Config::set('database.connections.' . $connection, array_merge([
            'driver' => 'mysql',
            'host' => env('LEGACY_PERSURATAN_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('LEGACY_PERSURATAN_DB_PORT', env('DB_PORT', '3306')),
            'database' => $legacyDbName,
            'username' => env('LEGACY_PERSURATAN_DB_USERNAME', env('DB_USERNAME', 'forge')),
            'password' => env('LEGACY_PERSURATAN_DB_PASSWORD', env('DB_PASSWORD', '')),
            'unix_socket' => env('LEGACY_PERSURATAN_DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
        ], $base, ['database' => $legacyDbName]));
    }
}
