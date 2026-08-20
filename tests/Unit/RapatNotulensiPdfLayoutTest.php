<?php

namespace Tests\Unit;

use App\RapatNotulensi;
use Tests\TestCase;

class RapatNotulensiPdfLayoutTest extends TestCase
{
    public function test_long_minutes_table_and_validation_have_separate_layout_areas(): void
    {
        $html = view('rapat.notulensi.pdf', $this->viewData())->render();

        $this->assertStringContainsString('margin: 1.4cm 1.2cm 2.8cm 1.2cm;', $html);
        $this->assertRegExp('/\.minutes-rich-content table\s*\{[^}]*table-layout:\s*fixed\s*!important;/s', $html);
        $this->assertRegExp('/\.minutes-rich-content table tr\s*\{[^}]*page-break-inside:\s*avoid;/s', $html);
        $this->assertRegExp('/\.recommendation-section\s*\{[^}]*page-break-inside:\s*avoid;/s', $html);
        $this->assertStringContainsString('<div class="section-block-header">D.&nbsp;&nbsp; HASIL AGENDA</div>', $html);
        $this->assertStringContainsString('class="pdf-verification-badge"', $html);
        $this->assertStringContainsString('bottom: -2cm;', $html);
    }

    public function test_documentation_is_arranged_in_two_column_rows_on_a_new_page(): void
    {
        $html = view('rapat.notulensi.pdf', $this->viewData())->render();

        $this->assertRegExp('/\.documentation-section\s*\{[^}]*page-break-before:\s*always;/s', $html);
        $this->assertStringContainsString('class="documentation-table"', $html);
        $this->assertSame(3, substr_count($html, 'alt="Dokumentasi'));
    }

    protected function viewData(): array
    {
        $notulensi = new RapatNotulensi();
        $notulensi->forceFill([
            'agenda_rapat' => '<p>Agenda pengujian</p>',
            'susunan_agenda' => '<ol><li>Pembukaan</li><li>Pembahasan</li></ol>',
            'hasil_rapat' => '<p>Hasil rapat:</p><table><thead><tr><th>No.</th><th>Bidang</th><th>Tindak Lanjut</th><th>Penanggung Jawab</th><th>Target</th></tr></thead><tbody><tr><td>1</td><td>Umum</td><td>Melaksanakan tindak lanjut.</td><td>Bagian Umum</td><td>Segera</td></tr></tbody></table>',
            'rekomendasi' => '<p>Rekomendasi pengujian.</p>',
        ]);

        $pixel = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        return [
            'notulensi' => $notulensi,
            'rapat' => null,
            'kopImage' => null,
            'dokumentasiImages' => collect([
                ['nama' => 'Dokumentasi 1', 'data_uri' => $pixel],
                ['nama' => 'Dokumentasi 2', 'data_uri' => $pixel],
                ['nama' => 'Dokumentasi 3', 'data_uri' => $pixel],
            ]),
            'uraianKegiatanRows' => [],
            'notulisSignature' => ['line1' => 'Notulis,', 'name' => 'Notulis'],
            'approvalSignature' => ['line1' => 'Pejabat Approval,', 'name' => 'Pejabat'],
            'pdfVerification' => ['qr' => $pixel],
        ];
    }
}
