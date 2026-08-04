<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExternalInvitationToRapats extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('rapats', 'is_external')) {
            Schema::table('rapats', function (Blueprint $table) {
                $table->boolean('is_external')->default(false)->after('bersama_satker');
            });
        }

        if (!Schema::hasColumn('rapats', 'tujuan_external')) {
            Schema::table('rapats', function (Blueprint $table) {
                $table->text('tujuan_external')->nullable()->after('is_external');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('rapats', 'tujuan_external')) {
            Schema::table('rapats', function (Blueprint $table) {
                $table->dropColumn('tujuan_external');
            });
        }

        if (Schema::hasColumn('rapats', 'is_external')) {
            Schema::table('rapats', function (Blueprint $table) {
                $table->dropColumn('is_external');
            });
        }
    }
}
