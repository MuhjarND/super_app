<?php

use Illuminate\Database\Seeder;
use App\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            ['name' => 'super_admin', 'display_name' => 'Super Admin'],
            ['name' => 'admin', 'display_name' => 'Admin'],
            ['name' => 'operator', 'display_name' => 'Operator'],
            ['name' => 'notulis', 'display_name' => 'Notulis'],
            ['name' => 'peserta', 'display_name' => 'Peserta'],
            ['name' => 'approval', 'display_name' => 'Approval'],
            ['name' => 'protokoler', 'display_name' => 'Protokoler'],
            ['name' => 'operator_surat_masuk', 'display_name' => 'Operator Surat Masuk'],
            ['name' => 'admin_surat', 'display_name' => 'Admin Surat'],
            ['name' => 'sekretaris', 'display_name' => 'Sekretaris'],
            ['name' => 'panitera', 'display_name' => 'Panitera'],
            ['name' => 'ketua', 'display_name' => 'Ketua'],
            ['name' => 'wakil_ketua', 'display_name' => 'Wakil Ketua'],
            ['name' => 'kasubag', 'display_name' => 'Kasubag'],
            ['name' => 'kabag', 'display_name' => 'Kabag'],
            ['name' => 'panmud', 'display_name' => 'Panmud'],
            ['name' => 'pegawai', 'display_name' => 'Pegawai'],
            ['name' => 'atasan_langsung', 'display_name' => 'Atasan Langsung'],
            ['name' => 'admin_kepegawaian', 'display_name' => 'Admin Kepegawaian'],
            ['name' => 'verifikator_dokumen', 'display_name' => 'Verifikator Dokumen'],
            ['name' => 'ppk', 'display_name' => 'PPK'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
