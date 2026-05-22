<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImagePathToSupplyItemsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('supply_items') && !Schema::hasColumn('supply_items', 'image_path')) {
            Schema::table('supply_items', function (Blueprint $table) {
                $table->string('image_path')->nullable()->after('description');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('supply_items') && Schema::hasColumn('supply_items', 'image_path')) {
            Schema::table('supply_items', function (Blueprint $table) {
                $table->dropColumn('image_path');
            });
        }
    }
}
