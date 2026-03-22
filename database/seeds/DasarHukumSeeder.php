<?php

use App\DasarHukum;
use App\KlasifikasiKode;
use Illuminate\Database\Seeder;

class DasarHukumSeeder extends Seeder
{
    public function run()
    {
        $kategoriMap = KlasifikasiKode::whereDoesntHave('children')->get()->keyBy('kode');

        $items = [
            [
                'tema' => 'Umum Peradilan',
                'kategori_kode' => null,
                'kata_kunci' => null,
                'uraian' => 'Undang-Undang Nomor 48 Tahun 2009 tentang Kekuasaan Kehakiman.',
                'urutan' => 10,
                'aktif' => true,
            ],
            [
                'tema' => 'Peradilan Agama',
                'kategori_kode' => null,
                'kata_kunci' => null,
                'uraian' => 'Undang-Undang Nomor 7 Tahun 1989 tentang Peradilan Agama sebagaimana telah diubah terakhir dengan Undang-Undang Nomor 50 Tahun 2009.',
                'urutan' => 20,
                'aktif' => true,
            ],
            [
                'tema' => 'Zona Integritas',
                'kategori_kode' => null,
                'kata_kunci' => 'zona integritas, zi, wbk, wbbm, reformasi birokrasi',
                'uraian' => 'Peraturan Presiden Nomor 81 Tahun 2010 tentang Grand Design Reformasi Birokrasi 2010-2025.',
                'urutan' => 30,
                'aktif' => true,
            ],
            [
                'tema' => 'Zona Integritas',
                'kategori_kode' => null,
                'kata_kunci' => 'zona integritas, zi, wbk, wbbm, reformasi birokrasi',
                'uraian' => 'Pedoman pembangunan Zona Integritas menuju Wilayah Bebas dari Korupsi dan Wilayah Birokrasi Bersih dan Melayani pada instansi pemerintah.',
                'urutan' => 40,
                'aktif' => true,
            ],
            [
                'tema' => 'Informasi Publik',
                'kategori_kode' => null,
                'kata_kunci' => 'informasi publik, ppid, keterbukaan informasi, website, media informasi',
                'uraian' => 'Undang-Undang Nomor 14 Tahun 2008 tentang Keterbukaan Informasi Publik.',
                'urutan' => 50,
                'aktif' => true,
            ],
            [
                'tema' => 'Kepegawaian',
                'kategori_kode' => null,
                'kata_kunci' => 'kepegawaian, pegawai, asn, pppk, cpns, pelantikan, mutasi',
                'uraian' => 'Peraturan perundang-undangan yang berlaku di bidang manajemen aparatur sipil negara dan pembinaan kepegawaian.',
                'urutan' => 60,
                'aktif' => true,
            ],
            [
                'tema' => 'Keuangan',
                'kategori_kode' => null,
                'kata_kunci' => 'keuangan, anggaran, dipa, realisasi, pagu, laporan keuangan',
                'uraian' => 'Ketentuan pengelolaan keuangan negara dan pelaksanaan anggaran yang berlaku.',
                'urutan' => 70,
                'aktif' => true,
            ],
            [
                'tema' => 'Persuratan dan Arsip',
                'kategori_kode' => null,
                'kata_kunci' => 'arsip, persuratan, surat, naskah dinas',
                'uraian' => 'Ketentuan tata naskah dinas dan pengelolaan arsip di lingkungan peradilan agama.',
                'urutan' => 80,
                'aktif' => true,
            ],
            [
                'tema' => 'Teknologi Informasi',
                'kategori_kode' => null,
                'kata_kunci' => 'teknologi informasi, aplikasi, sistem, digital, website',
                'uraian' => 'Kebijakan internal pengelolaan teknologi informasi, aplikasi, dan sistem layanan pada Pengadilan Tinggi Agama Papua Barat.',
                'urutan' => 90,
                'aktif' => true,
            ],
            [
                'tema' => 'Monitoring dan Evaluasi',
                'kategori_kode' => null,
                'kata_kunci' => 'monitoring, evaluasi, monev, pengawasan',
                'uraian' => 'Program kerja, hasil monitoring sebelumnya, dan arahan pimpinan terkait pelaksanaan monitoring dan evaluasi kegiatan.',
                'urutan' => 100,
                'aktif' => true,
            ],
        ];

        foreach ($items as $item) {
            $kategori = $item['kategori_kode'] ? ($kategoriMap[$item['kategori_kode']] ?? null) : null;

            DasarHukum::updateOrCreate(
                [
                    'tema' => $item['tema'],
                    'uraian' => $item['uraian'],
                ],
                [
                    'kategori_surat_kode_id' => optional($kategori)->id,
                    'kata_kunci' => $item['kata_kunci'],
                    'urutan' => $item['urutan'],
                    'aktif' => $item['aktif'],
                ]
            );
        }
    }
}
