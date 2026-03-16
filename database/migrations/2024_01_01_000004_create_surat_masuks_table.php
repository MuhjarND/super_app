<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuratMasuksTable extends Migration
{
    public function up()
    {
        Schema::create('surat_masuks', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat');
            $table->enum('opsi_pengirim', ['mahkamah_agung', 'non_mahkamah_agung']);
            $table->unsignedBigInteger('klasifikasi_kode_id')->nullable();
            $table->string('pengirim');
            $table->text('perihal');
            $table->date('tanggal_surat');
            $table->enum('sifat', ['biasa', 'rahasia', 'sangat_rahasia'])->default('biasa');
            $table->string('file_path');
            $table->enum('status', ['baru', 'didisposisi', 'selesai'])->default('baru');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('klasifikasi_kode_id')->references('id')->on('klasifikasi_kodes')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('surat_masuks');
    }
}
