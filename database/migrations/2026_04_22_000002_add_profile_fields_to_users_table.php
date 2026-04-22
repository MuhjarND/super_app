<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AddProfileFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username', 60)->nullable()->after('name');
            }

            if (!Schema::hasColumn('users', 'profile_photo_path')) {
                $table->string('profile_photo_path')->nullable()->after('password');
            }
        });

        $used = [];
        DB::table('users')->orderBy('id')->get(['id', 'name', 'email'])->each(function ($user) use (&$used) {
            $base = Str::slug(Str::before((string) $user->email, '@'), '_');
            if ($base === '') {
                $base = Str::slug((string) $user->name, '_');
            }
            if ($base === '') {
                $base = 'user_' . $user->id;
            }

            $username = $base;
            $counter = 1;
            while (in_array($username, $used, true) || DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $base . '_' . $counter;
                $counter++;
            }

            $used[] = $username;

            DB::table('users')->where('id', $user->id)->update([
                'username' => $username,
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropUnique(['username']);
                $table->dropColumn('username');
            }

            if (Schema::hasColumn('users', 'profile_photo_path')) {
                $table->dropColumn('profile_photo_path');
            }
        });
    }
}
