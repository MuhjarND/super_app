<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRapatAttendancesTable extends Migration
{
    public function up()
    {
        Schema::create('rapat_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapat_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('attendance_type')->default('internal');
            $table->string('participant_name_snapshot');
            $table->string('participant_jabatan_snapshot')->nullable();
            $table->string('guest_instansi')->nullable();
            $table->string('source')->default('public');
            $table->string('signature_path');
            $table->string('signature_mime')->nullable();
            $table->unsignedBigInteger('signature_size')->nullable();
            $table->timestamp('attended_at');
            $table->string('created_ip', 45)->nullable();
            $table->timestamps();

            $table->unique(['rapat_id', 'user_id']);
            $table->index(['rapat_id', 'attendance_type']);
            $table->foreign('rapat_id')->references('id')->on('rapats')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rapat_attendances');
    }
}
