<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDriveUrlToZiEvidences extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('zi_evidences', 'drive_url')) {
            Schema::table('zi_evidences', function (Blueprint $table) {
                $table->text('drive_url')->nullable();
            });
        }
    }

    public function down()
    {
        Schema::table('zi_evidences', function (Blueprint $table) {
            $table->dropColumn('drive_url');
        });
    }
}
