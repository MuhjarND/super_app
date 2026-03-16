<?php

use Illuminate\Database\Seeder;
use App\KategoriSurat;
use App\KlasifikasiKode;

class KategoriSuratSeeder extends Seeder
{
    public function run()
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
