<?php

namespace Tests\Unit;

use App\Http\Controllers\RapatController;
use App\Http\Requests\StoreRapatRequest;
use App\Rapat;
use App\Services\DocumentQrCodeService;
use App\Services\PdfVerificationService;
use App\Services\RapatDocumentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Mockery;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class RapatExternalInvitationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_advanced_form_contains_external_option_and_destination(): void
    {
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
            'approvers' => collect(),
        ])->render();

        $this->assertStringContainsString('name="is_external"', $html);
        $this->assertStringContainsString('name="tujuan_external"', $html);
        $this->assertStringContainsString('Tujuan Surat External', $html);
    }

    public function test_external_fields_are_stored_in_rapat_payload(): void
    {
        $request = Request::create('/rapat', 'POST', [
            'is_external' => '1',
            'tujuan_external' => 'Kepala Kantor Wilayah Kementerian Agama Provinsi Papua Barat',
        ]);
        $data = [
            'judul' => 'Rapat External',
            'kategori_surat_kode_id' => 1,
            'nomenklatur_jabatan' => 'sekretaris',
            'tanggal' => '2026-08-03',
            'waktu_mulai' => '09:00',
            'tempat' => 'Aula PTA Papua Barat',
            'is_external' => true,
            'tujuan_external' => 'Kepala Kantor Wilayah Kementerian Agama Provinsi Papua Barat',
        ];

        $controller = (new ReflectionClass(RapatController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(RapatController::class, 'payloadFromRequest');
        $method->setAccessible(true);
        $existingRapat = new Rapat(['kategori_rapat_id' => 9]);
        $payload = $method->invoke($controller, $request, $data, $existingRapat);

        $this->assertTrue($payload['is_external']);
        $this->assertSame(
            'Kepala Kantor Wilayah Kementerian Agama Provinsi Papua Barat',
            $payload['tujuan_external']
        );
    }

    public function test_external_destination_is_required_when_option_is_enabled(): void
    {
        $rules = (new StoreRapatRequest())->rules();

        $this->assertContains('required_if:is_external,1', $rules['tujuan_external']);
        $this->assertContains('max:255', $rules['tujuan_external']);
    }

    public function test_pdf_uses_separate_external_and_satker_destinations(): void
    {
        $rapat = new Rapat([
            'judul' => 'Rapat Koordinasi',
            'tanggal' => '2026-08-03',
            'waktu_mulai' => '09:00:00',
            'tempat' => 'Aula PTA Papua Barat',
            'is_external' => true,
            'tujuan_external' => 'Kepala Kantor Wilayah Kementerian Agama Provinsi Papua Barat',
            'bersama_satker' => true,
            'tujuan_surat' => 'Ketua Pengadilan Agama se-wilayah Papua Barat',
        ]);
        $rapat->forceFill(['created_at' => Carbon::parse('2026-08-03 09:00:00')]);
        $rapat->setRelation('pesertas', collect());
        $rapat->setRelation('approvals', collect());
        $rapat->setRelation('approver1', null);
        $rapat->setRelation('approver2', null);
        $rapat->setRelation('creator', null);
        $rapat->setRelation('kategoriSuratKode', null);
        $rapat->setRelation('suratKeluar', null);

        $service = new RapatDocumentService(
            Mockery::mock(DocumentQrCodeService::class),
            Mockery::mock(PdfVerificationService::class)
        );

        $externalData = $service->buildPdfViewData($rapat, false, null, 'internal');
        $satkerData = $service->buildPdfViewData($rapat, false, null, 'satker');

        $this->assertTrue($externalData['tujuanManual']);
        $this->assertSame($rapat->tujuan_external, $externalData['tujuanSurat']);
        $this->assertTrue($satkerData['tujuanManual']);
        $this->assertSame($rapat->tujuan_surat, $satkerData['tujuanSurat']);
    }
}
