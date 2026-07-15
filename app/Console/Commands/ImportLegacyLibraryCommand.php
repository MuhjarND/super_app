<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ImportLegacyLibraryCommand extends Command
{
    protected $signature = 'library:import-legacy
        {--connection=library_legacy : Koneksi database aplikasi perpustakaan lama}
        {--storage-path= : Lokasi storage/app/public aplikasi lama}
        {--user-id= : User SIMANTAP fallback untuk transaksi petugas lama}';

    protected $description = 'Import seluruh data dan berkas aplikasi perpustakaan lama ke modul Perpustakaan SIMANTAP';

    protected $tables = [
        'categories' => 'library_categories',
        'shelves' => 'library_shelves',
        'books' => 'library_books',
        'book_copies' => 'library_book_copies',
        'members' => 'library_members',
        'loans' => 'library_loans',
        'loan_items' => 'library_loan_items',
        'returns' => 'library_returns',
        'fines' => 'library_fines',
        'settings' => 'library_settings',
    ];

    public function handle()
    {
        $connection = $this->option('connection');
        $this->validateTables($connection);

        $userMap = $this->buildUserMap($connection);
        $fallbackUserId = $this->resolveFallbackUserId();

        DB::transaction(function () use ($connection, $userMap, $fallbackUserId) {
            foreach ($this->tables as $source => $target) {
                $rows = DB::connection($connection)->table($source)->orderBy('id')->get();

                foreach ($rows as $row) {
                    $data = (array) $row;
                    if (in_array($source, ['loans', 'returns'], true)) {
                        $data['user_id'] = $userMap[$data['user_id']] ?? $fallbackUserId;
                    }
                    if ($source === 'fines' && !empty($data['paid_by'])) {
                        $data['paid_by'] = $userMap[$data['paid_by']] ?? $fallbackUserId;
                    }

                    DB::table($target)->updateOrInsert(['id' => $data['id']], $data);
                }

                $this->line(sprintf('%-20s %d data', $target, $rows->count()));
            }
        });

        $this->copyLegacyFiles();
        $this->info('Import perpustakaan lama selesai.');

        return 0;
    }

    protected function validateTables($connection)
    {
        foreach (array_keys($this->tables) as $table) {
            if (!Schema::connection($connection)->hasTable($table)) {
                throw new \RuntimeException("Tabel legacy {$table} tidak ditemukan.");
            }
        }

        foreach (array_values($this->tables) as $table) {
            if (!Schema::hasTable($table)) {
                throw new \RuntimeException("Tabel target {$table} belum tersedia. Jalankan php artisan migrate terlebih dahulu.");
            }
        }
    }

    protected function buildUserMap($connection)
    {
        if (!Schema::connection($connection)->hasTable('users')) {
            return [];
        }

        $map = [];
        foreach (DB::connection($connection)->table('users')->get() as $legacyUser) {
            $user = User::where('email', $legacyUser->email)->first();
            if ($user) {
                $map[$legacyUser->id] = $user->id;
            }
        }

        return $map;
    }

    protected function resolveFallbackUserId()
    {
        $requested = $this->option('user-id');
        if ($requested && User::whereKey($requested)->exists()) {
            return (int) $requested;
        }

        $user = User::whereHas('roles', function ($query) {
            $query->where('name', 'super_admin');
        })->first() ?: User::first();

        if (!$user) {
            throw new \RuntimeException('Tidak ada user SIMANTAP untuk dipakai sebagai petugas transaksi legacy.');
        }

        return $user->id;
    }

    protected function copyLegacyFiles()
    {
        $sourceRoot = $this->option('storage-path')
            ?: env('LIBRARY_LEGACY_STORAGE_PATH', 'C:\\xampp\\htdocs\\perpus\\storage\\app\\public');

        if (!is_dir($sourceRoot)) {
            $this->warn("Folder berkas legacy tidak ditemukan: {$sourceRoot}");
            return;
        }

        foreach (['books', 'members'] as $directory) {
            $source = rtrim($sourceRoot, '\\/') . DIRECTORY_SEPARATOR . $directory;
            if (!is_dir($source)) {
                continue;
            }

            $target = Storage::disk('public')->path('library/' . $directory);
            File::ensureDirectoryExists($target);
            File::copyDirectory($source, $target);
            $this->line("Berkas {$directory} disalin.");
        }

        DB::table('library_books')->whereNotNull('cover')->orderBy('id')->get()->each(function ($book) {
            if (strpos($book->cover, 'library/') !== 0) {
                DB::table('library_books')->where('id', $book->id)->update(['cover' => 'library/' . ltrim($book->cover, '\\/')]);
            }
        });

        DB::table('library_members')->whereNotNull('photo')->orderBy('id')->get()->each(function ($member) {
            if (strpos($member->photo, 'library/') !== 0) {
                DB::table('library_members')->where('id', $member->id)->update(['photo' => 'library/' . ltrim($member->photo, '\\/')]);
            }
        });
    }
}
