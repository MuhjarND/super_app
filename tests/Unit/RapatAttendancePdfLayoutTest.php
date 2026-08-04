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
            'hasAttendanceSigner' => true,
            'hasAttendanceSignature' => true,
            'attendanceSignature' => [
                'line1' => 'Ketua,',
                'line2' => 'Pengadilan Tinggi Agama Papua Barat',
                'name' => 'Pejabat Pengujian',
                'nip' => '198001012006041001',
                'signed_at' => \Carbon\Carbon::parse('2026-08-03 09:15:00', 'Asia/Jayapura'),
                'image' => 'data:image/png;base64,testing-signature',
            ],
            'kopImage' => null,
            'pdfVerification' => [
                'qr' => 'data:image/png;base64,testing',
            ],
        ])->render();

        $this->assertStringNotContainsString('Keterangan Kehadiran', $html);
        $this->assertStringNotContainsString('Telah melakukan absensi pada', $html);
        preg_match_all('/<th(?:\s|>)/', $html, $tableHeaders);
        $this->assertSame(4, count($tableHeaders[0]));
        $this->assertStringContainsString('page-break-inside: avoid', $html);
        $this->assertStringContainsString('position: static', $html);
        $this->assertStringContainsString('margin-top: 18pt', $html);
        $this->assertStringContainsString('QR tanda tangan pejabat absensi', $html);
        $this->assertStringContainsString('Pejabat Pengujian', $html);
        $this->assertStringContainsString('Ditandatangani secara elektronik pada', $html);
    }
}
