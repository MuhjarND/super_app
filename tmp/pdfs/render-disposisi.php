<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sender = new App\User(['name' => 'Ketua Pengadilan Tinggi Agama Papua Barat']);
$recipient = new App\User(['name' => 'Sekretaris']);
$sourcePosition = new App\Jabatan(['nama' => 'Ketua']);
$targetPosition = new App\Jabatan(['nama' => 'Sekretaris']);
$letter = new App\SuratMasuk([
    'nomor_surat' => '139/BUA/TI1.4.1/VII/2026',
    'pengirim' => 'MAHKAMAH AGUNG REPUBLIK INDONESIA BADAN URUSAN ADMINISTRASI',
    'perihal' => 'Penanggulangan dan Mitigasi Penipuan melalui Peniruan Situs Web Pengadilan',
    'tanggal_surat' => '2026-07-30',
    'sifat' => 'biasa',
    'file_path' => 'surat-masuk/contoh.pdf',
]);
$letter->forceFill(['id' => 139, 'created_at' => Carbon\Carbon::parse('2026-08-06 13:13:00')]);
$disposition = new App\Disposisi([
    'petunjuk' => 'Sesuai catatan',
    'catatan' => 'Segera ditindaklanjuti sesuai ketentuan yang berlaku.',
    'priority_level' => 'normal',
    'status' => 'pending',
]);
$disposition->forceFill(['id' => 1, 'created_at' => Carbon\Carbon::parse('2026-08-06 13:13:00')]);
$disposition->setRelation('suratMasuk', $letter);
$disposition->setRelation('dariUser', $sender);
$disposition->setRelation('kepadaUser', $recipient);
$disposition->setRelation('dariJabatan', $sourcePosition);
$disposition->setRelation('kepadaJabatan', $targetPosition);

$kop = public_path('kop_undangan.png');
$kopImage = 'data:image/png;base64,' . base64_encode(file_get_contents($kop));
$qrPath = public_path('logo_qr.png');
$pdfVerification = ['qr' => is_file($qrPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($qrPath)) : $kopImage];
$pdf = app('dompdf.wrapper')->loadView('surat-masuk.pdf.disposisi', [
    'disposisi' => $disposition,
    'suratMasuk' => $letter,
    'kopImage' => $kopImage,
    'petunjukOptions' => App\Disposisi::getPetunjukOptions(),
    'pdfVerification' => $pdfVerification,
    'pdfVerificationInFlow' => false,
    'pdfVerificationQrSize' => 34,
])->setPaper('a4', 'portrait');
file_put_contents(__DIR__ . '/disposisi-preview.pdf', $pdf->output());


