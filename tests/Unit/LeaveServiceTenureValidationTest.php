<?php

namespace Tests\Unit;

use App\LeaveRequest;
use App\LeaveType;
use App\Services\LeaveBalanceService;
use App\Services\LeaveValidationService;
use App\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Tests\TestCase;

class LeaveServiceTenureValidationTest extends TestCase
{
    public function test_manual_employment_baseline_is_used_for_service_year_requirement(): void
    {
        $user = new User([
            'tmt_pns' => '2026-08-01',
            'masa_kerja_tahun' => 5,
            'masa_kerja_bulan' => 0,
            'masa_kerja_acuan' => '2026-01-01',
        ]);
        $leaveType = new LeaveType(['code' => LeaveType::CODE_TAHUNAN, 'service_years_required' => 1]);

        $this->validateServiceYears(new LeaveRequest(), $user, $leaveType, '2026-09-09');
        $this->addToAssertionCount(1);
    }

    public function test_tmt_pns_fallback_rejects_employee_before_required_anniversary(): void
    {
        $user = new User(['tmt_pns' => '2025-09-10']);
        $leaveType = new LeaveType(['code' => LeaveType::CODE_TAHUNAN, 'service_years_required' => 1]);

        try {
            $this->validateServiceYears(new LeaveRequest(), $user, $leaveType, '2026-09-09');
            $this->fail('Validasi masa kerja seharusnya menolak pengajuan ini.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString(
                'masa kerja saat mulai cuti tercatat 11 bulan',
                $exception->errors()['start_date'][0] ?? ''
            );
        }
    }

    protected function validateServiceYears(LeaveRequest $request, User $user, LeaveType $leaveType, $startDate): void
    {
        $service = new LeaveValidationService($this->createMock(LeaveBalanceService::class));
        $method = new ReflectionMethod($service, 'validateServiceYears');
        $method->setAccessible(true);
        $method->invoke($service, $request, $user, $leaveType, Carbon::parse($startDate));
    }
}
