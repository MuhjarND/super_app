<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSuratMasukPageIndexes extends Migration
{
    public function up()
    {
        $this->addIndexIfMissing('surat_masuks', ['created_at'], 'sm_created_idx');
        $this->addIndexIfMissing('surat_masuks', ['status', 'created_at'], 'sm_status_created_idx');
        $this->addIndexIfMissing('surat_masuks', ['created_by', 'created_at'], 'sm_created_by_created_idx');
    }

    public function down()
    {
        $this->dropIndexIfExists('surat_masuks', 'sm_created_idx');
        $this->dropIndexIfExists('surat_masuks', 'sm_status_created_idx');
        $this->dropIndexIfExists('surat_masuks', 'sm_created_by_created_idx');
    }

    protected function addIndexIfMissing($table, array $columns, $indexName)
    {
        if (!Schema::hasTable($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
            $blueprint->index($columns, $indexName);
        });
    }

    protected function dropIndexIfExists($table, $indexName)
    {
        if (!Schema::hasTable($table) || !$this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
            $blueprint->dropIndex($indexName);
        });
    }

    protected function indexExists($table, $indexName)
    {
        $database = DB::getDatabaseName();
        $result = DB::select(
            'SELECT COUNT(1) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return (int) ($result[0]->aggregate ?? 0) > 0;
    }
}
