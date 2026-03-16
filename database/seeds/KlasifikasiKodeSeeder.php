<?php

use Illuminate\Database\Seeder;
use App\KlasifikasiKode;
use Illuminate\Support\Facades\Log;

class KlasifikasiKodeSeeder extends Seeder
{
    public function run()
    {
        $csvPath = database_path('seeds/data/klasifikasi_turunan.csv');

        if (!file_exists($csvPath)) {
            throw new RuntimeException('File CSV klasifikasi tidak ditemukan: ' . $csvPath);
        }

        $rows = $this->readCsvRows($csvPath);

        $klasifikasiData = [];
        $fungsiData = [];
        $kegiatanData = [];
        $transaksiData = [];

        foreach ($rows as $row) {
            $this->mergeNode($klasifikasiData, $row['kode_klasifikasi'], $row['klasifikasi'], null, 'klasifikasi');
            $this->mergeNode($fungsiData, $row['kode_fungsi'], $row['fungsi'], $row['kode_klasifikasi'], 'fungsi');
            $this->mergeNode($kegiatanData, $row['kode_kegiatan'], $row['kegiatan'], $row['kode_fungsi'], 'kegiatan');
            $this->mergeNode($transaksiData, $row['kode_transaksi'], $row['transaksi'], $row['kode_kegiatan'], 'transaksi');
        }

        $klasifikasiIds = [];
        foreach ($klasifikasiData as $kode => $item) {
            $model = KlasifikasiKode::updateOrCreate(
                ['kode' => $kode, 'tipe' => 'klasifikasi'],
                ['nama' => $item['nama'], 'parent_id' => null]
            );
            $klasifikasiIds[$kode] = $model->id;
        }

        $fungsiIds = [];
        foreach ($fungsiData as $kode => $item) {
            $parentId = isset($klasifikasiIds[$item['parent_kode']]) ? $klasifikasiIds[$item['parent_kode']] : null;
            $model = KlasifikasiKode::updateOrCreate(
                ['kode' => $kode, 'tipe' => 'fungsi'],
                ['nama' => $item['nama'], 'parent_id' => $parentId]
            );
            $fungsiIds[$kode] = $model->id;
        }

        $kegiatanIds = [];
        foreach ($kegiatanData as $kode => $item) {
            $parentId = isset($fungsiIds[$item['parent_kode']]) ? $fungsiIds[$item['parent_kode']] : null;
            $model = KlasifikasiKode::updateOrCreate(
                ['kode' => $kode, 'tipe' => 'kegiatan'],
                ['nama' => $item['nama'], 'parent_id' => $parentId]
            );
            $kegiatanIds[$kode] = $model->id;
        }

        foreach ($transaksiData as $kode => $item) {
            $parentId = isset($kegiatanIds[$item['parent_kode']]) ? $kegiatanIds[$item['parent_kode']] : null;
            KlasifikasiKode::updateOrCreate(
                ['kode' => $kode, 'tipe' => 'transaksi'],
                ['nama' => $item['nama'], 'parent_id' => $parentId]
            );
        }
    }

    protected function readCsvRows($path)
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new RuntimeException('Gagal membuka file CSV: ' . $path);
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return [];
        }

        $headers = array_map(function ($header) {
            $header = trim($header);
            $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
            return trim($header, "\"'");
        }, $headers);

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($headers)) {
                continue;
            }

            $row = array_combine($headers, $data);
            $normalized = [];
            foreach ($row as $key => $value) {
                $value = trim($value);
                $normalized[$key] = $value === '' ? null : $value;
            }
            $rows[] = $normalized;
        }

        fclose($handle);
        return $rows;
    }

    protected function mergeNode(&$collection, $kode, $nama, $parentKode, $level)
    {
        if (!$kode || !$nama) {
            return;
        }

        if (!isset($collection[$kode])) {
            $collection[$kode] = [
                'nama' => $nama,
                'parent_kode' => $parentKode,
            ];
            return;
        }

        if ($collection[$kode]['nama'] !== $nama) {
            Log::warning('[KlasifikasiKodeSeeder] Nama berbeda untuk kode ' . $kode . ' pada level ' . $level . ': "' . $collection[$kode]['nama'] . '" vs "' . $nama . '". Menggunakan nilai pertama.');
        }

        if ($collection[$kode]['parent_kode'] !== $parentKode) {
            Log::warning('[KlasifikasiKodeSeeder] Parent berbeda untuk kode ' . $kode . ' pada level ' . $level . '. Menggunakan parent pertama.');
        }
    }
}
