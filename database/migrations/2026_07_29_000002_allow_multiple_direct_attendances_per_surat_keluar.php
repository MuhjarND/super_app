<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AllowMultipleDirectAttendancesPerSuratKeluar extends Migration
{
    const TABLE = 'rapats';
    const UNIQUE_INDEX = 'rapat_attendance_surat_unique';
    const LOOKUP_INDEX = 'rapat_attendance_surat_idx';

    public function up()
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, 'attendance_surat_keluar_id')) {
            return;
        }

        if (!$this->indexExists(self::TABLE, self::LOOKUP_INDEX)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->index('attendance_surat_keluar_id', self::LOOKUP_INDEX);
            });
        }

        if ($this->indexExists(self::TABLE, self::UNIQUE_INDEX)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropUnique(self::UNIQUE_INDEX);
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable(self::TABLE) || !Schema::hasColumn(self::TABLE, 'attendance_surat_keluar_id')) {
            return;
        }

        $hasDuplicates = DB::table(self::TABLE)
            ->whereNotNull('attendance_surat_keluar_id')
            ->select('attendance_surat_keluar_id')
            ->groupBy('attendance_surat_keluar_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            throw new RuntimeException(
                'Rollback tidak dapat dilakukan karena satu Surat Keluar sudah memiliki lebih dari satu absensi.'
            );
        }

        if (!$this->indexExists(self::TABLE, self::UNIQUE_INDEX)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->unique('attendance_surat_keluar_id', self::UNIQUE_INDEX);
            });
        }

        if ($this->indexExists(self::TABLE, self::LOOKUP_INDEX)) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropIndex(self::LOOKUP_INDEX);
            });
        }
    }

    protected function indexExists($table, $indexName)
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('" . str_replace("'", "''", $table) . "')");

            return collect($indexes)->contains(function ($index) use ($indexName) {
                return ($index->name ?? null) === $indexName;
            });
        }

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->where('indexname', $indexName)
                ->exists();
        }

        if ($driver === 'sqlsrv') {
            $result = DB::select(
                'SELECT COUNT(1) AS aggregate
                 FROM sys.indexes
                 WHERE object_id = OBJECT_ID(?) AND name = ?',
                [$table, $indexName]
            );

            return (int) ($result[0]->aggregate ?? 0) > 0;
        }

        $result = DB::select(
            'SELECT COUNT(1) AS aggregate
             FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$connection->getDatabaseName(), $table, $indexName]
        );

        return (int) ($result[0]->aggregate ?? 0) > 0;
    }
}
