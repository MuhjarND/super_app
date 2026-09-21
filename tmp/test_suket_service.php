<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$signer = new App\User();
$signer->forceFill(['id' => 1, 'name' => 'Ketua Pengadilan', 'nip' => '199001012020011001', 'jabatan_keterangan' => 'Ketua']);
$jabatan = new App\Jabatan();
$jabatan->forceFill(['nama' => 'Ketua']);
$signer->setRelation('jabatan', $jabatan);

$surat = new App\SuratKeluar();
$surat->forceFill(['id' => 987, 'nomor_surat' => '10/KPTA.W31-A/KP/IX/2026', 'tanggal_surat' => '2026-09-21']);
$approval = new App\SuratKeluarApproval();
$approval->forceFill([
    'status' => 'approved',
    'template_slug' => 'surat-keterangan-perbaikan-presensi',
    'signer_name_snapshot' => 'Ketua Pengadilan',
    'signer_title_snapshot' => 'Ketua',
    'field_values' => [
        'nama_pembuat_keterangan' => 'Operator', 'nip_pembuat_keterangan' => '199101012021011002',
        'jabatan_pembuat_keterangan' => 'Operator', 'nama_pegawai' => 'Pegawai Contoh',
        'nip_pegawai' => '199202022022021003', 'jabatan_pegawai' => 'Analis', 'unit_kerja' => 'Bagian Umum',
        'satuan_kerja' => 'Pengadilan Tinggi Agama Papua Barat', 'tanggal_presensi' => '21 September 2026',
        'waktu_presensi' => '08:00', 'zona_waktu' => 'WIT', 'status_presensi' => 'kehadiran',
        'tempat_surat' => 'Manokwari', 'jabatan_pimpinan_satker' => 'Ketua', 'nama_pimpinan_satker' => 'Ketua Pengadilan',
        'nip_pimpinan_satker' => '199001012020011001',
    ],
]);
$approval->setRelation('approver', $signer);
$approval->setRelation('suratKeluar', $surat);
$path = app(App\Services\SuratKeteranganPerbaikanPresensiDocumentService::class)->make($surat, $approval);
echo $path . PHP_EOL;
