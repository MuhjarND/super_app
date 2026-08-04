<?php

namespace Tests\Unit;

use App\Disposisi;
use App\KlasifikasiKode;
use App\Services\PersuratanRegisterReportService;
use App\SuratKeluar;
use App\SuratMasuk;
use App\User;
use Carbon\Carbon;
use Tests\TestCase;

class PersuratanRegisterReportTest extends TestCase
{
    public function test_period_label_supports_month_and_custom_date_range(): void
    {
        $service = new PersuratanRegisterReportService();

        $this->assertSame(
            'BULAN JUNI TAHUN 2026',
            $service->periodLabel(Carbon::parse('2026-06-01'), Carbon::parse('2026-06-30'))
        );
        $this->assertSame(
            'PERIODE 15 JUNI 2026 S.D. 04 JULI 2026',
            $service->periodLabel(Carbon::parse('2026-06-15'), Carbon::parse('2026-07-04'))
        );
    }

    public function test_incoming_rows_include_classification_disposition_and_file_status(): void
    {
        $classification = new KlasifikasiKode(['kode' => 'KP', 'nama' => 'Kepegawaian']);
        $creator = new User(['name' => 'Operator Surat']);
        $disposition = new Disposisi(['tipe' => 'naikan']);
        $disposition->forceFill(['created_at' => Carbon::parse('2026-06-30 10:00:00')]);

        $letter = new SuratMasuk([
            'nomor_surat' => '6086/SEK/SK.KP3.3/VI/2026',
            'opsi_pengirim' => 'mahkamah_agung',
            'pengirim' => 'SEKRETARIS MAHKAMAH AGUNG',
            'perihal' => 'Ketentuan pelaksanaan tugas belajar',
            'tanggal_surat' => '2026-06-30',
            'sifat' => 'biasa',
            'file_path' => 'surat-masuk/contoh.pdf',
            'status' => 'didisposisi',
        ]);
        $letter->setRelation('klasifikasiKode', $classification);
        $letter->setRelation('creator', $creator);
        $letter->setRelation('disposisis', collect([$disposition]));

        $row = (new PersuratanRegisterReportService())->incomingRows(collect([$letter]))->first();

        $this->assertSame('KP - Kepegawaian', $row['classification']);
        $this->assertSame('Dinaikkan', $row['status']);
        $this->assertSame('Berkas tersedia', $row['file_status']);
        $this->assertSame('Operator Surat', $row['creator']);
    }

    public function test_outgoing_rows_include_external_destination_and_completion_status(): void
    {
        $creator = new User(['name' => 'Pembuat Surat']);
        $letter = new SuratKeluar([
            'nomor_surat' => '735/SEK.W31-A/PL1.2.7/VI/2026',
            'opsi_penerima' => 'external',
            'penerima_external' => 'KPPN Manokwari',
            'perihal' => 'Laporan pelaksanaan kegiatan',
            'tanggal_surat' => '2026-06-30',
            'status' => 'lengkap',
        ]);
        $letter->setRelation('creator', $creator);

        $row = (new PersuratanRegisterReportService())->outgoingRows(collect([$letter]))->first();

        $this->assertSame('KPPN Manokwari', $row['recipient']);
        $this->assertSame('Selesai', $row['status']);
        $this->assertSame('Berkas tersedia', $row['file_status']);
    }

    public function test_pdf_template_and_report_control_render_required_content(): void
    {
        $rows = collect([[
            'number' => 1,
            'letter_number' => '100/KPTA.W31-A/HM1.1/VI/2026',
            'recipient' => 'Internal',
            'date' => '2026-06-30',
            'subject' => 'Rapat koordinasi',
            'status' => 'Selesai',
            'file_status' => 'Berkas tersedia',
            'creator' => 'Super Admin',
        ]]);

        $pdfHtml = view('persuratan.reports.register', [
            'type' => 'keluar',
            'title' => 'REGISTER SURAT KELUAR',
            'rows' => $rows,
            'periodLabel' => 'BULAN JUNI TAHUN 2026',
            'startDate' => Carbon::parse('2026-06-01'),
            'endDate' => Carbon::parse('2026-06-30'),
        ])->render();
        $controlHtml = view('persuratan._report-control', [
            'modalId' => 'reportTestModal',
            'action' => '/surat-keluar/laporan/pdf',
            'title' => 'Cetak Laporan Surat Keluar',
        ])->render();

        $this->assertStringContainsString('REGISTER SURAT KELUAR', $pdfHtml);
        $this->assertStringContainsString('PENGADILAN TINGGI AGAMA PAPUA BARAT', $pdfHtml);
        $this->assertStringContainsString('BULAN JUNI TAHUN 2026', $pdfHtml);
        $this->assertStringContainsString('name="tanggal_mulai"', $controlHtml);
        $this->assertStringContainsString('name="tanggal_selesai"', $controlHtml);
        $this->assertStringContainsString('target="_blank"', $controlHtml);
    }
}
