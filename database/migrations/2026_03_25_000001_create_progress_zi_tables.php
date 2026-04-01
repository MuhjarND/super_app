<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgressZiTables extends Migration
{
    public function up()
    {
        Schema::create('zi_periods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->unsignedSmallInteger('year');
            $table->date('target_evaluation_date')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->enum('status', ['active', 'inactive'])->default('inactive');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['year', 'is_active']);
        });

        Schema::create('zi_areas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('pic_user_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('pic_user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('zi_activities', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('zi_period_id');
            $table->unsignedBigInteger('zi_area_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('target_start_date')->nullable();
            $table->date('target_end_date')->nullable();
            $table->unsignedBigInteger('pic_user_id')->nullable();
            $table->enum('status', ['belum_mulai', 'dijadwalkan', 'sedang_berjalan', 'sudah_terlaksana', 'selesai', 'perlu_perbaikan'])->default('belum_mulai');
            $table->string('source_type', 30)->default('manual');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('zi_period_id')->references('id')->on('zi_periods')->onDelete('cascade');
            $table->foreign('zi_area_id')->references('id')->on('zi_areas')->onDelete('cascade');
            $table->foreign('pic_user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['zi_period_id', 'zi_area_id']);
            $table->index(['pic_user_id', 'status']);
            $table->index('target_end_date');
        });

        Schema::create('zi_indicators', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('zi_activity_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('weight', 8, 2)->default(1);
            $table->string('target_fulfillment_text')->nullable();
            $table->boolean('is_evidence_required')->default(true);
            $table->unsignedInteger('minimum_evidence_count')->default(1);
            $table->enum('status', ['belum_diisi', 'belum_terpenuhi', 'sebagian_terpenuhi', 'terpenuhi', 'diverifikasi', 'ditolak'])->default('belum_diisi');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('zi_activity_id')->references('id')->on('zi_activities')->onDelete('cascade');
            $table->index(['zi_activity_id', 'status']);
        });

        Schema::create('zi_activity_realizations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('zi_activity_id');
            $table->date('realization_date');
            $table->text('implementation_summary');
            $table->text('result_summary')->nullable();
            $table->text('obstacles')->nullable();
            $table->text('follow_up')->nullable();
            $table->enum('source_type', ['manual', 'persuratan', 'rapat', 'cuti'])->default('manual');
            $table->string('source_reference_type', 50)->nullable();
            $table->unsignedBigInteger('source_reference_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('zi_activity_id')->references('id')->on('zi_activities')->onDelete('cascade');
            $table->index(['zi_activity_id', 'realization_date'], 'zi_realizations_activity_date_idx');
            $table->index(['source_reference_type', 'source_reference_id'], 'zi_realizations_source_idx');
        });

        Schema::create('zi_evidences', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('zi_activity_realization_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('evidence_type', 50);
            $table->string('source_type', 30)->default('manual');
            $table->string('source_reference_type', 50)->nullable();
            $table->unsignedBigInteger('source_reference_id')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->enum('status', ['belum_ada', 'terupload', 'terhubung', 'valid', 'revisi', 'tidak_valid'])->default('belum_ada');
            $table->boolean('is_auto_linked')->default(false);
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
            $table->foreign('zi_activity_realization_id')->references('id')->on('zi_activity_realizations')->onDelete('cascade');
            $table->index(['status', 'evidence_type'], 'zi_evidences_status_type_idx');
            $table->index(['source_reference_type', 'source_reference_id'], 'zi_evidences_source_idx');
        });

        Schema::create('zi_indicator_evidence', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('zi_indicator_id');
            $table->unsignedBigInteger('zi_evidence_id');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['zi_indicator_id', 'zi_evidence_id']);
            $table->foreign('zi_indicator_id')->references('id')->on('zi_indicators')->onDelete('cascade');
            $table->foreign('zi_evidence_id')->references('id')->on('zi_evidences')->onDelete('cascade');
        });

        Schema::create('zi_reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('reviewable');
            $table->enum('review_scope', ['indicator', 'evidence']);
            $table->enum('status', ['approved', 'revisi', 'rejected']);
            $table->text('review_notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('zi_reviews');
        Schema::dropIfExists('zi_indicator_evidence');
        Schema::dropIfExists('zi_evidences');
        Schema::dropIfExists('zi_activity_realizations');
        Schema::dropIfExists('zi_indicators');
        Schema::dropIfExists('zi_activities');
        Schema::dropIfExists('zi_areas');
        Schema::dropIfExists('zi_periods');
    }
}
