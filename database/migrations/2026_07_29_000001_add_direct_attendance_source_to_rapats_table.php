<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDirectAttendanceSourceToRapatsTable extends Migration
{
    public function up()
    {
        Schema::table('rapats', function (Blueprint $table) {
            $table->boolean('is_attendance_only')
                ->default(false)
                ->after('public_code')
                ->index('rapat_attendance_only_idx');
            $table->unsignedBigInteger('attendance_surat_keluar_id')
                ->nullable()
                ->after('is_attendance_only');
            $table->unique('attendance_surat_keluar_id', 'rapat_attendance_surat_unique');
            $table->foreign('attendance_surat_keluar_id', 'rapat_attendance_surat_fk')
                ->references('id')
                ->on('surat_keluars')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('rapats', function (Blueprint $table) {
            $table->dropForeign('rapat_attendance_surat_fk');
            $table->dropUnique('rapat_attendance_surat_unique');
            $table->dropIndex('rapat_attendance_only_idx');
            $table->dropColumn(['attendance_surat_keluar_id', 'is_attendance_only']);
        });
    }
}
