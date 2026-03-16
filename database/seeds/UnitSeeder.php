<?php

use Illuminate\Database\Seeder;
use App\Unit;

class UnitSeeder extends Seeder
{
    public function run()
    {
        $units = [
            ['kode' => 'PIMPINAN', 'nama' => 'Pimpinan', 'keterangan' => 'Ketua dan Wakil Ketua PTA'],
            ['kode' => 'KESEKRETARIATAN', 'nama' => 'Kesekretariatan', 'keterangan' => 'Sekretaris dan unsur pendukung kesekretariatan'],
            ['kode' => 'KEPANITERAAN', 'nama' => 'Kepaniteraan', 'keterangan' => 'Panitera dan unsur kepaniteraan'],
            ['kode' => 'KEPEGAWAIAN', 'nama' => 'Kepegawaian', 'keterangan' => 'Kabag dan Kasubag kepegawaian'],
            ['kode' => 'UMUM', 'nama' => 'Umum dan Keuangan', 'keterangan' => 'Kabag umum, pelaporan, dan TURT'],
            ['kode' => 'PERSURATAN', 'nama' => 'Persuratan', 'keterangan' => 'Admin dan operator persuratan'],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(['kode' => $unit['kode']], $unit);
        }
    }
}
