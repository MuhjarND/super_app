<?php

use Illuminate\Database\Seeder;
use App\Unit;
use App\KategoriSurat;
use App\KlasifikasiKode;

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
        ]);

        $this->seedKategoriSurat();
    }

    protected function seedUnits()
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
}
