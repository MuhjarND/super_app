<?php

namespace Tests\Unit;

use App\Rapat;
use Tests\TestCase;

class RapatAttendancePdfLayoutTest extends TestCase
{
    public function testAttendancePdfUsesFourColumnsWithoutAttendanceDescription()
    {
        $rapat = new Rapat([
            'judul' => 'Kegiatan Pengujian',
            'tanggal' => '2026-08-03',
            'waktu_mulai' => '09:00:00',
            'tempat' => 'Aula PTA Papua Barat',
        ]);

        $html = view('rapat.absensi.pdf', [
            'rapat' => $rapat,
            'attendanceRows' => collect([
                [
                    'name' => 'Pegawai Pengujian',
                    'description' => 'Hakim Tinggi',
                    'signature' => null,
                ],
            ]),
            'hasApprovalSignature' => false,
            'pimpinanSignature' => [],
            'kopImage' => null,
            'pdfVerification' => null,
        ])->render();

        $this->assertStringNotContainsString('Keterangan Kehadiran', $html);
        $this->assertStringNotContainsString('Telah melakukan absensi pada', $html);
        preg_match_all('/<th(?:\s|>)/', $html, $tableHeaders);
        $this->assertSame(4, count($tableHeaders[0]));
        $this->assertStringContainsString('page-break-inside: avoid', $html);
    }
}
