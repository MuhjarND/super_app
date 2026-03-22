<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddManualContentFieldsToRapatLaporansTable extends Migration
{
    public function up()
    {
        Schema::table('rapat_laporans', function (Blueprint $table) {
            $table->longText('bab_1_latar_belakang')->nullable()->after('deskripsi');
            $table->longText('bab_1_dasar')->nullable()->after('bab_1_latar_belakang');
            $table->longText('bab_1_tujuan')->nullable()->after('bab_1_dasar');
            $table->longText('bab_2_hasil_monitoring')->nullable()->after('bab_1_tujuan');
            $table->longText('bab_3_tindak_lanjut')->nullable()->after('bab_2_hasil_monitoring');
            $table->timestamp('generated_at')->nullable()->after('archived_at');
        });
    }

    public function down()
    {
        Schema::table('rapat_laporans', function (Blueprint $table) {
            $table->dropColumn([
                'bab_1_latar_belakang',
                'bab_1_dasar',
                'bab_1_tujuan',
                'bab_2_hasil_monitoring',
                'bab_3_tindak_lanjut',
                'generated_at',
            ]);
        });
    }
}
