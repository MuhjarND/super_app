<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVirtualMeetingsTables extends Migration
{
    public function up()
    {
        Schema::create('virtual_meetings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('surat_masuk_id')->nullable()->unique();
            $table->string('judul');
            $table->date('tanggal_kegiatan');
            $table->time('waktu_mulai');
            $table->time('waktu_selesai')->nullable();
            $table->text('zoom_link');
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->foreign('surat_masuk_id')->references('id')->on('surat_masuks')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['tanggal_kegiatan', 'waktu_mulai']);
        });

        Schema::create('virtual_meeting_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('virtual_meeting_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('urutan')->default(999);
            $table->timestamps();

            $table->unique(['virtual_meeting_id', 'user_id']);
            $table->foreign('virtual_meeting_id')->references('id')->on('virtual_meetings')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('virtual_meeting_user');
        Schema::dropIfExists('virtual_meetings');
    }
}
