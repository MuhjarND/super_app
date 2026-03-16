<?php

use Illuminate\Database\Seeder;
use App\Bidang;
use App\Unit;
use App\KategoriRapat;
use App\KategoriSurat;
use App\KlasifikasiKode;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->seedUnits();
        $this->seedBidangs();

        $this->call([
            RoleSeeder::class,
            JabatanSeeder::class,
            KlasifikasiKodeSeeder::class,
            UserSeeder::class,
        ]);

        $this->seedKategoriSurat();
        $this->seedKategoriRapat();
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

    protected function seedBidangs()
    {
        $bidangs = [
            ['kode' => 'PIMPINAN', 'nama' => 'Pimpinan', 'keterangan' => 'Ketua, Wakil Ketua, Sekretaris, dan Panitera'],
            ['kode' => 'KEPEGAWAIAN', 'nama' => 'Kepegawaian', 'keterangan' => 'Bidang kepegawaian dan pengembangan SDM'],
            ['kode' => 'PERENCANAAN', 'nama' => 'Perencanaan', 'keterangan' => 'Bidang perencanaan, program, dan pelaporan'],
            ['kode' => 'KEUANGAN', 'nama' => 'Keuangan', 'keterangan' => 'Bidang keuangan dan pelaporan'],
            ['kode' => 'TURT', 'nama' => 'TURT', 'keterangan' => 'Bidang tata usaha, rumah tangga, dan protokoler'],
            ['kode' => 'KEPANITERAAN', 'nama' => 'Kepaniteraan', 'keterangan' => 'Bidang kepaniteraan dan hukum'],
            ['kode' => 'PERSURATAN', 'nama' => 'Persuratan', 'keterangan' => 'Bidang pengelolaan surat masuk dan keluar'],
        ];

        foreach ($bidangs as $bidang) {
            Bidang::updateOrCreate(['kode' => $bidang['kode']], $bidang);
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
