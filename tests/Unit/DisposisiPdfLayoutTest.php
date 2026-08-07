<?php

namespace Tests\Unit;

use App\Disposisi;
use App\Jabatan;
use App\SuratMasuk;
use App\User;
use Carbon\Carbon;
use Tests\TestCase;

class DisposisiPdfLayoutTest extends TestCase
{
    public function test_disposition_pdf_contains_letter_and_workflow_information()
    {
        $sender = new User(['name' => 'Ketua Pengujian']);
        $recipient = new User(['name' => 'Pejabat Tujuan']);
        $sourcePosition = new Jabatan(['nama' => 'Ketua']);
        $targetPosition = new Jabatan(['nama' => 'Sekretaris']);
        $letter = new SuratMasuk([
            'nomor_surat' => '100/KP/VIII/2026',
            'pengirim' => 'Mahkamah Agung RI',
            'perihal' => 'Pengujian lembar disposisi',
            'tanggal_surat' => '2026-08-07',
            'sifat' => 'biasa',
            'file_path' => 'surat-masuk/test.pdf',
        ]);
        $letter->forceFill(['created_at' => Carbon::parse('2026-08-07 08:00:00')]);
        $disposition = new Disposisi([
            'petunjuk' => 'Untuk diketahui',
            'catatan' => 'Mohon ditindaklanjuti.',
            'priority_level' => 'normal',
            'status' => 'pending',
        ]);
        $disposition->forceFill(['created_at' => Carbon::parse('2026-08-07 09:00:00')]);
        $disposition->setRelation('suratMasuk', $letter);
        $disposition->setRelation('dariUser', $sender);
        $disposition->setRelation('kepadaUser', $recipient);
        $disposition->setRelation('dariJabatan', $sourcePosition);
        $disposition->setRelation('kepadaJabatan', $targetPosition);

        $html = view('surat-masuk.pdf.disposisi', [
            'disposisi' => $disposition,
            'suratMasuk' => $letter,
            'logoData' => null,
            'petunjukOptions' => Disposisi::getPetunjukOptions(),
            'pdfVerification' => ['qr' => 'data:image/png;base64,testing'],
            'pdfVerificationInFlow' => true,
            'pdfVerificationQrSize' => 40,
        ])->render();

        $normalizedHtml = str_replace('&#8203;', '', $html);

        $this->assertStringContainsString('LEMBAR DISPOSISI', $normalizedHtml);
        $this->assertStringContainsString('100/KP/VIII/2026', $normalizedHtml);
        $this->assertStringContainsString('Pejabat Tujuan', $html);
        $this->assertStringContainsString('Untuk diketahui', $html);
        $this->assertStringContainsString('Mohon ditindaklanjuti.', $html);
        $this->assertStringContainsString('Validasi PDF', $html);
    }
}
