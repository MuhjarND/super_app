<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$surat = new App\SuratKeluar();
$surat->forceFill([
    'nomor_surat' => '10/KPTA.W31-A/KP/IX/2026',
    'tanggal_surat' => '2026-09-21',
    'created_at' => '2026-09-21 00:00:00',
    'opsi_penerima' => 'internal',
]);
$image = public_path('kpta + stempel.png');
$signature = [
    'image' => 'data:image/png;base64,' . base64_encode(file_get_contents($image)),
    'name' => 'Ketua Pengadilan',
    'nip' => '199001012020011001',
];
$fields = [
    'nama_pembuat_keterangan' => 'Operator', 'nip_pembuat_keterangan' => '199101012021011002',
    'jabatan_pembuat_keterangan' => 'Operator', 'nama_pegawai' => 'Pegawai Contoh',
    'nip_pegawai' => '199202022022021003', 'jabatan_pegawai' => 'Analis', 'unit_kerja' => 'Bagian Umum',
    'satuan_kerja' => 'Pengadilan Tinggi Agama Papua Barat', 'tanggal_presensi' => '21 September 2026',
    'waktu_presensi' => '08:00', 'zona_waktu' => 'WIT', 'status_presensi' => 'kehadiran',
    'tempat_surat' => 'Manokwari', 'jabatan_pimpinan_satker' => 'Ketua', 'nama_pimpinan_satker' => 'Ketua Pengadilan',
    'nip_pimpinan_satker' => '199001012020011001',
];
$pdf = Barryvdh\DomPDF\Facade::loadView('surat-template.pdf.document', [
    'suratKeluar' => $surat,
    'templateName' => 'Surat Keterangan Perbaikan Presensi',
    'templateSlug' => 'surat-keterangan-perbaikan-presensi',
    'renderedBody' => '',
    'fieldValues' => $fields,
    'kopImage' => 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('kop_undangan.png'))),
    'signatoryTitle' => ['line1' => 'Ketua,', 'line2' => 'Pengadilan Tinggi Agama Papua Barat'],
    'approvalSignature' => $signature,
    'approvalNote' => null,
    'pdfVerification' => null,
])->setPaper('a4', 'portrait');
file_put_contents(__DIR__ . '/suket-preview-test.pdf', $pdf->output());
echo "ok\n";
