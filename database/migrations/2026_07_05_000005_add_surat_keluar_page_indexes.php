<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSuratKeluarPageIndexes extends Migration
{
    public function up()
    {
        $this->addIndexIfMissing('surat_keluar_penerima', ['user_id', 'read_at', 'surat_keluar_id'], 'skp_user_read_surat_idx');
        $this->addIndexIfMissing('surat_keluar_penerima', ['surat_keluar_id', 'user_id'], 'skp_surat_user_idx');
        $this->addIndexIfMissing('surat_keluars', ['created_by', 'created_at'], 'sk_created_by_created_idx');
        $this->addIndexIfMissing('surat_keluars', ['status', 'created_at'], 'sk_status_created_idx');
    }

    public function down()
    {
        $this->dropIndexIfExists('surat_keluar_penerima', 'skp_user_read_surat_idx');
        $this->dropIndexIfExists('surat_keluar_penerima', 'skp_surat_user_idx');
        $this->dropIndexIfExists('surat_keluars', 'sk_created_by_created_idx');
        $this->dropIndexIfExists('surat_keluars', 'sk_status_created_idx');
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
