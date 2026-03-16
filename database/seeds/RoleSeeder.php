<?php

use Illuminate\Database\Seeder;
use App\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['name' => 'super_admin', 'display_name' => 'Super Admin'],
            ['name' => 'operator_surat_masuk', 'display_name' => 'Operator Surat Masuk'],
            ['name' => 'admin_surat', 'display_name' => 'Admin Surat'],
            ['name' => 'sekretaris', 'display_name' => 'Sekretaris'],
            ['name' => 'panitera', 'display_name' => 'Panitera'],
            ['name' => 'ketua', 'display_name' => 'Ketua'],
            ['name' => 'wakil_ketua', 'display_name' => 'Wakil Ketua'],
            ['name' => 'kasubag', 'display_name' => 'Kasubag'],
            ['name' => 'kabag', 'display_name' => 'Kabag'],
            ['name' => 'panmud', 'display_name' => 'Panmud'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
