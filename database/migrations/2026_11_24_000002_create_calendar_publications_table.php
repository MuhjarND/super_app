<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCalendarPublicationsTable extends Migration
{
    public function up()
    {
        Schema::create('calendar_publications', function (Blueprint $table) {
            $table->string('event_key', 64)->primary();
            $table->string('public_title');
            $table->foreignId('published_by')->constrained('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('calendar_publications');
    }
}
