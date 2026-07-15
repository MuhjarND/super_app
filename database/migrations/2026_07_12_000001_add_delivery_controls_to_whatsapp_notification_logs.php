<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryControlsToWhatsappNotificationLogs extends Migration
{
    public function up()
    {
        Schema::table('whatsapp_notification_logs', function (Blueprint $table) {
            $table->string('fingerprint', 64)->nullable()->after('message');
            $table->timestamp('scheduled_at')->nullable()->after('status');
            $table->timestamp('attempted_at')->nullable()->after('scheduled_at');
            $table->unsignedInteger('attempt_count')->default(0)->after('attempted_at');

            $table->index(['fingerprint', 'status'], 'wa_logs_fingerprint_status_index');
            $table->index(['status', 'scheduled_at'], 'wa_logs_delivery_queue_index');
            $table->index(['phone_number', 'created_at'], 'wa_logs_phone_created_index');
        });
    }

    public function down()
    {
        Schema::table('whatsapp_notification_logs', function (Blueprint $table) {
            $table->dropIndex('wa_logs_fingerprint_status_index');
            $table->dropIndex('wa_logs_delivery_queue_index');
            $table->dropIndex('wa_logs_phone_created_index');
            $table->dropColumn(['fingerprint', 'scheduled_at', 'attempted_at', 'attempt_count']);
        });
    }
}
