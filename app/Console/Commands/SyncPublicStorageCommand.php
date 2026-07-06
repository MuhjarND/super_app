<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SyncPublicStorageCommand extends Command
{
    protected $signature = 'papeda:sync-public-storage {--force : Timpa file tujuan jika sudah ada}';

    protected $description = 'Salin file public lama dari storage/app/public ke folder fisik public/storage tanpa symlink.';

    public function handle()
    {
        $source = storage_path('app/public');
        $target = public_path('storage');
        $force = (bool) $this->option('force');

        if (!File::isDirectory($source)) {
            $this->warn('Folder sumber tidak ditemukan: ' . $source);
            return self::SUCCESS;
        }

        if (is_link($target)) {
            $this->warn('public/storage masih berupa symlink. Hapus symlink itu di hosting jika ingin memakai folder fisik.');
        }

        File::ensureDirectoryExists($target);

        $copied = 0;
        $skipped = 0;

        foreach (File::allFiles($source) as $file) {
            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            $destination = $target . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            File::ensureDirectoryExists(dirname($destination));

            if (!$force && File::exists($destination)) {
                $skipped++;
                continue;
            }

            File::copy($file->getPathname(), $destination);
            $copied++;
        }

        $this->info('Sinkron selesai.');
        $this->line('Sumber : ' . $source);
        $this->line('Tujuan : ' . $target);
        $this->line('Disalin: ' . $copied);
        $this->line('Dilewati: ' . $skipped);

        return self::SUCCESS;
    }
}
