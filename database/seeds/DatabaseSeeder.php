<?php

use Illuminate\Database\Seeder;
use App\Unit;
use App\KategoriRapat;
use App\KategoriSurat;
use App\KlasifikasiKode;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->seedUnits();

        $this->call([
            RoleSeeder::class,
            JabatanSeeder::class,
            KlasifikasiKodeSeeder::class,
            UserSeeder::class,
            DasarHukumSeeder::class,
        ]);

        if (Schema::hasTable('leave_types')) {
            $this->call([
                LeaveTypeSeeder::class,
                LeavePolicySeeder::class,
                LeaveBalanceSeeder::class,
            ]);
        }

        $this->seedKategoriSurat();
        $this->seedKategoriRapat();
    }

    protected function seedUnits()
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

    protected function seedKategoriSurat()
    {
        $kategoris = KlasifikasiKode::where('tipe', 'klasifikasi')
            ->orderBy('kode')
            ->get()
            ->filter(function ($item) {
                return (bool) preg_match('/[A-Za-z]/', (string) $item->kode);
            });

        foreach ($kategoris as $kategori) {
            KategoriSurat::updateOrCreate(
                ['kode' => $kategori->kode],
                [
                    'nama' => $kategori->nama,
                    'keterangan' => 'Sinkron dari data klasifikasi surat yang aktif digunakan.',
                    'aktif' => true,
                ]
            );
        }
    }

    protected function seedKategoriRapat()
    {
        $kategoriRapats = [
            ['kode' => 'RPT-INT', 'nama' => 'Rapat Internal', 'keterangan' => 'Rapat internal pimpinan dan pegawai.', 'butuh_pakaian' => false, 'aktif' => true],
            ['kode' => 'RPT-FORMAL', 'nama' => 'Rapat Formal', 'keterangan' => 'Rapat formal dengan undangan resmi dan pakaian dinas.', 'butuh_pakaian' => true, 'aktif' => true],
            ['kode' => 'RPT-VIRTUAL', 'nama' => 'Rapat Virtual', 'keterangan' => 'Rapat melalui media virtual.', 'butuh_pakaian' => false, 'aktif' => true],
        ];

        foreach ($kategoriRapats as $kategoriRapat) {
            KategoriRapat::updateOrCreate(['kode' => $kategoriRapat['kode']], $kategoriRapat);
        }
    }
}
