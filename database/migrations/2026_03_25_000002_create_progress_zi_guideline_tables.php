<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgressZiGuidelineTables extends Migration
{
    public function up()
    {
        Schema::create('zi_guideline_points', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('zi_area_id');
            $table->string('code', 20);
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('zi_area_id')->references('id')->on('zi_areas')->onDelete('cascade');
            $table->unique(['zi_area_id', 'code']);
            $table->index(['zi_area_id', 'sort_order'], 'zi_guideline_points_area_sort_idx');
        });

        Schema::create('zi_guideline_sub_points', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('zi_guideline_point_id');
            $table->string('code', 20);
            $table->text('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('zi_guideline_point_id')->references('id')->on('zi_guideline_points')->onDelete('cascade');
            $table->unique(['zi_guideline_point_id', 'code']);
            $table->index(['zi_guideline_point_id', 'sort_order'], 'zi_guideline_sub_points_point_sort_idx');
        });

        Schema::create('zi_guideline_indicators', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('zi_guideline_sub_point_id');
            $table->string('code', 20)->nullable();
            $table->text('indicator_text');
            $table->text('evidence_example')->nullable();
            $table->text('implementation_note')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('zi_guideline_sub_point_id')->references('id')->on('zi_guideline_sub_points')->onDelete('cascade');
            $table->index(['zi_guideline_sub_point_id', 'sort_order'], 'zi_guideline_indicators_sub_sort_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('zi_guideline_indicators');
        Schema::dropIfExists('zi_guideline_sub_points');
        Schema::dropIfExists('zi_guideline_points');
    }
}
