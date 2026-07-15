<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryMaintenanceSchedulesTables extends Migration
{
    public function up()
    {
        Schema::create('inventory_maintenance_schedules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->unsignedBigInteger('inventory_item_detail_id')->nullable();
            $table->dateTime('scheduled_at');
            $table->text('description');
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamp('notification_completed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('inventory_item_detail_id', 'inventory_schedule_detail_fk')
                ->references('id')->on('inventory_item_details')->onDelete('set null');
            $table->foreign('completed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['status', 'scheduled_at'], 'inventory_schedule_status_date_idx');
        });

        Schema::create('inventory_maintenance_schedule_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('inventory_maintenance_schedule_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->foreign('inventory_maintenance_schedule_id', 'inventory_schedule_notification_schedule_fk')
                ->references('id')->on('inventory_maintenance_schedules')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(
                ['inventory_maintenance_schedule_id', 'user_id'],
                'inventory_schedule_notification_unique'
            );
            $table->index(['status', 'last_attempt_at'], 'inventory_schedule_notification_status_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_maintenance_schedule_notifications');
        Schema::dropIfExists('inventory_maintenance_schedules');
    }
}
