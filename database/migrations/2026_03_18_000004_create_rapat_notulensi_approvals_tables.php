<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRapatNotulensiApprovalsTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('rapat_notulensi_approvals')) {
            Schema::create('rapat_notulensi_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rapat_notulensi_id')->unique();
                $table->unsignedBigInteger('approver_id');
                $table->string('approver_name_snapshot');
                $table->string('approver_jabatan_snapshot')->nullable();
                $table->string('status')->default('pending');
                $table->text('catatan')->nullable();
                $table->timestamp('acted_at')->nullable();
                $table->timestamp('notified_at')->nullable();
                $table->timestamps();

                $table->foreign('rapat_notulensi_id', 'rna_rapat_notulensi_fk')->references('id')->on('rapat_notulensis')->onDelete('cascade');
                $table->foreign('approver_id', 'rna_approver_fk')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('rapat_notulensi_approval_histories')) {
            Schema::create('rapat_notulensi_approval_histories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rapat_notulensi_id');
                $table->unsignedBigInteger('rapat_notulensi_approval_id');
                $table->unsignedBigInteger('approver_id');
                $table->string('approver_name_snapshot');
                $table->string('approver_jabatan_snapshot')->nullable();
                $table->string('action');
                $table->text('catatan')->nullable();
                $table->timestamp('acted_at')->nullable();
                $table->timestamps();

                $table->foreign('rapat_notulensi_id', 'rnah_notulensi_fk')->references('id')->on('rapat_notulensis')->onDelete('cascade');
                $table->foreign('rapat_notulensi_approval_id', 'rnah_approval_fk')->references('id')->on('rapat_notulensi_approvals')->onDelete('cascade');
                $table->foreign('approver_id', 'rnah_approver_fk')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('rapat_notulensi_approval_histories');
        Schema::dropIfExists('rapat_notulensi_approvals');
    }
}
