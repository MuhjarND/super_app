<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDokumentasiLinkToAgendaPimpinansTable extends Migration
{
    public function up()
    {
        Schema::table('agenda_pimpinans', function (Blueprint $table) {
            if (!Schema::hasColumn('agenda_pimpinans', 'dokumentasi_link')) {
                $table->text('dokumentasi_link')->nullable()->after('lampiran_link');
            }
        });
    }

    public function down()
    {
        Schema::table('agenda_pimpinans', function (Blueprint $table) {
            if (Schema::hasColumn('agenda_pimpinans', 'dokumentasi_link')) {
                $table->dropColumn('dokumentasi_link');
            }
        });
    }
}
