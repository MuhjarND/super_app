<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRapatLaporansTable extends Migration
{
    public function up()
    {
        Schema::create('rapat_laporans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapat_id');
            $table->string('jenis');
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('status')->default('aktif');
            $table->boolean('is_ready')->default(false);
            $table->string('file_path')->nullable();
            $table->string('file_nama')->nullable();
            $table->string('file_mime')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['rapat_id', 'jenis']);
            $table->index(['status', 'archived_at']);
            $table->foreign('rapat_id')->references('id')->on('rapats')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rapat_laporans');
    }
}
