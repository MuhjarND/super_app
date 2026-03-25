<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuratKeluarApprovalsTables extends Migration
{
    public function up()
    {
        Schema::create('surat_keluar_approvals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('surat_keluar_id');
            $table->unsignedBigInteger('approver_id');
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->string('template_slug')->nullable();
            $table->string('template_name')->nullable();
            $table->longText('rendered_body')->nullable();
            $table->json('field_values')->nullable();
            $table->string('signer_name_snapshot')->nullable();
            $table->string('signer_title_snapshot')->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->foreign('surat_keluar_id')->references('id')->on('surat_keluars')->onDelete('cascade');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['approver_id', 'status']);
            $table->index(['surat_keluar_id', 'status']);
        });

        Schema::create('surat_keluar_approval_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('surat_keluar_approval_id');
            $table->unsignedBigInteger('surat_keluar_id');
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->string('action', 30);
            $table->text('note')->nullable();
            $table->string('signer_name_snapshot')->nullable();
            $table->string('signer_title_snapshot')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->foreign('surat_keluar_approval_id')->references('id')->on('surat_keluar_approvals')->onDelete('cascade');
            $table->foreign('surat_keluar_id')->references('id')->on('surat_keluars')->onDelete('cascade');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['surat_keluar_id', 'acted_at']);
            $table->index(['approver_id', 'acted_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('surat_keluar_approval_histories');
        Schema::dropIfExists('surat_keluar_approvals');
    }
}
