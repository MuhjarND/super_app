<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKlasifikasiKodesTable extends Migration
{
    public function up()
    {
        Schema::create('klasifikasi_kodes', function (Blueprint $table) {
            $table->id();
            $table->string('kode');
            $table->string('nama');
            $table->enum('tipe', ['klasifikasi', 'fungsi', 'kegiatan', 'transaksi']);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('klasifikasi_kodes')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('klasifikasi_kodes');
    }
}
