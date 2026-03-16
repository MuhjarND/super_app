<?php

use Illuminate\Database\Seeder;
use App\User;
use App\Role;
use App\Jabatan;
use App\Unit;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@pta-papuabarat.go.id',
                'role' => 'super_admin',
                'jabatan_kode' => 'KPTA',
                'nip' => '199001010001',
                'no_hp' => '08123456001',
            ],
            [
                'name' => 'Operator Surat Masuk',
                'email' => 'operator@pta-papuabarat.go.id',
                'role' => 'operator_surat_masuk',
                'jabatan_kode' => 'OPR_SM',
                'nip' => '199001010002',
                'no_hp' => '08123456002',
            ],
            [
                'name' => 'Admin Surat',
                'email' => 'adminsurat@pta-papuabarat.go.id',
                'role' => 'admin_surat',
                'jabatan_kode' => 'ADMIN_SURAT',
                'nip' => '199001010003',
                'no_hp' => '08123456003',
            ],
            [
                'name' => 'Sekretaris',
                'email' => 'sekretaris@pta-papuabarat.go.id',
                'role' => 'sekretaris',
                'jabatan_kode' => 'SEK',
                'nip' => '199001010004',
                'no_hp' => '08123456004',
            ],
            [
                'name' => 'Panitera',
                'email' => 'panitera@pta-papuabarat.go.id',
                'role' => 'panitera',
                'jabatan_kode' => 'PAN',
                'nip' => '199001010005',
                'no_hp' => '08123456005',
            ],
            [
                'name' => 'Ketua PTA',
                'email' => 'ketua@pta-papuabarat.go.id',
                'role' => 'ketua',
                'jabatan_kode' => 'KPTA',
                'nip' => '199001010006',
                'no_hp' => '08123456006',
            ],
            [
                'name' => 'Wakil Ketua PTA',
                'email' => 'wakilketua@pta-papuabarat.go.id',
                'role' => 'wakil_ketua',
                'jabatan_kode' => 'WKPTA',
                'nip' => '199001010007',
                'no_hp' => '08123456007',
            ],
            [
                'name' => 'Kabag Kepegawaian',
                'email' => 'kabagkepeg@pta-papuabarat.go.id',
                'role' => 'kabag',
                'jabatan_kode' => 'KABAG_KEPEG',
                'nip' => '199001010008',
                'no_hp' => '08123456008',
            ],
            [
                'name' => 'Kabag Umum',
                'email' => 'kabagumum@pta-papuabarat.go.id',
                'role' => 'kabag',
                'jabatan_kode' => 'KABAG_UMUM',
                'nip' => '199001010009',
                'no_hp' => '08123456009',
            ],
            [
                'name' => 'Kasubag Kepegawaian',
                'email' => 'kasubagkepeg@pta-papuabarat.go.id',
                'role' => 'kasubag',
                'jabatan_kode' => 'KASUBAG_KEPEG',
                'nip' => '199001010010',
                'no_hp' => '08123456010',
            ],
            [
                'name' => 'Kasubag Rencana dan Program',
                'email' => 'kasubagrenpro@pta-papuabarat.go.id',
                'role' => 'kasubag',
                'jabatan_kode' => 'KASUBAG_RENPRO',
                'nip' => '199001010011',
                'no_hp' => '08123456011',
            ],
            [
                'name' => 'Kasubag Pelaporan dan Keuangan',
                'email' => 'kasubaglapkeu@pta-papuabarat.go.id',
                'role' => 'kasubag',
                'jabatan_kode' => 'KASUBAG_LAPKEU',
                'nip' => '199001010012',
                'no_hp' => '08123456012',
            ],
            [
                'name' => 'Kasubag TURT',
                'email' => 'kasubagturt@pta-papuabarat.go.id',
                'role' => 'kasubag',
                'jabatan_kode' => 'KASUBAG_TURT',
                'nip' => '199001010013',
                'no_hp' => '08123456013',
            ],
            [
                'name' => 'Panmud Banding',
                'email' => 'panmudbanding@pta-papuabarat.go.id',
                'role' => 'panmud',
                'jabatan_kode' => 'PANMUD_BANDING',
                'nip' => '199001010014',
                'no_hp' => '08123456014',
            ],
            [
                'name' => 'Panmud Hukum',
                'email' => 'panmudhukum@pta-papuabarat.go.id',
                'role' => 'panmud',
                'jabatan_kode' => 'PANMUD_HUKUM',
                'nip' => '199001010015',
                'no_hp' => '08123456015',
            ],
        ];

        foreach ($users as $userData) {
            $jabatan = Jabatan::where('kode', $userData['jabatan_kode'])->first();
            $unitId = $jabatan ? $jabatan->unit_id : null;

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password123'),
                    'jabatan_id' => $jabatan ? $jabatan->id : null,
                    'unit_id' => $unitId,
                    'nip' => $userData['nip'],
                    'no_hp' => $userData['no_hp'],
                ]
            );

            $role = Role::where('name', $userData['role'])->first();
            if ($role && !$user->roles->contains($role->id)) {
                $user->roles()->attach($role->id);
            }
        }
    }
}
