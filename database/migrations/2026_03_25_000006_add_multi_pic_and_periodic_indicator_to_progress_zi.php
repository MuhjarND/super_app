<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddMultiPicAndPeriodicIndicatorToProgressZi extends Migration
{
    public function up()
    {
        Schema::create('zi_area_pic', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('zi_area_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['zi_area_id', 'user_id'], 'zi_area_pic_unique');
            $table->foreign('zi_area_id')->references('id')->on('zi_areas')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('zi_guideline_indicators', function (Blueprint $table) {
            $table->boolean('is_periodic')->default(false)->after('implementation_note');
            $table->index('is_periodic');
        });

        $areas = DB::table('zi_areas')
            ->whereNotNull('pic_user_id')
            ->get(['id', 'pic_user_id']);

        foreach ($areas as $area) {
            DB::table('zi_area_pic')->updateOrInsert(
                [
                    'zi_area_id' => $area->id,
                    'user_id' => $area->pic_user_id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down()
    {
        Schema::table('zi_guideline_indicators', function (Blueprint $table) {
            $table->dropIndex(['is_periodic']);
            $table->dropColumn('is_periodic');
        });

        Schema::dropIfExists('zi_area_pic');
    }
}
