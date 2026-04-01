<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGuidelineIndicatorIdToZiIndicatorsTable extends Migration
{
    public function up()
    {
        Schema::table('zi_indicators', function (Blueprint $table) {
            $table->unsignedBigInteger('zi_guideline_indicator_id')->nullable()->after('zi_activity_id');
            $table->foreign('zi_guideline_indicator_id')->references('id')->on('zi_guideline_indicators')->onDelete('set null');
            $table->index('zi_guideline_indicator_id', 'zi_indicators_guideline_indicator_idx');
        });
    }

    public function down()
    {
        Schema::table('zi_indicators', function (Blueprint $table) {
            $table->dropForeign(['zi_guideline_indicator_id']);
            $table->dropIndex('zi_indicators_guideline_indicator_idx');
            $table->dropColumn('zi_guideline_indicator_id');
        });
    }
}
