<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddOperatorSiperlatinRole extends Migration
{
    public function up()
    {
        DB::table('roles')->updateOrInsert(
            ['name' => 'operator_siperlatin'],
            [
                'display_name' => 'Operator SIPERLATIN',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down()
    {
        $roleId = DB::table('roles')->where('name', 'operator_siperlatin')->value('id');

        if ($roleId) {
            DB::table('role_user')->where('role_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
        }
    }
}
