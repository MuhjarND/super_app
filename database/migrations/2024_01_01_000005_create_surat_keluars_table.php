<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuratKeluarsTable extends Migration
{
    public function up()
    {
        Schema::create('surat_keluars', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat')->unique();
            $table->integer('nomor_urut');
            $table->year('tahun_surat');
            $table->unsignedBigInteger('klasifikasi_kode_id');
            $table->unsignedBigInteger('kode_fungsi_id')->nullable();
            $table->unsignedBigInteger('kode_kegiatan_id')->nullable();
            $table->unsignedBigInteger('kode_transaksi_id')->nullable();
            $table->enum('nomenklatur_jabatan', ['ketua', 'sekretaris', 'panitera']);
            $table->enum('opsi_penerima', ['internal', 'external']);
            $table->string('penerima_external')->nullable();
            $table->text('perihal');
            $table->date('tanggal_surat');
            $table->boolean('has_lampiran')->default(false);
            $table->string('file_path')->nullable();
            $table->enum('status', ['draft', 'lengkap'])->default('draft');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('klasifikasi_kode_id')->references('id')->on('klasifikasi_kodes');
            $table->foreign('kode_fungsi_id')->references('id')->on('klasifikasi_kodes');
            $table->foreign('kode_kegiatan_id')->references('id')->on('klasifikasi_kodes');
            $table->foreign('kode_transaksi_id')->references('id')->on('klasifikasi_kodes');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('surat_keluar_penerima', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('surat_keluar_id');
            $table->unsignedBigInteger('user_id');
            $table->foreign('surat_keluar_id')->references('id')->on('surat_keluars')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('surat_keluar_penerima');
        Schema::dropIfExists('surat_keluars');
    }
}
