<?php

use Illuminate\Database\Seeder;
use App\Unit;

class UnitSeeder extends Seeder
{
    public function run()
    {
        $units = [
            ['kode' => 'PIMPINAN', 'nama' => 'Pimpinan', 'keterangan' => 'Ketua dan Wakil Ketua PTA'],
            ['kode' => 'HAKIM_TINGGI', 'nama' => 'Hakim Tinggi', 'keterangan' => 'Hakim Tinggi PTA Papua Barat'],
            ['kode' => 'KESEKRETARIATAN', 'nama' => 'Kesekretariatan', 'keterangan' => 'Sekretaris dan unsur pendukung kesekretariatan'],
            ['kode' => 'KEPANITERAAN', 'nama' => 'Kepaniteraan', 'keterangan' => 'Panitera dan unsur kepaniteraan'],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(['kode' => $unit['kode']], $unit);
        }
    }
}
