<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitStockToSupplyItemsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('supply_items')) {
            return;
        }

        Schema::table('supply_items', function (Blueprint $table) {
            if (!Schema::hasColumn('supply_items', 'has_unit_stock')) {
                $table->boolean('has_unit_stock')->default(false)->after('stock');
            }
            if (!Schema::hasColumn('supply_items', 'unit_stock')) {
                $table->unsignedInteger('unit_stock')->default(0)->after('has_unit_stock');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('supply_items')) {
            return;
        }

        Schema::table('supply_items', function (Blueprint $table) {
            if (Schema::hasColumn('supply_items', 'unit_stock')) {
                $table->dropColumn('unit_stock');
            }
            if (Schema::hasColumn('supply_items', 'has_unit_stock')) {
                $table->dropColumn('has_unit_stock');
            }
        });
    }
}
