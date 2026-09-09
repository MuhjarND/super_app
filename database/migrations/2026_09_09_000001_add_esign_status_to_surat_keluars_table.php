<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddEsignStatusToSuratKeluarsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('surat_keluars', 'status')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE surat_keluars MODIFY status ENUM('draft', 'e-sign', 'lengkap') NOT NULL DEFAULT 'draft'");
        }
    }

    public function down()
    {
        if (!Schema::hasColumn('surat_keluars', 'status')) {
            return;
        }

        DB::table('surat_keluars')->where('status', 'e-sign')->update(['status' => 'draft']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE surat_keluars MODIFY status ENUM('draft', 'lengkap') NOT NULL DEFAULT 'draft'");
        }
    }
}
