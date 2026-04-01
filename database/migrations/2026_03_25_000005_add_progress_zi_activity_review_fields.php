<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProgressZiActivityReviewFields extends Migration
{
    public function up()
    {
        Schema::table('zi_activities', function (Blueprint $table) {
            $table->string('source_reference_type', 50)->nullable()->after('source_type');
            $table->unsignedBigInteger('source_reference_id')->nullable()->after('source_reference_type');

            $table->index(['source_reference_type', 'source_reference_id'], 'zi_activities_source_reference_idx');
        });

        Schema::create('zi_activity_approvals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('zi_activity_id');
            $table->unsignedBigInteger('approver_id');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('request_notes')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->foreign('zi_activity_id')->references('id')->on('zi_activities')->onDelete('cascade');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['approver_id', 'status'], 'zi_activity_approvals_approver_status_idx');
            $table->index(['zi_activity_id', 'status'], 'zi_activity_approvals_activity_status_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('zi_activity_approvals');

        Schema::table('zi_activities', function (Blueprint $table) {
            $table->dropIndex('zi_activities_source_reference_idx');
            $table->dropColumn(['source_reference_type', 'source_reference_id']);
        });
    }
}
