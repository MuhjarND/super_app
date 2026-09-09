<?php

namespace Tests\Feature;

use App\Services\DocumentShareLinkService;
use App\SuratKeluar;
use App\SuratMasuk;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicDocumentShareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('surat_masuks');
        Schema::create('surat_masuks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nomor_surat');
            $table->string('perihal');
            $table->date('tanggal_surat')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
        Schema::dropIfExists('surat_keluar_approvals');
        Schema::dropIfExists('surat_keluars');
        Schema::create('surat_keluars', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nomor_surat');
            $table->string('perihal');
            $table->date('tanggal_surat')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
        Schema::create('surat_keluar_approvals', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('surat_keluar_id');
            $table->string('template_slug')->nullable();
            $table->string('status')->default('pending');
            $table->json('field_values')->nullable();
            $table->timestamps();
        });
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('surat_keluar_approvals');
        Schema::dropIfExists('surat_keluars');
        Schema::dropIfExists('surat_masuks');
        parent::tearDown();
    }

    public function test_short_incoming_letter_link_opens_file_without_login(): void
    {
        Storage::disk('public')->put('surat-masuk/contoh.pdf', '%PDF-1.4 contoh');
        $surat = SuratMasuk::create([
            'nomor_surat' => '001/KPTA.W31-A/VIII/2026',
            'perihal' => 'Undangan Rapat',
            'tanggal_surat' => '2026-08-26',
            'file_path' => 'surat-masuk/contoh.pdf',
        ]);
        $url = app(DocumentShareLinkService::class)->incomingUrl($surat);

        $this->assertRegExp('#/s/[A-Za-z0-9_-]{23}$#', $url);
        $response = $this->get($url);

        $response->assertOk();
        $this->assertStringContainsString(
            '001 - Undangan Rapat.pdf',
            (string) $response->headers->get('Content-Disposition')
        );
    }

    public function test_short_outgoing_letter_link_opens_file_without_login(): void
    {
        Storage::disk('public')->put('surat-keluar/contoh.pdf', '%PDF-1.4 contoh');
        $surat = SuratKeluar::create([
            'nomor_surat' => '022/KPTA.W31-A/VIII/2026',
            'perihal' => 'Pemberitahuan Kegiatan',
            'tanggal_surat' => '2026-08-26',
            'file_path' => 'surat-keluar/contoh.pdf',
            'status' => 'lengkap',
        ]);
        $url = app(DocumentShareLinkService::class)->outgoingUrl($surat);

        $this->assertRegExp('#/s/[A-Za-z0-9_-]{23}$#', $url);
        $response = $this->get($url);

        $response->assertOk();
        $this->assertStringContainsString(
            '022 - Pemberitahuan Kegiatan.pdf',
            (string) $response->headers->get('Content-Disposition')
        );
    }
}
