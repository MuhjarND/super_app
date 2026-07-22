<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmploymentIdentityFieldsToUsersTable extends Migration
{
    public function up()
    {
        $addGolonganRuang = !Schema::hasColumn('users', 'golongan_ruang');
        $addSatuanKerja = !Schema::hasColumn('users', 'satuan_kerja');

        if (!$addGolonganRuang && !$addSatuanKerja) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($addGolonganRuang, $addSatuanKerja) {
            if ($addGolonganRuang) {
                $table->string('golongan_ruang', 100)->nullable()->after('tmt_pns');
            }

            if ($addSatuanKerja) {
                $table->string('satuan_kerja')->nullable()->after('golongan_ruang');
            }
        });
    }

    public function down()
    {
        $columns = collect(['golongan_ruang', 'satuan_kerja'])
            ->filter(function ($column) {
                return Schema::hasColumn('users', $column);
            })
            ->all();

        if (empty($columns)) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }
}
