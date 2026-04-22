<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddGroupTypeToZiAreasTable extends Migration
{
    public function up()
    {
        Schema::table('zi_areas', function (Blueprint $table) {
            $table->enum('group_type', ['pengungkit', 'reform', 'hasil'])
                ->default('pengungkit')
                ->after('description');
        });

        $areas = DB::table('zi_areas')->orderBy('code')->orderBy('id')->get();

        foreach ($areas as $index => $area) {
            $position = $index + 1;

            if ($position <= 6) {
                $groupType = 'pengungkit';
            } elseif ($position <= 12) {
                $groupType = 'reform';
            } else {
                $groupType = 'hasil';
            }

            DB::table('zi_areas')
                ->where('id', $area->id)
                ->update(['group_type' => $groupType]);
        }
    }

    public function down()
    {
        Schema::table('zi_areas', function (Blueprint $table) {
            $table->dropColumn('group_type');
        });
    }
}
