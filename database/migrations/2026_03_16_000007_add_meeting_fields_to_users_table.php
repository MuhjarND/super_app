<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMeetingFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('bidang_id')->nullable()->after('unit_id');
            $table->string('jabatan_keterangan')->nullable()->after('jabatan_id');
            $table->unsignedInteger('hirarki')->default(999)->after('jabatan_keterangan');

            $table->foreign('bidang_id')->references('id')->on('bidangs')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['bidang_id']);
            $table->dropColumn(['bidang_id', 'jabatan_keterangan', 'hirarki']);
        });
    }
}
