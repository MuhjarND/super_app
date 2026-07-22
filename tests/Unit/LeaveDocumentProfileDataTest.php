<?php

namespace Tests\Unit;

use App\Services\DocumentQrCodeService;
use App\Services\LeaveDocumentService;
use App\Services\PdfVerificationService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class LeaveDocumentProfileDataTest extends TestCase
{
    public function test_work_period_uses_year_and_month_format()
    {
        $service = new LeaveDocumentService(
            $this->createMock(DocumentQrCodeService::class),
            $this->createMock(PdfVerificationService::class)
        );
        $method = new ReflectionMethod($service, 'buildWorkPeriodText');
        $method->setAccessible(true);

        $result = $method->invoke(
            $service,
            Carbon::parse('2020-05-10'),
            Carbon::parse('2026-07-22')
        );

        $this->assertSame('6 Tahun 2 Bulan', $result);
    }

    public function test_work_period_is_empty_when_start_date_is_unavailable()
    {
        $service = new LeaveDocumentService(
            $this->createMock(DocumentQrCodeService::class),
            $this->createMock(PdfVerificationService::class)
        );
        $method = new ReflectionMethod($service, 'buildWorkPeriodText');
        $method->setAccessible(true);

        $this->assertSame('-', $method->invoke($service, null, Carbon::parse('2026-07-22')));
    }
}
