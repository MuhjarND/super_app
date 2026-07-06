<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserJabatanDelegationsTable extends Migration
{
    public function up()
    {
        Schema::create('user_jabatan_delegations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('jabatan_id');
            $table->enum('delegation_type', ['plh', 'plt']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('user_id', 'ujd_user_unique');
            $table->foreign('user_id', 'ujd_user_fk')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('jabatan_id', 'ujd_jabatan_fk')->references('id')->on('jabatans')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_jabatan_delegations');
    }
}
