<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmploymentPeriodBaselineToUsersTable extends Migration
{
    public function up()
    {
        $addYears = !Schema::hasColumn('users', 'masa_kerja_tahun');
        $addMonths = !Schema::hasColumn('users', 'masa_kerja_bulan');
        $addReferenceDate = !Schema::hasColumn('users', 'masa_kerja_acuan');

        if (!$addYears && !$addMonths && !$addReferenceDate) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($addYears, $addMonths, $addReferenceDate) {
            if ($addYears) {
                $table->unsignedSmallInteger('masa_kerja_tahun')->nullable()->after('tmt_pns');
            }

            if ($addMonths) {
                $table->unsignedTinyInteger('masa_kerja_bulan')->nullable()->after('masa_kerja_tahun');
            }

            if ($addReferenceDate) {
                $table->date('masa_kerja_acuan')->nullable()->after('masa_kerja_bulan');
            }
        });
    }

    public function down()
    {
        $columns = collect(['masa_kerja_tahun', 'masa_kerja_bulan', 'masa_kerja_acuan'])
            ->filter(function ($column) {
                return Schema::hasColumn('users', $column);
            })
            ->all();

        if (!empty($columns)) {
            Schema::table('users', function (Blueprint $table) use ($columns) {
                $table->dropColumn($columns);
            });
        }
    }
}
