<?php

namespace Tests\Unit;

use App\Services\DocumentQrCodeService;
use Tests\TestCase;

class DocumentQrCodeServiceTest extends TestCase
{
    public function test_qr_code_contains_centered_application_logo()
    {
        $dataUri = app(DocumentQrCodeService::class)->dataUri('https://papeda.pta-papuabarat.go.id/verifikasi/test', 120);

        $this->assertStringStartsWith('data:image/svg+xml;base64,', $dataUri);

        $svg = base64_decode(substr($dataUri, strlen('data:image/svg+xml;base64,')));

        $this->assertStringContainsString('id="papeda-qr-logo"', $svg);
        $this->assertStringContainsString('data:image/png;base64,', $svg);
        $this->assertStringContainsString('viewBox="0 0 120 120"', $svg);
    }
}
