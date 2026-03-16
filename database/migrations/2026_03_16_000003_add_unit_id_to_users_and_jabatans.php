<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUnitIdToUsersAndJabatans extends Migration
{
    public function up()
    {
        Schema::table('jabatans', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('level');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('unit_id')->nullable()->after('jabatan_id');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });

        Schema::table('jabatans', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropColumn('unit_id');
        });
    }
}
