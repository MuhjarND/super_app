<?php

namespace Tests\Unit;

use App\Http\Controllers\RapatController;
use App\Http\Requests\StoreRapatRequest;
use App\Rapat;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class RapatInvitationClosingTest extends TestCase
{
    public function test_form_provides_optional_required_closing_field(): void
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
            'satkers' => collect(),
            'approvers' => collect(),
        ])->render();

        $this->assertStringContainsString('name="include_penutup_undangan"', $html);
        $this->assertStringContainsString('name="penutup_undangan"', $html);
        $this->assertStringContainsString('Isi Penutup Undangan', $html);

        $rules = (new StoreRapatRequest())->rules();
        $this->assertContains('required_if:include_penutup_undangan,1', $rules['penutup_undangan']);
    }

    public function test_custom_closing_is_stored_only_when_enabled(): void
    {
        $closing = 'Mohon hadir 15 menit sebelum kegiatan dimulai.';
        $request = Request::create('/rapat', 'POST', [
            'include_penutup_undangan' => '1',
            'penutup_undangan' => $closing,
        ]);
        $data = [
            'judul' => 'Rapat Pengujian Penutup',
            'kategori_surat_kode_id' => 1,
            'nomenklatur_jabatan' => 'sekretaris',
            'tanggal' => '2026-08-12',
            'waktu_mulai' => '09:00',
            'tempat' => 'Ruang Rapat',
            'include_penutup_undangan' => true,
            'penutup_undangan' => $closing,
        ];

        $controller = (new ReflectionClass(RapatController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(RapatController::class, 'payloadFromRequest');
        $method->setAccessible(true);
        $payload = $method->invoke($controller, $request, $data, new Rapat(['kategori_rapat_id' => 9]));

        $this->assertSame($closing, $payload['penutup_undangan']);
    }
}
