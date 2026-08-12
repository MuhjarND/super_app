<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSifatSuratToRapats extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('rapats') || Schema::hasColumn('rapats', 'sifat_surat')) {
            return;
        }

        Schema::table('rapats', function (Blueprint $table) {
            $table->string('sifat_surat', 30)->default('biasa')->after('nomenklatur_jabatan');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('rapats') || !Schema::hasColumn('rapats', 'sifat_surat')) {
            return;
        }

        Schema::table('rapats', function (Blueprint $table) {
            $table->dropColumn('sifat_surat');
        });
    }
}
