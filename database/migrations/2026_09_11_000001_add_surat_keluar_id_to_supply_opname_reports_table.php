<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSuratKeluarIdToSupplyOpnameReportsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('supply_opname_reports') || Schema::hasColumn('supply_opname_reports', 'surat_keluar_id')) {
            return;
        }

        Schema::table('supply_opname_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('surat_keluar_id')->nullable()->after('opname_date');
            $table->unique('surat_keluar_id');
            $table->foreign('surat_keluar_id')
                ->references('id')
                ->on('surat_keluars')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('supply_opname_reports') || !Schema::hasColumn('supply_opname_reports', 'surat_keluar_id')) {
            return;
        }

        Schema::table('supply_opname_reports', function (Blueprint $table) {
            $table->dropForeign(['surat_keluar_id']);
            $table->dropUnique(['surat_keluar_id']);
            $table->dropColumn('surat_keluar_id');
        });
    }
}
