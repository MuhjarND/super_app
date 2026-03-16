<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJabatansTable extends Migration
{
    public function up()
    {
        Schema::create('jabatans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode')->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('level')->default(0);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('jabatans')->onDelete('set null');
        });

        // Add jabatan_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('jabatan_id')->nullable()->after('password');
            $table->string('nip')->nullable()->after('jabatan_id');
            $table->string('no_hp')->nullable()->after('nip');
            $table->foreign('jabatan_id')->references('id')->on('jabatans')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['jabatan_id']);
            $table->dropColumn(['jabatan_id', 'nip', 'no_hp']);
        });
        Schema::dropIfExists('jabatans');
    }
}
