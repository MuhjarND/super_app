<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuratTemplateTables extends Migration
{
    public function up()
    {
        Schema::create('surat_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category', 100);
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->longText('field_schema')->nullable();
            $table->longText('template_body')->nullable();
            $table->string('sample_file_path')->nullable();
            $table->string('source_type', 30)->default('manual');
            $table->unsignedBigInteger('source_request_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'category']);
            $table->index('created_by');
            $table->index('approved_by');
        });

        Schema::create('surat_template_proposals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('slug')->nullable();
            $table->string('category', 100);
            $table->text('description')->nullable();
            $table->longText('requested_fields')->nullable();
            $table->longText('suggested_template_body')->nullable();
            $table->string('example_file_path');
            $table->enum('status', ['submitted', 'in_review', 'approved', 'rejected'])->default('submitted');
            $table->text('review_notes')->nullable();
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'category']);
            $table->index('requested_by');
            $table->index('resolved_by');
        });
    }

    public function down()
    {
        Schema::dropIfExists('surat_template_proposals');
        Schema::dropIfExists('surat_templates');
    }
}

