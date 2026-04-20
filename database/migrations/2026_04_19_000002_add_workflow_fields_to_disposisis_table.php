<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkflowFieldsToDisposisisTable extends Migration
{
    public function up()
    {
        Schema::table('disposisis', function (Blueprint $table) {
            if (!Schema::hasColumn('disposisis', 'priority_level')) {
                $table->enum('priority_level', ['low', 'normal', 'high'])->default('normal')->after('status');
            }

            if (!Schema::hasColumn('disposisis', 'target_tindak_lanjut_at')) {
                $table->dateTime('target_tindak_lanjut_at')->nullable()->after('priority_level');
            }

            if (!Schema::hasColumn('disposisis', 'read_at')) {
                $table->dateTime('read_at')->nullable()->after('target_tindak_lanjut_at');
            }

            if (!Schema::hasColumn('disposisis', 'completed_at')) {
                $table->dateTime('completed_at')->nullable()->after('read_at');
            }

            if (!Schema::hasColumn('disposisis', 'notification_sent_at')) {
                $table->dateTime('notification_sent_at')->nullable()->after('completed_at');
            }

            if (!Schema::hasColumn('disposisis', 'reminder_whatsapp_sent_at')) {
                $table->dateTime('reminder_whatsapp_sent_at')->nullable()->after('notification_sent_at');
            }
        });
    }

    public function down()
    {
        Schema::table('disposisis', function (Blueprint $table) {
            foreach (['priority_level', 'target_tindak_lanjut_at', 'read_at', 'completed_at', 'notification_sent_at', 'reminder_whatsapp_sent_at'] as $column) {
                if (Schema::hasColumn('disposisis', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
