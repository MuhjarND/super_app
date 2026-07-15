<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTautanTindakLanjutToDisposisisTable extends Migration
{
    public function up()
    {
        Schema::table('disposisis', function (Blueprint $table) {
            $table->text('tautan_tindak_lanjut')->nullable()->after('catatan_tindak_lanjut');
        });
    }

    public function down()
    {
        Schema::table('disposisis', function (Blueprint $table) {
            $table->dropColumn('tautan_tindak_lanjut');
        });
    }
}
