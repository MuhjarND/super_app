<?php

namespace Tests\Unit;

use App\LeaveType;
use App\Services\LeaveBalanceService;
use App\Services\LeaveValidationService;
use Mockery;
use PHPUnit\Framework\TestCase;

class LeaveDayCalculationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sick_leave_counts_weekends_as_calendar_days(): void
    {
        $leaveType = new LeaveType(['code' => LeaveType::CODE_SAKIT]);

        $this->assertSame(
            4,
            $this->service()->countLeaveDays('2026-08-07', '2026-08-10', $leaveType)
        );
    }

    public function test_important_reason_leave_counts_every_calendar_day(): void
    {
        $leaveType = new LeaveType(['code' => LeaveType::CODE_ALASAN_PENTING]);

        $this->assertSame(
            7,
            $this->service()->countLeaveDays('2026-08-10', '2026-08-16', $leaveType)
        );
    }

    protected function service()
    {
        return new LeaveValidationService(Mockery::mock(LeaveBalanceService::class));
    }
}
