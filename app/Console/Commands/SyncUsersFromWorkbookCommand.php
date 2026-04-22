<?php

namespace App\Console\Commands;

use App\Services\UserWorkbookSyncService;
use Illuminate\Console\Command;

class SyncUsersFromWorkbookCommand extends Command
{
    protected $signature = 'users:sync-workbook
                            {--path= : Path workbook user (.xlsx)}
                            {--reset-passwords : Reset password user existing sesuai workbook}';

    protected $description = 'Sinkronkan user dari workbook pegawai ke sistem baru berdasarkan hirarki dan jabatan.';

    public function handle(UserWorkbookSyncService $service)
    {
        $path = $this->option('path') ?: 'H:/My Drive/TASK HARIAN/DATA PEGAWAI/data pegawai.xlsx';

        $summary = $service->sync($path, (bool) $this->option('reset-passwords'));

        $this->table(
            ['Item', 'Nilai'],
            collect($summary)->map(function ($value, $key) {
                return [$key, $value];
            })->values()->all()
        );

        $this->info('Sinkronisasi user dari workbook selesai.');

        return 0;
    }
}
