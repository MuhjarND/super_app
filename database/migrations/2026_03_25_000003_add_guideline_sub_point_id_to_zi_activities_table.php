<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGuidelineSubPointIdToZiActivitiesTable extends Migration
{
    public function up()
    {
        Schema::table('zi_activities', function (Blueprint $table) {
            $table->unsignedBigInteger('zi_guideline_sub_point_id')->nullable()->after('zi_area_id');
            $table->foreign('zi_guideline_sub_point_id')->references('id')->on('zi_guideline_sub_points')->onDelete('set null');
            $table->index('zi_guideline_sub_point_id', 'zi_activities_guideline_sub_point_idx');
        });
    }

    public function down()
    {
        Schema::table('zi_activities', function (Blueprint $table) {
            $table->dropForeign(['zi_guideline_sub_point_id']);
            $table->dropIndex('zi_activities_guideline_sub_point_idx');
            $table->dropColumn('zi_guideline_sub_point_id');
        });
    }
}
