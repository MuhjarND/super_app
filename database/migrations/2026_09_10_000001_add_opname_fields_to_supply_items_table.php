<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOpnameFieldsToSupplyItemsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('supply_items')) {
            return;
        }

        Schema::table('supply_items', function (Blueprint $table) {
            if (!Schema::hasColumn('supply_items', 'account_code')) {
                $table->string('account_code', 40)->nullable()->after('code');
            }

            if (!Schema::hasColumn('supply_items', 'unit_price')) {
                $table->decimal('unit_price', 15, 2)->nullable()->after('stock');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('supply_items')) {
            return;
        }

        Schema::table('supply_items', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('supply_items', 'account_code')) {
                $columns[] = 'account_code';
            }

            if (Schema::hasColumn('supply_items', 'unit_price')) {
                $columns[] = 'unit_price';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
}
