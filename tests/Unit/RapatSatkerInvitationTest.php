<?php

namespace Tests\Unit;

use App\Rapat;
use App\Services\DocumentQrCodeService;
use App\Services\PdfVerificationService;
use App\Services\RapatDocumentService;
use App\SuratKeluar;
use App\User;
use Carbon\Carbon;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class RapatSatkerInvitationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_form_provides_separate_satker_destination_selection(): void
    {
        $satker = new User(['name' => 'Pengadilan Agama Manokwari']);
        $satker->forceFill(['id' => 21]);
        $html = view('rapat.partials.form-modal', [
            'modalId' => 'createRapatModal',
            'formId' => 'createRapatForm',
            'title' => 'Tambah Rapat',
            'submitLabel' => 'Simpan',
            'action' => '/rapat',
            'method' => 'POST',
            'kategoriSuratOptions' => collect(),
            'participants' => collect(),
            'participantUnits' => collect(),
            'satkers' => collect([$satker]),
            'approvers' => collect(),
        ])->render();

        $this->assertStringContainsString('name="satker_ids[]"', $html);
        $this->assertStringContainsString('name="penerima_satker"', $html);
        $this->assertStringContainsString('Opsi Penerima Satker', $html);
        $this->assertStringContainsString('Pilih Semua Satker', $html);
        $this->assertStringContainsString('Pengadilan Agama Manokwari', $html);
    }

    public function test_all_satkers_create_one_collective_target(): void
    {
        $targets = $this->targets([
            $this->satker(1, 'Pengadilan Agama Manokwari'),
            $this->satker(2, 'Pengadilan Agama Sorong'),
            $this->satker(3, 'Pengadilan Agama Fakfak'),
            $this->satker(4, 'Pengadilan Agama Kaimana'),
        ], 4);

        $this->assertCount(1, $targets);
        $this->assertTrue($targets->first()['collective']);
        $this->assertSame('Seluruh Satker Sewilayah Hukum PTA Papua Barat', $targets->first()['destination']);
    }

    public function test_subset_creates_one_external_target_for_each_satker(): void
    {
        $targets = $this->targets([
            $this->satker(1, 'Pengadilan Agama Manokwari'),
            $this->satker(2, 'Pengadilan Agama Sorong'),
            $this->satker(3, 'Pengadilan Agama Kaimana'),
        ], 4);

        $this->assertCount(3, $targets);
        $this->assertSame(
            ['Pengadilan Agama Manokwari', 'Pengadilan Agama Sorong', 'Pengadilan Agama Kaimana'],
            $targets->pluck('destination')->all()
        );
        $this->assertFalse($targets->contains('collective', true));
    }

    public function test_satker_pdf_uses_target_letter_number_and_destination(): void
    {
        $rapat = new Rapat([
            'nomor_undangan' => 'nomor-utama',
            'judul' => 'Rapat Koordinasi Satker',
            'tanggal' => '2026-08-12',
            'waktu_mulai' => '09:00:00',
            'tempat' => 'Ruang Rapat',
            'bersama_satker' => true,
            'penerima_satker' => 'Ketua',
        ]);
        $rapat->forceFill(['created_at' => Carbon::parse('2026-08-12 08:00:00')]);
        $rapat->setRelation('pesertas', collect());
        $rapat->setRelation('approvals', collect());
        $rapat->setRelation('approver1', null);
        $rapat->setRelation('approver2', null);
        $rapat->setRelation('creator', null);
        $rapat->setRelation('kategoriSuratKode', null);
        $rapat->setRelation('suratKeluar', null);

        $letter = new SuratKeluar([
            'nomor_surat' => '102/KPTA.W31-A/UND.OT1.6/VIII/2026',
            'opsi_penerima' => 'external',
            'penerima_external' => 'Pengadilan Agama Sorong',
            'satker_id' => 2,
        ]);

        $data = $this->service()->buildPdfViewData($rapat, false, null, 'satker', $letter);

        $this->assertSame($letter->nomor_surat, $data['nomorUndangan']);
        $this->assertSame('Pengadilan Agama Sorong', $data['tujuanSurat']);
        $this->assertSame('Ketua', $data['penerimaSatker']);
        $this->assertTrue($data['tujuanManual']);

        $html = view('rapat.pdf.undangan', $data)->render();
        $this->assertStringContainsString('class="recipient-destination">Yth. Ketua Pengadilan Agama Sorong</div>', $html);
        $this->assertRegExp('/\.recipient-destination\s*\{[^}]*font-weight:\s*bold;/s', $html);
        $this->assertStringNotContainsString('Kepada Yth.', $html);
    }

    protected function targets(array $satkers, $allSatkerCount)
    {
        $method = new ReflectionMethod(RapatDocumentService::class, 'buildSatkerInvitationTargets');
        $method->setAccessible(true);

        return $method->invoke($this->service(), collect($satkers), $allSatkerCount);
    }

    protected function satker($id, $name)
    {
        $satker = new User(['name' => $name]);
        $satker->forceFill(['id' => $id]);
        return $satker;
    }

    protected function service()
    {
        return new RapatDocumentService(
            Mockery::mock(DocumentQrCodeService::class),
            Mockery::mock(PdfVerificationService::class)
        );
    }
}
