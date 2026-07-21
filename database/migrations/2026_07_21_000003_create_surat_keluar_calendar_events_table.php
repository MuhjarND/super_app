<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSuratKeluarCalendarEventsTable extends Migration
{
    public function up()
    {
        Schema::create('surat_keluar_calendar_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('surat_keluar_id')->unique();
            $table->string('type', 40);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('surat_keluar_id', 'sk_calendar_surat_fk')
                ->references('id')->on('surat_keluars')->onDelete('cascade');
            $table->foreign('created_by', 'sk_calendar_creator_fk')
                ->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by', 'sk_calendar_updater_fk')
                ->references('id')->on('users')->onDelete('set null');
            $table->index(['type', 'start_date'], 'sk_calendar_type_start_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('surat_keluar_calendar_events');
    }
}
