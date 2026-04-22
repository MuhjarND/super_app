<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLegacySourceIdToPersuratanTables extends Migration
{
    public function up()
    {
        Schema::table('surat_masuks', function (Blueprint $table) {
            if (!Schema::hasColumn('surat_masuks', 'legacy_source_id')) {
                $table->unsignedBigInteger('legacy_source_id')->nullable()->after('id');
                $table->unique('legacy_source_id', 'surat_masuks_legacy_source_id_unique');
            }
        });

        Schema::table('surat_keluars', function (Blueprint $table) {
            if (!Schema::hasColumn('surat_keluars', 'legacy_source_id')) {
                $table->unsignedBigInteger('legacy_source_id')->nullable()->after('id');
                $table->unique('legacy_source_id', 'surat_keluars_legacy_source_id_unique');
            }
        });

        Schema::table('disposisis', function (Blueprint $table) {
            if (!Schema::hasColumn('disposisis', 'legacy_source_id')) {
                $table->unsignedBigInteger('legacy_source_id')->nullable()->after('id');
                $table->unique('legacy_source_id', 'disposisis_legacy_source_id_unique');
            }
        });
    }

    public function down()
    {
        Schema::table('disposisis', function (Blueprint $table) {
            if (Schema::hasColumn('disposisis', 'legacy_source_id')) {
                $table->dropUnique('disposisis_legacy_source_id_unique');
                $table->dropColumn('legacy_source_id');
            }
        });

        Schema::table('surat_keluars', function (Blueprint $table) {
            if (Schema::hasColumn('surat_keluars', 'legacy_source_id')) {
                $table->dropUnique('surat_keluars_legacy_source_id_unique');
                $table->dropColumn('legacy_source_id');
            }
        });

        Schema::table('surat_masuks', function (Blueprint $table) {
            if (Schema::hasColumn('surat_masuks', 'legacy_source_id')) {
                $table->dropUnique('surat_masuks_legacy_source_id_unique');
                $table->dropColumn('legacy_source_id');
            }
        });
    }
}
