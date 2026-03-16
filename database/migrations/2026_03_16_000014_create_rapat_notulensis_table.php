<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRapatNotulensisTable extends Migration
{
    public function up()
    {
        Schema::create('rapat_notulensis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapat_id')->unique();
            $table->unsignedBigInteger('notulis_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('mode')->default('template_a');
            $table->string('status')->default('draft');
            $table->boolean('tidak_membuat_notulen')->default(false);
            $table->string('judul')->nullable();
            $table->text('uraian_kegiatan')->nullable();
            $table->text('agenda_rapat')->nullable();
            $table->text('susunan_agenda')->nullable();
            $table->text('hasil_rapat')->nullable();
            $table->text('rekomendasi')->nullable();
            $table->text('dokumentasi_rapat')->nullable();
            $table->text('catatan')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_nama')->nullable();
            $table->string('file_mime')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->boolean('approval_ready')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('rapat_id')->references('id')->on('rapats')->onDelete('cascade');
            $table->foreign('notulis_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rapat_notulensis');
    }
}
