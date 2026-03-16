<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRapatPesertaTable extends Migration
{
    public function up()
    {
        Schema::create('rapat_peserta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapat_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('urutan')->default(999);
            $table->timestamps();

            $table->unique(['rapat_id', 'user_id']);
            $table->foreign('rapat_id')->references('id')->on('rapats')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rapat_peserta');
    }
}
