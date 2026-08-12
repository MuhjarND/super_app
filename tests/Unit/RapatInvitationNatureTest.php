<?php

namespace Tests\Unit;

use App\Http\Controllers\RapatController;
use App\Http\Requests\StoreRapatRequest;
use App\Rapat;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class RapatInvitationNatureTest extends TestCase
{
    public function test_meeting_form_provides_letter_nature_selection(): void
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

        $this->assertStringContainsString('name="sifat_surat"', $html);
        $this->assertStringContainsString('<option value="biasa" selected>Biasa</option>', $html);
    }

    public function test_letter_nature_is_validated_and_stored_in_payload(): void
    {
        $rules = (new StoreRapatRequest())->rules();
        $this->assertContains('in:biasa,penting,segera,sangat_segera,rahasia', $rules['sifat_surat']);

        $request = Request::create('/rapat', 'POST', ['sifat_surat' => 'segera']);
        $data = [
            'judul' => 'Rapat Segera',
            'kategori_surat_kode_id' => 1,
            'nomenklatur_jabatan' => 'sekretaris',
            'sifat_surat' => 'segera',
            'tanggal' => '2026-08-12',
            'waktu_mulai' => '09:00',
            'tempat' => 'Ruang Rapat',
        ];

        $controller = (new ReflectionClass(RapatController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(RapatController::class, 'payloadFromRequest');
        $method->setAccessible(true);
        $payload = $method->invoke($controller, $request, $data, new Rapat(['kategori_rapat_id' => 9]));

        $this->assertSame('segera', $payload['sifat_surat']);
    }

    public function test_letter_nature_defaults_to_biasa(): void
    {
        $request = Request::create('/rapat', 'POST');
        $data = [
            'judul' => 'Rapat Biasa',
            'kategori_surat_kode_id' => 1,
            'nomenklatur_jabatan' => 'sekretaris',
            'tanggal' => '2026-08-12',
            'waktu_mulai' => '09:00',
            'tempat' => 'Ruang Rapat',
        ];

        $controller = (new ReflectionClass(RapatController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(RapatController::class, 'payloadFromRequest');
        $method->setAccessible(true);
        $payload = $method->invoke($controller, $request, $data, new Rapat(['kategori_rapat_id' => 9]));

        $this->assertSame('biasa', $payload['sifat_surat']);
    }
}
