<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRapatApprovalsTable extends Migration
{
    public function up()
    {
        Schema::create('rapat_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapat_id');
            $table->unsignedTinyInteger('step_order');
            $table->unsignedBigInteger('approver_id');
            $table->string('approver_name_snapshot');
            $table->string('approver_jabatan_snapshot')->nullable();
            $table->string('status')->default('waiting');
            $table->text('catatan')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->unique(['rapat_id', 'step_order']);
            $table->foreign('rapat_id')->references('id')->on('rapats')->onDelete('cascade');
            $table->foreign('approver_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rapat_approvals');
    }
}
