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

    public function test_signatory_block_and_signature_are_left_aligned(): void
    {
        $html = view('rapat.pdf.undangan', $this->viewData())->render();

        $this->assertRegExp('/\.ttd-box\s*\{[^}]*text-align:\s*left;/s', $html);
        $this->assertRegExp('/\.signature-pad-image\s*\{[^}]*margin:\s*4pt 0 3pt;[^}]*text-align:\s*left;/s', $html);
        $this->assertRegExp('/\.signature-pad-image img\s*\{[^}]*margin:\s*0;/s', $html);
    }

    public function test_greetings_are_complete_upright_and_signatory_name_has_no_titles(): void
    {
        $data = $this->viewData();
        $data['signatory'] = (object) ['name' => 'Dr. Nama Sekretaris, S.H., M.H.'];
        $html = view('rapat.pdf.undangan', $data)->render();

        $this->assertStringContainsString("Assalamu'alaikum warahmatullahi wabarakatuh.", $html);
        $this->assertStringContainsString("Wassalamu'alaikum warahmatullahi wabarakatuh.", $html);
        $this->assertRegExp('/\.salam\s*\{[^}]*font-style:\s*normal;/s', $html);
        $this->assertStringContainsString('Nama Sekretaris', $html);
        $this->assertStringNotContainsString('Dr. Nama Sekretaris', $html);
        $this->assertStringNotContainsString('S.H., M.H.', $html);
    }

    public function test_metadata_contains_nature_single_line_number_and_wrapped_long_subject(): void
    {
        $data = $this->viewData();
        $data['rapat']->forceFill([
            'judul' => 'Rapat Monitoring dan Evaluasi Kinerja Kesekretariatan Pengadilan Agama Sewilayah Hukum',
            'sifat_surat' => 'penting',
        ]);
        $html = view('rapat.pdf.undangan', $data)->render();

        $this->assertStringContainsString('<td>Sifat</td>', $html);
        $this->assertStringContainsString('<td>Penting</td>', $html);
        $this->assertRegExp('/\.nomor-undangan-value\s*\{[^}]*white-space:\s*nowrap;/s', $html);
        $this->assertStringContainsString('<div>Undangan</div>', $html);
        $this->assertStringContainsString('class="hal-title"', $html);
    }

    public function test_institution_name_is_kept_together(): void
    {
        $html = view('rapat.pdf.undangan', $this->viewData())->render();

        $this->assertRegExp('/\.institution-name\s*\{[^}]*white-space:\s*nowrap;/s', $html);
        $this->assertStringContainsString(
            'Pengadilan&nbsp;Tinggi&nbsp;Agama&nbsp;Papua&nbsp;Barat',
            $html
        );
    }

    protected function viewData(array $pdfVerification = null)
    {
        $rapat = new Rapat([
            'nomor_undangan' => '001/UND/VIII/2026',
            'judul' => 'Rapat Pengujian Tata Letak',
            'deskripsi' => 'Pembahasan tata letak dokumen undangan rapat.',
            'sifat_surat' => 'penting',
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
            'openingParagraph' => 'Dalam rangka pelaksanaan Rapat Pengujian Tata Letak di lingkungan Pengadilan Tinggi Agama Papua Barat, dengan ini kami mengharapkan kehadiran Saudara pada kegiatan dimaksud yang akan dilaksanakan pada:',
            'signatoryTitle' => ['line1' => 'Sekretaris,', 'line2' => 'Pengadilan Tinggi Agama Papua Barat'],
            'pdfVerification' => $pdfVerification,
        ];
    }
}
