<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ExpandSuratKeluarNomenklaturEnum extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE surat_keluars MODIFY nomenklatur_jabatan ENUM('ketua','wakil_ketua','sekretaris','panitera') NOT NULL");
    }

    public function down()
    {
        DB::statement("UPDATE surat_keluars SET nomenklatur_jabatan = 'ketua' WHERE nomenklatur_jabatan = 'wakil_ketua'");
        DB::statement("ALTER TABLE surat_keluars MODIFY nomenklatur_jabatan ENUM('ketua','sekretaris','panitera') NOT NULL");
    }
}
