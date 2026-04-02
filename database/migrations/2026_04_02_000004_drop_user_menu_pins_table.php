<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUserMenuPinsTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('user_menu_pins');
    }

    public function down()
    {
        if (Schema::hasTable('user_menu_pins')) {
            return;
        }

        Schema::create('user_menu_pins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('menu_key', 100);
            $table->timestamps();

            $table->unique(['user_id', 'menu_key']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
}
