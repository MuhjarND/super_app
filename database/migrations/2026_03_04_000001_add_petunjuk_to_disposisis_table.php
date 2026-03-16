<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPetunjukToDisposisisTable extends Migration
{
    public function up()
    {
        Schema::table('disposisis', function (Blueprint $table) {
            $table->string('petunjuk')->nullable()->after('kepada_jabatan_id');
        });
    }

    public function down()
    {
        Schema::table('disposisis', function (Blueprint $table) {
            $table->dropColumn('petunjuk');
        });
    }
}

