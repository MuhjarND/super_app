<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttendanceSignerToRapats extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('rapats') || Schema::hasColumn('rapats', 'attendance_signer_id')) {
            return;
        }

        Schema::table('rapats', function (Blueprint $table) {
            $table->unsignedBigInteger('attendance_signer_id')
                ->nullable()
                ->after('attendance_surat_keluar_id');
            $table->foreign('attendance_signer_id', 'rapat_attendance_signer_fk')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        if (!Schema::hasTable('rapats') || !Schema::hasColumn('rapats', 'attendance_signer_id')) {
            return;
        }

        Schema::table('rapats', function (Blueprint $table) {
            $table->dropForeign('rapat_attendance_signer_fk');
            $table->dropColumn('attendance_signer_id');
        });
    }
}
