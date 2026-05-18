<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePdfVerificationsTable extends Migration
{
    public function up()
    {
        Schema::create('pdf_verifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('token')->unique();
            $table->string('module', 80)->index();
            $table->string('document_type', 120)->index();
            $table->string('document_id')->nullable()->index();
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('file_hash', 64)->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->json('signers')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pdf_verifications');
    }
}
