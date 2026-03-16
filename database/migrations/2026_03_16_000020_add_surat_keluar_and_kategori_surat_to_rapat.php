<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSuratKeluarAndKategoriSuratToRapat extends Migration
{
    public function up()
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->unsignedBigInteger('rapat_id')->nullable()->unique()->after('created_by');
            $table->foreign('rapat_id')->references('id')->on('rapats')->onDelete('set null');
        });

        Schema::table('rapats', function (Blueprint $table) {
            $table->unsignedBigInteger('kategori_surat_kode_id')->nullable()->after('kategori_rapat_id');
            $table->foreign('kategori_surat_kode_id')->references('id')->on('klasifikasi_kodes')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->dropForeign(['rapat_id']);
            $table->dropColumn('rapat_id');
        });

        Schema::table('rapats', function (Blueprint $table) {
            $table->dropForeign(['kategori_surat_kode_id']);
            $table->dropColumn('kategori_surat_kode_id');
        });
    }
}
