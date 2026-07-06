<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfileSignatureToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'profile_signature_path')) {
                $table->string('profile_signature_path')->nullable()->after('profile_photo_path');
            }
            if (!Schema::hasColumn('users', 'profile_signature_mime')) {
                $table->string('profile_signature_mime', 100)->nullable()->after('profile_signature_path');
            }
            if (!Schema::hasColumn('users', 'profile_signature_size')) {
                $table->unsignedBigInteger('profile_signature_size')->nullable()->after('profile_signature_mime');
            }
            if (!Schema::hasColumn('users', 'profile_signature_method')) {
                $table->string('profile_signature_method', 20)->nullable()->after('profile_signature_size');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['profile_signature_method', 'profile_signature_size', 'profile_signature_mime', 'profile_signature_path'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
