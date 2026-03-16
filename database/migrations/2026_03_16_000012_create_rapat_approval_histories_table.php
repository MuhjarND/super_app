<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRapatApprovalHistoriesTable extends Migration
{
    public function up()
    {
        Schema::create('rapat_approval_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapat_id');
            $table->unsignedBigInteger('rapat_approval_id')->nullable();
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->unsignedTinyInteger('step_order');
            $table->string('approver_name_snapshot');
            $table->string('approver_jabatan_snapshot')->nullable();
            $table->string('action');
            $table->text('catatan')->nullable();
            $table->timestamp('acted_at');
            $table->timestamps();

            $table->foreign('rapat_id')->references('id')->on('rapats')->onDelete('cascade');
            $table->foreign('rapat_approval_id')->references('id')->on('rapat_approvals')->onDelete('set null');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rapat_approval_histories');
    }
}
