<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLegacySourceIdToInventoryTables extends Migration
{
    public function up()
    {
        $tables = [
            'inventory_units',
            'inventory_conditions',
            'inventory_rooms',
            'inventory_brands',
            'inventory_authorities',
            'inventory_items',
            'inventory_item_details',
            'inventory_maintenance_transactions',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'legacy_source_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedBigInteger('legacy_source_id')->nullable()->unique()->after('id');
                });
            }
        }
    }

    public function down()
    {
        $tables = [
            'inventory_units',
            'inventory_conditions',
            'inventory_rooms',
            'inventory_brands',
            'inventory_authorities',
            'inventory_items',
            'inventory_item_details',
            'inventory_maintenance_transactions',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'legacy_source_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropUnique($blueprint->getTable() . '_legacy_source_id_unique');
                });
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('legacy_source_id');
                });
            }
        }
    }
}
