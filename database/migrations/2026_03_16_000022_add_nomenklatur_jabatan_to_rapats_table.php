<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddNomenklaturJabatanToRapatsTable extends Migration
{
    public function up()
    {
        Schema::table('rapats', function (Blueprint $table) {
            $table->enum('nomenklatur_jabatan', ['ketua', 'wakil_ketua', 'sekretaris', 'panitera'])
                ->default('sekretaris')
                ->after('kategori_surat_kode_id');
        });

        DB::statement("
            UPDATE rapats r
            LEFT JOIN surat_keluars sk ON sk.rapat_id = r.id
            SET r.nomenklatur_jabatan = COALESCE(sk.nomenklatur_jabatan, 'sekretaris')
        ");
    }

    public function down()
    {
        Schema::table('rapats', function (Blueprint $table) {
            $table->dropColumn('nomenklatur_jabatan');
        });
    }
}
