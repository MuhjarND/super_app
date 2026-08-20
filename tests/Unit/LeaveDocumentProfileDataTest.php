<?php

namespace Tests\Unit;

use App\LeaveBalance;
use App\LeaveRequest;
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

    public function test_annual_leave_note_uses_days_from_current_request()
    {
        $service = new LeaveDocumentService(
            $this->createMock(DocumentQrCodeService::class),
            $this->createMock(PdfVerificationService::class)
        );
        $method = new ReflectionMethod($service, 'buildAnnualLeaveNote');
        $method->setAccessible(true);

        $balance = new LeaveBalance();
        $balance->setRawAttributes([
            'leave_type_id' => 1,
            'used_days' => 13,
            'remaining_balance' => 13,
        ]);
        $request = new LeaveRequest();
        $request->setRawAttributes([
            'leave_type_id' => 1,
            'start_date' => '2026-07-22',
            'requested_days' => 3,
            'approved_days' => 3,
        ]);

        $this->assertSame(
            'Diambil 3 hari sisa 13',
            $method->invoke($service, $balance, $request, 2026)
        );
    }

    public function test_annual_leave_note_keeps_historical_year_summary()
    {
        $service = new LeaveDocumentService(
            $this->createMock(DocumentQrCodeService::class),
            $this->createMock(PdfVerificationService::class)
        );
        $method = new ReflectionMethod($service, 'buildAnnualLeaveNote');
        $method->setAccessible(true);

        $balance = new LeaveBalance();
        $balance->setRawAttributes([
            'leave_type_id' => 1,
            'used_days' => 4,
            'remaining_balance' => 8,
        ]);
        $request = new LeaveRequest();
        $request->setRawAttributes([
            'leave_type_id' => 1,
            'start_date' => '2026-07-22',
            'requested_days' => 3,
        ]);

        $this->assertSame(
            'Diambil 4 hari sisa 8',
            $method->invoke($service, $balance, $request, 2025)
        );
    }

    public function test_annual_leave_note_uses_current_balance_after_request()
    {
        $service = new LeaveDocumentService(
            $this->createMock(DocumentQrCodeService::class),
            $this->createMock(PdfVerificationService::class)
        );
        $method = new ReflectionMethod($service, 'buildAnnualLeaveNote');
        $method->setAccessible(true);

        $balance = new LeaveBalance();
        $balance->setRawAttributes([
            'leave_type_id' => 1,
            'remaining_balance' => 8,
        ]);
        $request = new LeaveRequest();
        $request->setRawAttributes([
            'leave_type_id' => 1,
            'start_date' => '2026-08-10',
            'requested_days' => 4,
        ]);

        $this->assertSame(
            'Diambil 4 hari sisa 8',
            $method->invoke($service, $balance, $request, 2026, 8)
        );
    }

    public function test_annual_leave_balance_uses_latest_remaining_balance()
    {
        $service = new LeaveDocumentService(
            $this->createMock(DocumentQrCodeService::class),
            $this->createMock(PdfVerificationService::class)
        );
        $method = new ReflectionMethod($service, 'resolveCurrentAnnualLeaveBalance');
        $method->setAccessible(true);

        $balance = new LeaveBalance();
        $balance->setRawAttributes([
            'leave_type_id' => 1,
            'remaining_balance' => 8,
        ]);
        $request = new LeaveRequest();
        $request->setRawAttributes([
            'leave_type_id' => 1,
            'start_date' => '2026-08-10',
            'requested_days' => 4,
            'approved_days' => 4,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $this->assertSame(8, $method->invoke($service, $balance, $request));
    }

    public function test_annual_leave_balance_ignores_stale_submission_snapshot()
    {
        $service = new LeaveDocumentService(
            $this->createMock(DocumentQrCodeService::class),
            $this->createMock(PdfVerificationService::class)
        );
        $method = new ReflectionMethod($service, 'resolveCurrentAnnualLeaveBalance');
        $method->setAccessible(true);

        $balance = new LeaveBalance();
        $balance->setRawAttributes([
            'leave_type_id' => 1,
            'remaining_balance' => 4,
        ]);
        $request = new LeaveRequest();
        $request->setRawAttributes([
            'leave_type_id' => 1,
            'start_date' => '2026-08-10',
            'requested_days' => 4,
            'approved_days' => 4,
            'status' => LeaveRequest::STATUS_APPROVED,
            'approver_chain_snapshot' => json_encode([[
                'leave_balance_snapshot' => [
                    'leave_type_id' => 1,
                    'year' => 2026,
                    'remaining_balance' => 12,
                ],
            ]]),
        ]);

        $this->assertSame(4, $method->invoke($service, $balance, $request));
    }

    public function test_annual_leave_remaining_column_shows_balance_before_current_request()
    {
        $service = new LeaveDocumentService(
            $this->createMock(DocumentQrCodeService::class),
            $this->createMock(PdfVerificationService::class)
        );
        $method = new ReflectionMethod($service, 'resolveAnnualLeaveBalanceBeforeRequest');
        $method->setAccessible(true);

        $balance = new LeaveBalance();
        $balance->setRawAttributes([
            'leave_type_id' => 1,
            'remaining_balance' => 19,
        ]);
        $request = new LeaveRequest();
        $request->setRawAttributes([
            'leave_type_id' => 1,
            'start_date' => '2026-08-20',
            'requested_days' => 3,
            'approved_days' => 3,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        $this->assertSame(22, $method->invoke($service, $balance, $request, 2026, 19));
    }
}
