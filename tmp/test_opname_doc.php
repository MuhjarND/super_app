<?php

require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$report = new App\SupplyOpnameReport();
$report->report_period = Carbon\Carbon::parse('2026-08-01');
$report->opname_date = Carbon\Carbon::parse('2026-09-09');
$report->nomor_surat = '001/SEK.PTA.W31-A/PL1.1.1/IX/2026';
$items = [
    ['id'=>1,'name'=>'Kertas HVS A4','code'=>'ATK-001','account_code'=>'117111','unit'=>'Rim','saldo'=>10,'fisik'=>9,'selisih'=>-1,'kondisi'=>'Baik','nilai'=>900000],
    ['id'=>2,'name'=>'Tinta Epson 003','code'=>'ATK-002','account_code'=>'117111','unit'=>'Botol','saldo'=>2,'fisik'=>2,'selisih'=>0,'kondisi'=>'Baik','nilai'=>260000],
    ['id'=>3,'name'=>'Sapu Lantai','code'=>'PMH-001','account_code'=>'117113','unit'=>'Buah','saldo'=>3,'fisik'=>3,'selisih'=>0,'kondisi'=>'Baik','nilai'=>123000],
];
$report->snapshot = [
    'items'=>$items,
    'groups'=>[
        ['account_code'=>'117111','label'=>'Barang Konsumsi','items'=>array_slice($items,0,2),'total'=>1160000],
        ['account_code'=>'117113','label'=>'Barang untuk Pemeliharaan','items'=>array_slice($items,2,1),'total'=>123000],
    ],
    'grand_total'=>1283000,
    'committee'=>[
        ['name'=>'Kasubag TURT Aktual','nip'=>'197001010001','role'=>'Ketua'],
        ['name'=>'Sekretaris Aktual','nip'=>'197001010002','role'=>'Sekretaris'],
        ['name'=>'Operator Persediaan Aktual','nip'=>'197001010003','role'=>'Anggota'],
        ['name'=>'Staf TURT Aktual','nip'=>'197001010004','role'=>'Anggota'],
    ],
    'signatory'=>[
        'name'=>'Sekretaris Aktual',
        'nip'=>'197001010002',
    ],
];

$generated = (new App\Services\SupplyOpnameDocumentService())->make($report);
$target = dirname(__DIR__) . '/tmp/BA-Opname-test.docx';
copy($generated, $target);
@unlink($generated);
echo $target . PHP_EOL;
