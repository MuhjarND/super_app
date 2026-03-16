<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWhatsappTrackingColumns extends Migration
{
    public function up()
    {
        Schema::table('rapat_approvals', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->after('acted_at');
        });

        Schema::table('rapats', function (Blueprint $table) {
            $table->timestamp('participant_notified_at')->nullable()->after('public_code');
            $table->timestamp('last_attendance_reminder_at')->nullable()->after('participant_notified_at');
        });

        Schema::table('agenda_pimpinans', function (Blueprint $table) {
            $table->timestamp('last_notified_at')->nullable()->after('updated_by');
        });

        Schema::table('votings', function (Blueprint $table) {
            $table->timestamp('participant_notified_at')->nullable()->after('updated_by');
        });
    }

    public function down()
    {
        Schema::table('votings', function (Blueprint $table) {
            $table->dropColumn(['participant_notified_at']);
        });

        Schema::table('agenda_pimpinans', function (Blueprint $table) {
            $table->dropColumn(['last_notified_at']);
        });

        Schema::table('rapats', function (Blueprint $table) {
            $table->dropColumn(['participant_notified_at', 'last_attendance_reminder_at']);
        });

        Schema::table('rapat_approvals', function (Blueprint $table) {
            $table->dropColumn(['notified_at']);
        });
    }
}
