<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToLibraryCatalog extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('library_books', 'deleted_at')) {
            Schema::table('library_books', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (!Schema::hasColumn('library_book_copies', 'deleted_at')) {
            Schema::table('library_book_copies', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('library_book_copies', 'deleted_at')) {
            Schema::table('library_book_copies', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('library_books', 'deleted_at')) {
            Schema::table('library_books', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
}
