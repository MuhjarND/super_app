<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRapatNotulensiTindakLanjutsTable extends Migration
{
    public function up()
    {
        Schema::create('rapat_notulensi_tindak_lanjuts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapat_notulensi_id');
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('pending');
            $table->text('deskripsi_snapshot')->nullable();
            $table->text('catatan_penyelesaian')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamps();

            $table->foreign('rapat_notulensi_id')->references('id')->on('rapat_notulensis')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');
            $table->unique(['rapat_notulensi_id', 'user_id'], 'rapat_notulensi_user_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rapat_notulensi_tindak_lanjuts');
    }
}
