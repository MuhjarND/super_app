<?php

namespace Tests\Unit;

use App\Services\DocumentQrCodeService;
use App\Services\PdfVerificationService;
use App\Services\RapatDocumentService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RapatInvitationNumberTest extends TestCase
{
    public function test_invitation_marker_is_added_before_classification_code()
    {
        $number = $this->formatInvitationNumber('326/SEK.PTA.W31-A/KU1.1.1/VIII/2026');

        $this->assertSame('326/SEK.PTA.W31-A/UND.KU1.1.1/VIII/2026', $number);
    }

    public function test_existing_invitation_marker_is_not_duplicated()
    {
        $number = $this->formatInvitationNumber('326/SEK.PTA.W31-A/UND.KU1.1.1/VIII/2026');

        $this->assertSame('326/SEK.PTA.W31-A/UND.KU1.1.1/VIII/2026', $number);
    }

    protected function formatInvitationNumber($number)
    {
        $service = new RapatDocumentService(
            $this->createMock(DocumentQrCodeService::class),
            $this->createMock(PdfVerificationService::class)
        );
        $method = new ReflectionMethod($service, 'withInvitationClassificationPrefix');
        $method->setAccessible(true);

        return $method->invoke($service, $number);
    }
}
