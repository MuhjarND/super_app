<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKategoriSuratIdToSuratTables extends Migration
{
    public function up()
    {
        Schema::table('surat_masuks', function (Blueprint $table) {
            $table->unsignedBigInteger('kategori_surat_id')->nullable()->after('klasifikasi_kode_id');
            $table->foreign('kategori_surat_id')->references('id')->on('kategori_surats')->onDelete('set null');
        });

        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->unsignedBigInteger('kategori_surat_id')->nullable()->after('klasifikasi_kode_id');
            $table->foreign('kategori_surat_id')->references('id')->on('kategori_surats')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('surat_keluars', function (Blueprint $table) {
            $table->dropForeign(['kategori_surat_id']);
            $table->dropColumn('kategori_surat_id');
        });

        Schema::table('surat_masuks', function (Blueprint $table) {
            $table->dropForeign(['kategori_surat_id']);
            $table->dropColumn('kategori_surat_id');
        });
    }
}
