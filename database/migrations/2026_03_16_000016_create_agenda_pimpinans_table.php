<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAgendaPimpinansTable extends Migration
{
    public function up()
    {
        Schema::create('agenda_pimpinans', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_kegiatan');
            $table->string('judul_agenda');
            $table->string('tempat');
            $table->time('waktu');
            $table->text('yang_menghadiri')->nullable();
            $table->string('seragam_pakaian')->nullable();
            $table->string('nomor_naskah_dinas')->nullable();
            $table->text('lampiran_link')->nullable();
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('agenda_pimpinan_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agenda_pimpinan_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('urutan')->default(999);
            $table->timestamps();

            $table->unique(['agenda_pimpinan_id', 'user_id']);
            $table->foreign('agenda_pimpinan_id')->references('id')->on('agenda_pimpinans')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('agenda_pimpinan_user');
        Schema::dropIfExists('agenda_pimpinans');
    }
}
