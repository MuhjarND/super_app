<?php

namespace Tests\Unit;

use App\Rapat;
use App\Services\DocumentQrCodeService;
use App\Services\PdfVerificationService;
use App\Services\RapatDocumentService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RapatInvitationOpeningParagraphTest extends TestCase
{
    public function test_custom_opening_replaces_the_complete_standard_paragraph()
    {
        $customOpening = 'Berdasarkan hasil evaluasi pimpinan, dengan ini kami mengundang Saudara untuk menghadiri rapat yang akan dilaksanakan pada:';
        $rapat = new Rapat([
            'judul' => 'Rapat Monitoring dan Evaluasi',
            'detail_tambahan' => $customOpening,
        ]);

        $this->assertSame($customOpening, $this->openingParagraph($rapat));
        $this->assertStringNotContainsString('Dalam rangka pelaksanaan', $this->openingParagraph($rapat));
    }

    public function test_standard_opening_is_used_when_custom_opening_is_empty()
    {
        $rapat = new Rapat([
            'judul' => 'Rapat Monitoring dan Evaluasi',
            'detail_tambahan' => '',
        ]);

        $paragraph = $this->openingParagraph($rapat);

        $this->assertStringContainsString('Dalam rangka pelaksanaan Rapat Monitoring dan Evaluasi', $paragraph);
        $this->assertStringContainsString('yang akan dilaksanakan pada:', $paragraph);
    }

    protected function openingParagraph(Rapat $rapat)
    {
        $service = new RapatDocumentService(
            $this->createMock(DocumentQrCodeService::class),
            $this->createMock(PdfVerificationService::class)
        );
        $method = new ReflectionMethod($service, 'buildOpeningParagraph');
        $method->setAccessible(true);

        return $method->invoke($service, $rapat);
    }
}
