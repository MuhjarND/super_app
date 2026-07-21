<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSatkerRoleAndBersamaSatkerToRapats extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('rapats', 'bersama_satker')) {
            Schema::table('rapats', function (Blueprint $table) {
                $table->boolean('bersama_satker')->default(false)->after('tujuan_surat')->index();
            });
        }

        DB::table('roles')->updateOrInsert(
            ['name' => 'satker'],
            [
                'display_name' => 'Satuan Kerja',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down()
    {
        if (Schema::hasColumn('rapats', 'bersama_satker')) {
            Schema::table('rapats', function (Blueprint $table) {
                $table->dropIndex(['bersama_satker']);
                $table->dropColumn('bersama_satker');
            });
        }

        $roleId = DB::table('roles')->where('name', 'satker')->value('id');

        if ($roleId) {
            DB::table('role_user')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }
}
