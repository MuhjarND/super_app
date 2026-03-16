<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisposisisTable extends Migration
{
    public function up()
    {
        Schema::create('disposisis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('surat_masuk_id');
            $table->unsignedBigInteger('dari_user_id');
            $table->unsignedBigInteger('kepada_user_id');
            $table->unsignedBigInteger('dari_jabatan_id')->nullable();
            $table->unsignedBigInteger('kepada_jabatan_id')->nullable();
            $table->text('catatan')->nullable();
            $table->enum('tipe', ['disposisi', 'naikan'])->default('disposisi');
            $table->enum('status', ['pending', 'dibaca', 'ditindaklanjuti'])->default('pending');
            $table->timestamps();

            $table->foreign('surat_masuk_id')->references('id')->on('surat_masuks')->onDelete('cascade');
            $table->foreign('dari_user_id')->references('id')->on('users');
            $table->foreign('kepada_user_id')->references('id')->on('users');
            $table->foreign('dari_jabatan_id')->references('id')->on('jabatans');
            $table->foreign('kepada_jabatan_id')->references('id')->on('jabatans');
        });
    }

    public function down()
    {
        Schema::dropIfExists('disposisis');
    }
}
