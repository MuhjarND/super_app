<?php

namespace Tests\Unit;

use App\Rapat;
use Carbon\Carbon;
use Tests\TestCase;

class RapatInvitationPdfLayoutTest extends TestCase
{
    public function test_verification_barcode_reserves_footer_space_below_tembusan(): void
    {
        $html = view('rapat.pdf.undangan', $this->viewData([
            'qr' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        ]))->render();

        $this->assertStringContainsString('margin-bottom: 2cm;', $html);
        $this->assertStringContainsString('bottom: -1.4cm;', $html);
        $this->assertStringContainsString('page-break-inside: avoid;', $html);
    }

    public function test_invitation_without_verification_barcode_keeps_standard_margin(): void
    {
        $html = view('rapat.pdf.undangan', $this->viewData())->render();

        $this->assertStringContainsString('margin-bottom: 2cm;', $html);
    }

    protected function viewData(array $pdfVerification = null)
    {
        $rapat = new Rapat([
            'nomor_undangan' => '001/UND/VIII/2026',
            'judul' => 'Rapat Pengujian Tata Letak',
            'deskripsi' => 'Pembahasan tata letak dokumen undangan rapat.',
            'tanggal' => '2026-08-11',
            'waktu_mulai' => '09:00:00',
            'tempat' => 'Ruang Rapat',
        ]);

        return [
            'rapat' => $rapat,
            'displayRecipients' => collect(),
            'tujuanManual' => true,
            'tujuanSurat' => 'Pejabat dan Pegawai Pengadilan Tinggi Agama Papua Barat',
            'singleRecipient' => false,
            'showRecipientListInLetter' => false,
            'showTembusan' => true,
            'showLampiranPage' => false,
            'issueDate' => Carbon::parse('2026-08-11', 'Asia/Jayapura'),
            'signatory' => (object) ['name' => 'Sekretaris'],
            'signatureApprovedAt' => null,
            'signatureImage' => null,
            'kopImage' => null,
            'lampiranLabel' => '-',
            'signatoryTitle' => ['line1' => 'Sekretaris,', 'line2' => 'Pengadilan Tinggi Agama Papua Barat'],
            'pdfVerification' => $pdfVerification,
        ];
    }
}
