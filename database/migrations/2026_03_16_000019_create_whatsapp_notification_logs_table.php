<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappNotificationLogsTable extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_notification_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('module', 100);
            $table->string('event', 100);
            $table->string('notifiable_type')->nullable();
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->unsignedBigInteger('target_user_id')->nullable();
            $table->string('target_name')->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->longText('message');
            $table->string('status', 30)->default('queued');
            $table->longText('response_body')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['module', 'event']);
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index(['target_user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_notification_logs');
    }
}
