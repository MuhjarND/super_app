<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddRekomendasiItemsToRapatNotulensisAndFollowups extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('rapat_notulensis', 'rekomendasi_items')) {
            Schema::table('rapat_notulensis', function (Blueprint $table) {
                $table->longText('rekomendasi_items')->nullable()->after('rekomendasi');
            });
        }

        Schema::table('rapat_notulensi_tindak_lanjuts', function (Blueprint $table) {
            if (!Schema::hasColumn('rapat_notulensi_tindak_lanjuts', 'item_index')) {
                $table->unsignedInteger('item_index')->default(0)->after('rapat_notulensi_id');
            }
        });

        if (!$this->indexExists('rapat_notulensi_tindak_lanjuts', 'rntl_rapat_notulensi_id_idx')) {
            Schema::table('rapat_notulensi_tindak_lanjuts', function (Blueprint $table) {
                $table->index('rapat_notulensi_id', 'rntl_rapat_notulensi_id_idx');
            });
        }

        if ($this->indexExists('rapat_notulensi_tindak_lanjuts', 'rapat_notulensi_user_unique')) {
            Schema::table('rapat_notulensi_tindak_lanjuts', function (Blueprint $table) {
                $table->dropUnique('rapat_notulensi_user_unique');
            });
        }

        if (!$this->indexExists('rapat_notulensi_tindak_lanjuts', 'rapat_notulensi_item_user_unique')) {
            Schema::table('rapat_notulensi_tindak_lanjuts', function (Blueprint $table) {
                $table->unique(['rapat_notulensi_id', 'item_index', 'user_id'], 'rapat_notulensi_item_user_unique');
            });
        }
    }

    public function down()
    {
        if ($this->indexExists('rapat_notulensi_tindak_lanjuts', 'rapat_notulensi_item_user_unique')) {
            Schema::table('rapat_notulensi_tindak_lanjuts', function (Blueprint $table) {
                $table->dropUnique('rapat_notulensi_item_user_unique');
            });
        }

        if (Schema::hasColumn('rapat_notulensi_tindak_lanjuts', 'item_index')) {
            Schema::table('rapat_notulensi_tindak_lanjuts', function (Blueprint $table) {
                $table->dropColumn('item_index');
            });
        }

        if (!$this->indexExists('rapat_notulensi_tindak_lanjuts', 'rapat_notulensi_user_unique')) {
            Schema::table('rapat_notulensi_tindak_lanjuts', function (Blueprint $table) {
                $table->unique(['rapat_notulensi_id', 'user_id'], 'rapat_notulensi_user_unique');
            });
        }

        if ($this->indexExists('rapat_notulensi_tindak_lanjuts', 'rntl_rapat_notulensi_id_idx')) {
            Schema::table('rapat_notulensi_tindak_lanjuts', function (Blueprint $table) {
                $table->dropIndex('rntl_rapat_notulensi_id_idx');
            });
        }

        if (Schema::hasColumn('rapat_notulensis', 'rekomendasi_items')) {
            Schema::table('rapat_notulensis', function (Blueprint $table) {
                $table->dropColumn('rekomendasi_items');
            });
        }
    }

    protected function indexExists($table, $indexName)
    {
        $database = config('database.connections.mysql.database');
        $result = DB::select(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return !empty($result) && (int) $result[0]->aggregate > 0;
    }
}
