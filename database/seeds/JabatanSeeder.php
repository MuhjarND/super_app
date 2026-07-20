<?php

use Illuminate\Database\Seeder;
use App\Jabatan;
use App\Unit;

class JabatanSeeder extends Seeder
{
    public function run()
    {
        $unitMap = [
            'KPTA' => 'PIMPINAN',
            'WKPTA' => 'PIMPINAN',
            'SEK' => 'KESEKRETARIATAN',
            'PAN' => 'KEPANITERAAN',
            'ADMIN_SURAT' => 'KESEKRETARIATAN',
            'OPR_SM' => 'KESEKRETARIATAN',
            'KABAG_KEPEG' => 'KESEKRETARIATAN',
            'KABAG_UMUM' => 'KESEKRETARIATAN',
            'KASUBAG_KEPEG' => 'KESEKRETARIATAN',
            'KASUBAG_RENPRO' => 'KESEKRETARIATAN',
            'KASUBAG_LAPKEU' => 'KESEKRETARIATAN',
            'KASUBAG_TURT' => 'KESEKRETARIATAN',
            'PANMUD_BANDING' => 'KEPANITERAAN',
            'PANMUD_HUKUM' => 'KEPANITERAAN',
        ];

        $jabatans = [
            ['nama' => 'Ketua PTA', 'kode' => 'KPTA', 'level' => 1, 'parent_id' => null],
            ['nama' => 'Wakil Ketua PTA', 'kode' => 'WKPTA', 'level' => 1, 'parent_id' => null],
            ['nama' => 'Sekretaris', 'kode' => 'SEK', 'level' => 2, 'parent_id' => null],
            ['nama' => 'Panitera', 'kode' => 'PAN', 'level' => 2, 'parent_id' => null],
            ['nama' => 'Admin Surat', 'kode' => 'ADMIN_SURAT', 'level' => 3, 'parent_id' => null],
            ['nama' => 'Operator Surat Masuk', 'kode' => 'OPR_SM', 'level' => 4, 'parent_id' => null],
        ];

        // Create top-level jabatans first
        foreach ($jabatans as $j) {
            $j['unit_id'] = optional(Unit::where('kode', $unitMap[$j['kode']] ?? null)->first())->id;
            Jabatan::updateOrCreate(['kode' => $j['kode']], $j);
        }

        $sek = Jabatan::where('kode', 'SEK')->first();
        $pan = Jabatan::where('kode', 'PAN')->first();

        // Under Sekretaris
        $kabagKepeg = Jabatan::updateOrCreate(['kode' => 'KABAG_KEPEG'], [
            'nama' => 'Kabag Kepegawaian',
            'kode' => 'KABAG_KEPEG',
            'level' => 3,
            'parent_id' => $sek->id,
            'unit_id' => optional(Unit::where('kode', $unitMap['KABAG_KEPEG'])->first())->id,
        ]);
        $kabagUmum = Jabatan::updateOrCreate(['kode' => 'KABAG_UMUM'], [
            'nama' => 'Kabag Umum',
            'kode' => 'KABAG_UMUM',
            'level' => 3,
            'parent_id' => $sek->id,
            'unit_id' => optional(Unit::where('kode', $unitMap['KABAG_UMUM'])->first())->id,
        ]);

        // Under Kabag Kepegawaian
        Jabatan::updateOrCreate(['kode' => 'KASUBAG_KEPEG'], [
            'nama' => 'Kasubag Kepegawaian',
            'kode' => 'KASUBAG_KEPEG',
            'level' => 4,
            'parent_id' => $kabagKepeg->id,
            'unit_id' => optional(Unit::where('kode', $unitMap['KASUBAG_KEPEG'])->first())->id,
        ]);
        Jabatan::updateOrCreate(['kode' => 'KASUBAG_RENPRO'], [
            'nama' => 'Kasubag Rencana dan Program',
            'kode' => 'KASUBAG_RENPRO',
            'level' => 4,
            'parent_id' => $kabagKepeg->id,
            'unit_id' => optional(Unit::where('kode', $unitMap['KASUBAG_RENPRO'])->first())->id,
        ]);

        // Under Kabag Umum
        Jabatan::updateOrCreate(['kode' => 'KASUBAG_LAPKEU'], [
            'nama' => 'Kasubag Pelaporan dan Keuangan',
            'kode' => 'KASUBAG_LAPKEU',
            'level' => 4,
            'parent_id' => $kabagUmum->id,
            'unit_id' => optional(Unit::where('kode', $unitMap['KASUBAG_LAPKEU'])->first())->id,
        ]);
        Jabatan::updateOrCreate(['kode' => 'KASUBAG_TURT'], [
            'nama' => 'Kasubag TURT',
            'kode' => 'KASUBAG_TURT',
            'level' => 4,
            'parent_id' => $kabagUmum->id,
            'unit_id' => optional(Unit::where('kode', $unitMap['KASUBAG_TURT'])->first())->id,
        ]);

        // Under Panitera
        Jabatan::updateOrCreate(['kode' => 'PANMUD_BANDING'], [
            'nama' => 'Panmud Banding',
            'kode' => 'PANMUD_BANDING',
            'level' => 3,
            'parent_id' => $pan->id,
            'unit_id' => optional(Unit::where('kode', $unitMap['PANMUD_BANDING'])->first())->id,
        ]);
        Jabatan::updateOrCreate(['kode' => 'PANMUD_HUKUM'], [
            'nama' => 'Panmud Hukum',
            'kode' => 'PANMUD_HUKUM',
            'level' => 3,
            'parent_id' => $pan->id,
            'unit_id' => optional(Unit::where('kode', $unitMap['PANMUD_HUKUM'])->first())->id,
        ]);
    }
}
