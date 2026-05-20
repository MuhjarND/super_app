<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSuratMasukToAgendaPimpinansTable extends Migration
{
    public function up()
    {
        Schema::table('agenda_pimpinans', function (Blueprint $table) {
            if (!Schema::hasColumn('agenda_pimpinans', 'surat_masuk_id')) {
                $table->unsignedBigInteger('surat_masuk_id')->nullable()->after('id');
                $table->unique('surat_masuk_id', 'agenda_pimpinans_surat_masuk_id_unique');
                $table->foreign('surat_masuk_id', 'agenda_pimpinans_surat_masuk_id_foreign')
                    ->references('id')
                    ->on('surat_masuks')
                    ->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('agenda_pimpinans', function (Blueprint $table) {
            if (Schema::hasColumn('agenda_pimpinans', 'surat_masuk_id')) {
                $table->dropForeign('agenda_pimpinans_surat_masuk_id_foreign');
                $table->dropUnique('agenda_pimpinans_surat_masuk_id_unique');
                $table->dropColumn('surat_masuk_id');
            }
        });
    }
}
