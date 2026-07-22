<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LinkLibraryMembersToUsers extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('library_members', 'user_id')) {
            return;
        }

        Schema::table('library_members', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->unique()->after('id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('library_members', 'user_id')) {
            return;
        }

        Schema::table('library_members', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');
        });
    }
}
