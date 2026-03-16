<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCatatanTindakLanjutToDisposisisTable extends Migration
{
    public function up()
    {
        Schema::table('disposisis', function (Blueprint $table) {
            $table->text('catatan_tindak_lanjut')->nullable()->after('catatan');
        });
    }

    public function down()
    {
        Schema::table('disposisis', function (Blueprint $table) {
            $table->dropColumn('catatan_tindak_lanjut');
        });
    }
}
