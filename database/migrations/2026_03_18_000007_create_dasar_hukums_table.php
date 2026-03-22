<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDasarHukumsTable extends Migration
{
    public function up()
    {
        Schema::create('dasar_hukums', function (Blueprint $table) {
            $table->id();
            $table->string('tema');
            $table->unsignedBigInteger('kategori_surat_kode_id')->nullable();
            $table->text('kata_kunci')->nullable();
            $table->text('uraian');
            $table->unsignedInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->foreign('kategori_surat_kode_id', 'dasar_hukum_kategori_fk')
                ->references('id')->on('klasifikasi_kodes')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('dasar_hukums');
    }
}
