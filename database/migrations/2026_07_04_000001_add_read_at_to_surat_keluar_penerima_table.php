<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddReadAtToSuratKeluarPenerimaTable extends Migration
{
    public function up()
    {
        Schema::table('surat_keluar_penerima', function (Blueprint $table) {
            if (!Schema::hasColumn('surat_keluar_penerima', 'read_at')) {
                $table->dateTime('read_at')->nullable()->after('user_id');
            }
        });

        DB::table('surat_keluar_penerima')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function down()
    {
        Schema::table('surat_keluar_penerima', function (Blueprint $table) {
            if (Schema::hasColumn('surat_keluar_penerima', 'read_at')) {
                $table->dropColumn('read_at');
            }
        });
    }
}
