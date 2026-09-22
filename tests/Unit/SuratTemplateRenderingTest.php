<?php

namespace Tests\Unit;

use App\Http\Controllers\SuratTemplateController;
use App\Support\SuratTemplateCatalog;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SuratTemplateRenderingTest extends TestCase
{
    public function testStructuredApprovalMetadataIsNotPassedToHtmlspecialchars()
    {
        $controller = new SuratTemplateController();
        $method = new ReflectionMethod($controller, 'renderTemplateBody');
        $method->setAccessible(true);

        $template = SuratTemplateCatalog::find('surat-keterangan-perbaikan-presensi');
        $body = '<p>{{nama_pegawai}}</p><p>{{tanggal_presensi}}</p><p>{{penanda_tangan}}</p>';
        $fields = [
            'nama_pegawai' => 'Pegawai Contoh',
            'tanggal_presensi' => '2026-09-22',
            'penanda_tangan' => [
                'id' => 1,
                'nama' => 'Pimpinan Contoh',
            ],
        ];

        $rendered = $method->invoke($controller, $template, $body, $fields);

        $this->assertStringContainsString('Pegawai Contoh', $rendered);
        $this->assertStringContainsString('22 September 2026', $rendered);
        $this->assertStringContainsString('<p>-</p>', $rendered);
    }
}
