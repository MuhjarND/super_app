<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRapatsTable extends Migration
{
    public function up()
    {
        Schema::create('rapats', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_undangan');
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->unsignedBigInteger('kategori_rapat_id');
            $table->date('tanggal');
            $table->time('waktu_mulai');
            $table->string('tempat');
            $table->unsignedBigInteger('approver_1_id')->nullable();
            $table->unsignedBigInteger('approver_2_id')->nullable();
            $table->string('approval1_jabatan_manual')->nullable();
            $table->text('detail_tambahan')->nullable();
            $table->text('tujuan_surat')->nullable();
            $table->string('jenis_pakaian')->nullable();
            $table->boolean('is_virtual')->default(false);
            $table->string('meeting_id')->nullable();
            $table->string('meeting_passcode')->nullable();
            $table->string('lampiran_tambahan_path')->nullable();
            $table->string('lampiran_tambahan_nama')->nullable();
            $table->string('lampiran_tambahan_mime')->nullable();
            $table->unsignedBigInteger('lampiran_tambahan_size')->nullable();
            $table->string('status')->default('draft');
            $table->string('token_qr')->nullable()->unique();
            $table->string('public_code')->nullable()->unique();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_pattern')->nullable();
            $table->date('recurring_until')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('kategori_rapat_id')->references('id')->on('kategori_rapats')->onDelete('cascade');
            $table->foreign('approver_1_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approver_2_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rapats');
    }
}
