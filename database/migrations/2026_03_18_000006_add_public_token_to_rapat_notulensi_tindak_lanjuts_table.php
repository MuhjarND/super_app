<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddPublicTokenToRapatNotulensiTindakLanjutsTable extends Migration
{
    public function up()
    {
        Schema::table('rapat_notulensi_tindak_lanjuts', function (Blueprint $table) {
            if (!Schema::hasColumn('rapat_notulensi_tindak_lanjuts', 'public_token')) {
                $table->string('public_token', 64)->nullable()->after('user_id');
            }
        });

        DB::table('rapat_notulensi_tindak_lanjuts')
            ->whereNull('public_token')
            ->orderBy('id')
            ->get()
            ->each(function ($row) {
                DB::table('rapat_notulensi_tindak_lanjuts')
                    ->where('id', $row->id)
                    ->update(['public_token' => (string) Str::uuid()]);
            });

        Schema::table('rapat_notulensi_tindak_lanjuts', function (Blueprint $table) {
            if (!$this->indexExists('rapat_notulensi_tindak_lanjuts', 'rntl_public_token_unique')) {
                $table->unique('public_token', 'rntl_public_token_unique');
            }
        });
    }

    public function down()
    {
        Schema::table('rapat_notulensi_tindak_lanjuts', function (Blueprint $table) {
            if ($this->indexExists('rapat_notulensi_tindak_lanjuts', 'rntl_public_token_unique')) {
                $table->dropUnique('rntl_public_token_unique');
            }
            if (Schema::hasColumn('rapat_notulensi_tindak_lanjuts', 'public_token')) {
                $table->dropColumn('public_token');
            }
        });
    }

    protected function indexExists($table, $indexName)
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
}
