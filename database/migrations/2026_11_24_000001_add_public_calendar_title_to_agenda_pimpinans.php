<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublicCalendarTitleToAgendaPimpinans extends Migration
{
    public function up()
    {
        Schema::table('agenda_pimpinans', function (Blueprint $table) {
            $table->string('calendar_public_title')->nullable();
        });
    }

    public function down()
    {
        Schema::table('agenda_pimpinans', function (Blueprint $table) {
            $table->dropColumn('calendar_public_title');
        });
    }
}
