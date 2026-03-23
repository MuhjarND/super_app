<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveManagementTables extends Migration
{
    public function up()
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->bigIncrements('id'); $table->string('code', 20)->unique(); $table->string('name'); $table->text('description')->nullable();
            $table->boolean('requires_balance')->default(false); $table->boolean('requires_document')->default(false); $table->boolean('requires_verification')->default(false); $table->boolean('requires_ppk_approval')->default(false);
            $table->unsignedInteger('max_days')->nullable(); $table->unsignedInteger('max_months')->nullable(); $table->unsignedInteger('service_years_required')->default(0); $table->string('status', 20)->default('active'); $table->timestamps();
        });
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->bigIncrements('id'); $table->unsignedBigInteger('leave_type_id'); $table->string('key', 100); $table->json('value_json')->nullable(); $table->boolean('is_active')->default(true); $table->date('effective_start')->nullable(); $table->date('effective_end')->nullable(); $table->timestamps(); $table->index(['leave_type_id', 'key', 'is_active']);
        });
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->bigIncrements('id'); $table->string('request_number', 100)->nullable()->unique(); $table->string('letter_number', 150)->nullable(); $table->unsignedBigInteger('user_id'); $table->unsignedBigInteger('leave_type_id'); $table->unsignedBigInteger('delegate_approval_id')->nullable(); $table->string('status_asn_snapshot', 30)->nullable(); $table->string('unit_snapshot')->nullable(); $table->string('jabatan_snapshot')->nullable(); $table->json('approver_chain_snapshot')->nullable(); $table->date('start_date'); $table->date('end_date'); $table->unsignedInteger('requested_days')->default(0); $table->unsignedInteger('approved_days')->default(0); $table->unsignedInteger('workday_count')->default(0); $table->string('purpose'); $table->text('reason_detail')->nullable(); $table->unsignedInteger('child_number_context')->nullable(); $table->boolean('needs_document_verification')->default(false); $table->boolean('needs_ppk_approval')->default(false); $table->boolean('is_deferred')->default(false); $table->text('deferred_reason')->nullable(); $table->unsignedInteger('revision_number')->default(0); $table->text('revision_note')->nullable(); $table->string('status', 30)->default('draft'); $table->timestamp('submitted_at')->nullable(); $table->timestamp('verified_at')->nullable(); $table->timestamp('approved_at')->nullable(); $table->timestamp('rejected_at')->nullable(); $table->timestamp('cancelled_at')->nullable(); $table->timestamp('completed_at')->nullable(); $table->timestamp('locked_at')->nullable(); $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps(); $table->index(['user_id', 'status']); $table->index(['leave_type_id', 'start_date', 'end_date']);
        });
        Schema::create('leave_request_documents', function (Blueprint $table) {
            $table->bigIncrements('id'); $table->unsignedBigInteger('leave_request_id'); $table->string('document_type', 50); $table->string('original_name'); $table->string('file_path'); $table->string('mime_type', 100)->nullable(); $table->unsignedBigInteger('file_size')->default(0); $table->boolean('is_verified')->default(false); $table->unsignedBigInteger('verified_by')->nullable(); $table->timestamp('verified_at')->nullable(); $table->text('verification_note')->nullable(); $table->timestamps(); $table->index(['leave_request_id', 'document_type']);
        });
        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->bigIncrements('id'); $table->unsignedBigInteger('leave_request_id'); $table->unsignedInteger('step_no'); $table->string('role_name', 50); $table->unsignedBigInteger('approver_id')->nullable(); $table->unsignedBigInteger('delegated_to_id')->nullable(); $table->string('status', 30)->default('waiting'); $table->string('action', 30)->nullable(); $table->timestamp('acted_at')->nullable(); $table->text('note')->nullable(); $table->json('meta_json')->nullable(); $table->timestamps(); $table->index(['approver_id', 'status']); $table->index(['leave_request_id', 'step_no']);
        });
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->bigIncrements('id'); $table->unsignedBigInteger('user_id'); $table->unsignedBigInteger('leave_type_id'); $table->unsignedInteger('year'); $table->integer('opening_balance')->default(0); $table->integer('entitlement')->default(0); $table->integer('carry_forward')->default(0); $table->integer('adjustment_plus')->default(0); $table->integer('adjustment_minus')->default(0); $table->integer('used_days')->default(0); $table->integer('reserved_days')->default(0); $table->integer('remaining_balance')->default(0); $table->json('meta_json')->nullable(); $table->timestamps(); $table->unique(['user_id','leave_type_id','year'], 'leave_balances_user_type_year_unique');
        });
        Schema::create('leave_holidays', function (Blueprint $table) {
            $table->bigIncrements('id'); $table->date('holiday_date')->unique(); $table->string('name'); $table->string('category', 30); $table->boolean('impacts_balance')->default(false); $table->unsignedBigInteger('leave_type_id')->nullable(); $table->unsignedInteger('deduction_days')->default(0); $table->boolean('is_national_holiday')->default(false); $table->boolean('is_collective_leave')->default(false); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('leave_audit_trails', function (Blueprint $table) {
            $table->bigIncrements('id'); $table->unsignedBigInteger('leave_request_id'); $table->unsignedBigInteger('actor_id')->nullable(); $table->string('event', 50); $table->json('old_values_json')->nullable(); $table->json('new_values_json')->nullable(); $table->text('note')->nullable(); $table->string('ip_address', 45)->nullable(); $table->text('user_agent')->nullable(); $table->timestamp('created_at')->nullable(); $table->index(['leave_request_id', 'created_at']);
        });
        Schema::create('leave_delegations', function (Blueprint $table) {
            $table->bigIncrements('id'); $table->unsignedBigInteger('delegator_id'); $table->unsignedBigInteger('delegate_id'); $table->string('scope', 50)->default('leave_approval'); $table->date('start_date'); $table->date('end_date'); $table->boolean('is_active')->default(true); $table->text('note')->nullable(); $table->timestamps(); $table->index(['delegator_id', 'delegate_id', 'is_active']);
        });
        Schema::create('leave_number_sequences', function (Blueprint $table) {
            $table->bigIncrements('id'); $table->unsignedInteger('year'); $table->string('sequence_type', 50); $table->string('prefix', 100)->nullable(); $table->unsignedInteger('last_number')->default(0); $table->json('meta_json')->nullable(); $table->timestamps(); $table->unique(['year', 'sequence_type'], 'leave_number_sequences_year_type_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('leave_number_sequences'); Schema::dropIfExists('leave_delegations'); Schema::dropIfExists('leave_audit_trails'); Schema::dropIfExists('leave_holidays'); Schema::dropIfExists('leave_balances'); Schema::dropIfExists('leave_approvals'); Schema::dropIfExists('leave_request_documents'); Schema::dropIfExists('leave_requests'); Schema::dropIfExists('leave_policies'); Schema::dropIfExists('leave_types');
    }
}
