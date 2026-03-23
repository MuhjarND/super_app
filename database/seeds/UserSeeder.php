<?php

use Illuminate\Database\Seeder;
use App\User;
use App\Role;
use App\Bidang;
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
                'roles' => ['super_admin', 'admin'],
                'jabatan_kode' => 'KPTA',
                'nip' => '199001010001',
                'no_hp' => '08123456001',
            ],
            [
                'name' => 'Operator Surat Masuk',
                'email' => 'operator@pta-papuabarat.go.id',
                'roles' => ['operator_surat_masuk', 'operator', 'notulis'],
                'jabatan_kode' => 'OPR_SM',
                'nip' => '199001010002',
                'no_hp' => '08123456002',
            ],
            [
                'name' => 'Admin Surat',
                'email' => 'adminsurat@pta-papuabarat.go.id',
                'roles' => ['admin_surat'],
                'jabatan_kode' => 'ADMIN_SURAT',
                'nip' => '199001010003',
                'no_hp' => '08123456003',
            ],
            [
                'name' => 'Sekretaris',
                'email' => 'sekretaris@pta-papuabarat.go.id',
                'roles' => ['sekretaris', 'approval', 'peserta'],
                'jabatan_kode' => 'SEK',
                'nip' => '199001010004',
                'no_hp' => '08123456004',
            ],
            [
                'name' => 'Panitera',
                'email' => 'panitera@pta-papuabarat.go.id',
                'roles' => ['panitera', 'approval', 'peserta'],
                'jabatan_kode' => 'PAN',
                'nip' => '199001010005',
                'no_hp' => '08123456005',
            ],
            [
                'name' => 'Ketua PTA',
                'email' => 'ketua@pta-papuabarat.go.id',
                'roles' => ['ketua', 'approval', 'peserta'],
                'jabatan_kode' => 'KPTA',
                'nip' => '199001010006',
                'no_hp' => '08123456006',
            ],
            [
                'name' => 'Wakil Ketua PTA',
                'email' => 'wakilketua@pta-papuabarat.go.id',
                'roles' => ['wakil_ketua', 'approval', 'peserta'],
                'jabatan_kode' => 'WKPTA',
                'nip' => '199001010007',
                'no_hp' => '08123456007',
            ],
            [
                'name' => 'Kabag Kepegawaian',
                'email' => 'kabagkepeg@pta-papuabarat.go.id',
                'roles' => ['kabag', 'peserta'],
                'jabatan_kode' => 'KABAG_KEPEG',
                'nip' => '199001010008',
                'no_hp' => '08123456008',
            ],
            [
                'name' => 'Kabag Umum',
                'email' => 'kabagumum@pta-papuabarat.go.id',
                'roles' => ['kabag', 'peserta'],
                'jabatan_kode' => 'KABAG_UMUM',
                'nip' => '199001010009',
                'no_hp' => '08123456009',
            ],
            [
                'name' => 'Kasubag Kepegawaian',
                'email' => 'kasubagkepeg@pta-papuabarat.go.id',
                'roles' => ['kasubag', 'peserta'],
                'jabatan_kode' => 'KASUBAG_KEPEG',
                'nip' => '199001010010',
                'no_hp' => '08123456010',
            ],
            [
                'name' => 'Kasubag Rencana dan Program',
                'email' => 'kasubagrenpro@pta-papuabarat.go.id',
                'roles' => ['kasubag', 'peserta'],
                'jabatan_kode' => 'KASUBAG_RENPRO',
                'nip' => '199001010011',
                'no_hp' => '08123456011',
            ],
            [
                'name' => 'Kasubag Pelaporan dan Keuangan',
                'email' => 'kasubaglapkeu@pta-papuabarat.go.id',
                'roles' => ['kasubag', 'peserta'],
                'jabatan_kode' => 'KASUBAG_LAPKEU',
                'nip' => '199001010012',
                'no_hp' => '08123456012',
            ],
            [
                'name' => 'Kasubag TURT',
                'email' => 'kasubagturt@pta-papuabarat.go.id',
                'roles' => ['kasubag', 'peserta', 'protokoler'],
                'jabatan_kode' => 'KASUBAG_TURT',
                'nip' => '199001010013',
                'no_hp' => '08123456013',
            ],
            [
                'name' => 'Panmud Banding',
                'email' => 'panmudbanding@pta-papuabarat.go.id',
                'roles' => ['panmud', 'peserta'],
                'jabatan_kode' => 'PANMUD_BANDING',
                'nip' => '199001010014',
                'no_hp' => '08123456014',
            ],
            [
                'name' => 'Panmud Hukum',
                'email' => 'panmudhukum@pta-papuabarat.go.id',
                'roles' => ['panmud', 'peserta'],
                'jabatan_kode' => 'PANMUD_HUKUM',
                'nip' => '199001010015',
                'no_hp' => '08123456015',
            ],
            [
                'name' => 'Staf Kepegawaian 1',
                'email' => 'stafkepeg1@pta-papuabarat.go.id',
                'roles' => ['peserta'],
                'jabatan_keterangan' => 'Staf Kepegawaian',
                'unit_kode' => 'KEPEGAWAIAN',
                'bidang_kode' => 'KEPEGAWAIAN',
                'hirarki' => 200,
                'nip' => '199001010016',
                'no_hp' => '08123456016',
            ],
            [
                'name' => 'Staf Kepegawaian 2',
                'email' => 'stafkepeg2@pta-papuabarat.go.id',
                'roles' => ['peserta'],
                'jabatan_keterangan' => 'Staf Kepegawaian',
                'unit_kode' => 'KEPEGAWAIAN',
                'bidang_kode' => 'KEPEGAWAIAN',
                'hirarki' => 210,
                'nip' => '199001010017',
                'no_hp' => '08123456017',
            ],
            [
                'name' => 'Staf Perencanaan',
                'email' => 'stafrenpro@pta-papuabarat.go.id',
                'roles' => ['peserta'],
                'jabatan_keterangan' => 'Staf Perencanaan',
                'unit_kode' => 'KEPEGAWAIAN',
                'bidang_kode' => 'PERENCANAAN',
                'hirarki' => 220,
                'nip' => '199001010018',
                'no_hp' => '08123456018',
            ],
            [
                'name' => 'Staf Keuangan',
                'email' => 'stafkeuangan@pta-papuabarat.go.id',
                'roles' => ['peserta'],
                'jabatan_keterangan' => 'Staf Keuangan',
                'unit_kode' => 'UMUM',
                'bidang_kode' => 'KEUANGAN',
                'hirarki' => 230,
                'nip' => '199001010019',
                'no_hp' => '08123456019',
            ],
            [
                'name' => 'Staf TURT',
                'email' => 'stafturt@pta-papuabarat.go.id',
                'roles' => ['peserta'],
                'jabatan_keterangan' => 'Staf TURT',
                'unit_kode' => 'UMUM',
                'bidang_kode' => 'TURT',
                'hirarki' => 240,
                'nip' => '199001010020',
                'no_hp' => '08123456020',
            ],
            [
                'name' => 'Staf Persuratan',
                'email' => 'stafpersuratan@pta-papuabarat.go.id',
                'roles' => ['peserta'],
                'jabatan_keterangan' => 'Staf Persuratan',
                'unit_kode' => 'PERSURATAN',
                'bidang_kode' => 'PERSURATAN',
                'hirarki' => 250,
                'nip' => '199001010021',
                'no_hp' => '08123456021',
            ],
            [
                'name' => 'Staf Kepaniteraan 1',
                'email' => 'stafkepaniteraan1@pta-papuabarat.go.id',
                'roles' => ['peserta'],
                'jabatan_keterangan' => 'Staf Kepaniteraan',
                'unit_kode' => 'KEPANITERAAN',
                'bidang_kode' => 'KEPANITERAAN',
                'hirarki' => 260,
                'nip' => '199001010022',
                'no_hp' => '08123456022',
            ],
            [
                'name' => 'Staf Kepaniteraan 2',
                'email' => 'stafkepaniteraan2@pta-papuabarat.go.id',
                'roles' => ['peserta'],
                'jabatan_keterangan' => 'Staf Kepaniteraan',
                'unit_kode' => 'KEPANITERAAN',
                'bidang_kode' => 'KEPANITERAAN',
                'hirarki' => 270,
                'nip' => '199001010023',
                'no_hp' => '08123456023',
            ],
            [
                'name' => 'Panitera Pengganti 1',
                'email' => 'pp1@pta-papuabarat.go.id',
                'roles' => ['peserta'],
                'jabatan_keterangan' => 'Panitera Pengganti',
                'unit_kode' => 'KEPANITERAAN',
                'bidang_kode' => 'KEPANITERAAN',
                'hirarki' => 280,
                'nip' => '199001010024',
                'no_hp' => '08123456024',
            ],
            [
                'name' => 'Panitera Pengganti 2',
                'email' => 'pp2@pta-papuabarat.go.id',
                'roles' => ['peserta'],
                'jabatan_keterangan' => 'Panitera Pengganti',
                'unit_kode' => 'KEPANITERAAN',
                'bidang_kode' => 'KEPANITERAAN',
                'hirarki' => 290,
                'nip' => '199001010025',
                'no_hp' => '08123456025',
            ],
        ];

        foreach ($users as $userData) {
            $jabatan = Jabatan::where('kode', $userData['jabatan_kode'] ?? null)->first();
            $unit = $jabatan
                ? Unit::find($jabatan->unit_id)
                : Unit::where('kode', $userData['unit_kode'] ?? null)->first();
            $unitId = optional($unit)->id;
            $bidangKode = $userData['bidang_kode'] ?? $this->resolveBidangKode($userData['jabatan_kode'] ?? null);
            $bidang = Bidang::where('kode', $bidangKode)->first();

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password123'),
                    'jabatan_id' => $jabatan ? $jabatan->id : null,
                    'jabatan_keterangan' => $jabatan ? $jabatan->nama : ($userData['jabatan_keterangan'] ?? null),
                    'unit_id' => $unitId,
                    'bidang_id' => $bidang ? $bidang->id : null,
                    'hirarki' => $userData['hirarki'] ?? $this->resolveHirarki($userData['jabatan_kode'] ?? null),
                    'nip' => $userData['nip'],
                    'no_hp' => $userData['no_hp'],
                ]
            );

            $roles = $userData['roles'] ?? [$userData['role']];
            $roleIds = Role::whereIn('name', $roles)->pluck('id')->all();

            $user->roles()->sync($roleIds);
        }
    }

    protected function resolveBidangKode($jabatanKode)
    {
        $map = [
            'KPTA' => 'PIMPINAN',
            'WKPTA' => 'PIMPINAN',
            'SEK' => 'PIMPINAN',
            'PAN' => 'PIMPINAN',
            'KABAG_KEPEG' => 'KEPEGAWAIAN',
            'KASUBAG_KEPEG' => 'KEPEGAWAIAN',
            'KASUBAG_RENPRO' => 'PERENCANAAN',
            'KASUBAG_LAPKEU' => 'KEUANGAN',
            'KABAG_UMUM' => 'TURT',
            'KASUBAG_TURT' => 'TURT',
            'PANMUD_BANDING' => 'KEPANITERAAN',
            'PANMUD_HUKUM' => 'KEPANITERAAN',
            'ADMIN_SURAT' => 'PERSURATAN',
            'OPR_SM' => 'PERSURATAN',
        ];

        return $map[$jabatanKode] ?? null;
    }

    protected function resolveHirarki($jabatanKode)
    {
        $map = [
            'KPTA' => 10,
            'WKPTA' => 20,
            'SEK' => 30,
            'PAN' => 40,
            'KABAG_KEPEG' => 50,
            'KABAG_UMUM' => 60,
            'PANMUD_BANDING' => 70,
            'PANMUD_HUKUM' => 80,
            'KASUBAG_KEPEG' => 90,
            'KASUBAG_RENPRO' => 100,
            'KASUBAG_LAPKEU' => 110,
            'KASUBAG_TURT' => 120,
            'ADMIN_SURAT' => 130,
            'OPR_SM' => 140,
        ];

        return $map[$jabatanKode] ?? 999;
    }
}
