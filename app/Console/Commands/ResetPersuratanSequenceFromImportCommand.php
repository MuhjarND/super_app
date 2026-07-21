<?php

namespace App\Console\Commands;

use App\AppSetting;
use App\SuratKeluar;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ResetPersuratanSequenceFromImportCommand extends Command
{
    protected $signature = 'persuratan:reset-sequence-from-import
                            {--force : Jalankan pembersihan data non-import}
                            {--no-backup : Lewati backup JSON sebelum pembersihan}';

    protected $description = 'Bersihkan data persuratan non-import dan jadikan nomor surat keluar import sebagai basis nomor otomatis berikutnya';

    public function handle()
    {
        try {
            if (!$this->canRun()) {
                return 1;
            }

            $stats = $this->collectStats();
            $this->table(['Item', 'Jumlah'], collect($stats)->map(function ($value, $key) {
                return [$key, $value];
            })->values()->all());

            if (!$this->option('force')) {
                $this->warn('Mode cek saja. Tambahkan --force untuk menghapus data non-import.');
                $this->line('Command eksekusi: php artisan persuratan:reset-sequence-from-import --force');
                return 0;
            }

            $backupPath = null;
            if (!$this->option('no-backup')) {
                $backupPath = $this->createBackup();
                $this->info('Backup dibuat: ' . $backupPath);
            }

            $deleted = DB::transaction(function () {
                $now = Carbon::now('Asia/Jayapura')->toDateTimeString();
                $currentYear = (int) Carbon::now('Asia/Jayapura')->year;

                $suratKeluarColumns = ['id', 'nomor_surat'];
                if (Schema::hasColumn('surat_keluars', 'rapat_id')) {
                    $suratKeluarColumns[] = 'rapat_id';
                }

                $suratKeluarRows = DB::table('surat_keluars')
                    ->whereNull('legacy_source_id')
                    ->select($suratKeluarColumns)
                    ->get();
                $suratKeluarIds = $suratKeluarRows->pluck('id')->all();
                $suratKeluarNomors = $suratKeluarRows->pluck('nomor_surat')->filter()->values()->all();
                $rapatIds = Schema::hasColumn('surat_keluars', 'rapat_id')
                    ? $suratKeluarRows->pluck('rapat_id')->filter()->values()->all()
                    : [];

                $suratMasukIds = DB::table('surat_masuks')
                    ->whereNull('legacy_source_id')
                    ->pluck('id')
                    ->all();

                if (!empty($rapatIds) && Schema::hasTable('rapats') && Schema::hasColumn('rapats', 'nomor_undangan')) {
                    DB::table('rapats')->whereIn('id', $rapatIds)->update([
                        'nomor_undangan' => null,
                        'updated_at' => now(),
                    ]);
                }

                if (!empty($suratKeluarNomors) && Schema::hasTable('leave_requests') && Schema::hasColumn('leave_requests', 'letter_number')) {
                    DB::table('leave_requests')->whereIn('letter_number', $suratKeluarNomors)->update([
                        'letter_number' => null,
                        'updated_at' => now(),
                    ]);
                }

                if (!empty($suratKeluarIds) && Schema::hasTable('pdf_verifications')) {
                    DB::table('pdf_verifications')
                        ->where('module', 'surat_keluar')
                        ->whereIn('document_id', array_map('strval', $suratKeluarIds))
                        ->delete();
                }

                if (!empty($suratMasukIds) && Schema::hasTable('agenda_pimpinans') && Schema::hasColumn('agenda_pimpinans', 'surat_masuk_id')) {
                    DB::table('agenda_pimpinans')->whereIn('surat_masuk_id', $suratMasukIds)->update([
                        'surat_masuk_id' => null,
                        'updated_at' => now(),
                    ]);
                }

                $deletedSuratKeluar = empty($suratKeluarIds) ? 0 : DB::table('surat_keluars')->whereIn('id', $suratKeluarIds)->delete();
                $deletedSuratMasuk = empty($suratMasukIds) ? 0 : DB::table('surat_masuks')->whereIn('id', $suratMasukIds)->delete();

                $legacyMax = (int) (SuratKeluar::whereNotNull('legacy_source_id')
                    ->where('tahun_surat', $currentYear)
                    ->max('nomor_urut') ?: 0);
                AppSetting::putValue('surat_keluar_sequence_started_at', $now);
                AppSetting::putValue('surat_keluar_sequence_base_legacy_max', $legacyMax);
                AppSetting::putValue('surat_keluar_sequence_base_year', $currentYear);

                return [
                    'surat_keluar' => $deletedSuratKeluar,
                    'surat_masuk' => $deletedSuratMasuk,
                    'tahun_sequence' => $currentYear,
                    'legacy_max' => $legacyMax,
                    'next_number' => $legacyMax + 1,
                    'sequence_started_at' => $now,
                ];
            });

            $this->table(['Item', 'Nilai'], collect($deleted)->map(function ($value, $key) {
                return [$key, $value];
            })->values()->all());

            if ($backupPath) {
                $this->line('Backup: ' . $backupPath);
            }

            $this->info('Reset persuratan selesai. Nomor surat baru akan mengikuti lanjutan data import.');

            return 0;
        } catch (\Throwable $exception) {
            $this->error('Gagal menjalankan reset persuratan: ' . $exception->getMessage());
            return 1;
        }
    }

    protected function canRun()
    {
        foreach (['surat_masuks', 'surat_keluars'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->error('Tabel tidak ditemukan: ' . $table);
                return false;
            }

            if (!Schema::hasColumn($table, 'legacy_source_id')) {
                $this->error('Kolom legacy_source_id tidak ditemukan pada tabel: ' . $table);
                return false;
            }
        }

        if (!Schema::hasTable('app_settings')) {
            $this->error('Tabel app_settings belum tersedia. Jalankan migrate terlebih dahulu.');
            return false;
        }

        return true;
    }

    protected function collectStats()
    {
        $currentYear = (int) Carbon::now('Asia/Jayapura')->year;

        return [
            'surat_masuk_total' => DB::table('surat_masuks')->count(),
            'surat_masuk_import' => DB::table('surat_masuks')->whereNotNull('legacy_source_id')->count(),
            'surat_masuk_non_import_akan_dihapus' => DB::table('surat_masuks')->whereNull('legacy_source_id')->count(),
            'surat_keluar_total' => DB::table('surat_keluars')->count(),
            'surat_keluar_import' => DB::table('surat_keluars')->whereNotNull('legacy_source_id')->count(),
            'surat_keluar_non_import_akan_dihapus' => DB::table('surat_keluars')->whereNull('legacy_source_id')->count(),
            'tahun_sequence' => $currentYear,
            'nomor_urut_tertinggi_import_tahun_ini' => (int) (DB::table('surat_keluars')
                ->whereNotNull('legacy_source_id')
                ->where('tahun_surat', $currentYear)
                ->max('nomor_urut') ?: 0),
            'nomor_urut_berikutnya_tahun_ini' => SuratKeluar::nextNomorUrut($currentYear),
        ];
    }

    protected function createBackup()
    {
        $suratKeluarIds = DB::table('surat_keluars')->whereNull('legacy_source_id')->pluck('id')->all();
        $suratMasukIds = DB::table('surat_masuks')->whereNull('legacy_source_id')->pluck('id')->all();

        $backup = [
            'created_at' => Carbon::now('Asia/Jayapura')->toDateTimeString(),
            'description' => 'Backup data persuratan non-import sebelum reset sequence import.',
            'surat_masuks' => $this->rows('surat_masuks', 'id', $suratMasukIds),
            'disposisis' => $this->rows('disposisis', 'surat_masuk_id', $suratMasukIds),
            'surat_masuk_reads' => $this->rows('surat_masuk_reads', 'surat_masuk_id', $suratMasukIds),
            'agenda_pimpinans_by_surat_masuk' => $this->rows('agenda_pimpinans', 'surat_masuk_id', $suratMasukIds),
            'surat_keluars' => $this->rows('surat_keluars', 'id', $suratKeluarIds),
            'surat_keluar_penerima' => $this->rows('surat_keluar_penerima', 'surat_keluar_id', $suratKeluarIds),
            'surat_keluar_approvals' => $this->rows('surat_keluar_approvals', 'surat_keluar_id', $suratKeluarIds),
            'surat_keluar_approval_histories' => $this->rows('surat_keluar_approval_histories', 'surat_keluar_id', $suratKeluarIds),
            'pdf_verifications_surat_keluar' => $this->pdfVerificationRows($suratKeluarIds),
        ];

        $directory = storage_path('app/backups/persuratan');
        if (!is_dir($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }

        $path = $directory . DIRECTORY_SEPARATOR . 'reset-sequence-' . Carbon::now('Asia/Jayapura')->format('Ymd-His') . '.json';
        File::put($path, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    protected function rows($table, $column, array $values)
    {
        if (empty($values) || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return [];
        }

        return DB::table($table)->whereIn($column, $values)->get()->map(function ($row) {
            return (array) $row;
        })->all();
    }

    protected function pdfVerificationRows(array $suratKeluarIds)
    {
        if (empty($suratKeluarIds) || !Schema::hasTable('pdf_verifications')) {
            return [];
        }

        return DB::table('pdf_verifications')
            ->where('module', 'surat_keluar')
            ->whereIn('document_id', array_map('strval', $suratKeluarIds))
            ->get()
            ->map(function ($row) {
                return (array) $row;
            })
            ->all();
    }
}
