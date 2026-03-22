<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDokumentasiFilesToRapatNotulensisTable extends Migration
{
    public function up()
    {
        Schema::table('rapat_notulensis', function (Blueprint $table) {
            $table->longText('dokumentasi_files')->nullable()->after('dokumentasi_rapat');
        });
    }

    public function down()
    {
        Schema::table('rapat_notulensis', function (Blueprint $table) {
            $table->dropColumn('dokumentasi_files');
        });
    }
}
