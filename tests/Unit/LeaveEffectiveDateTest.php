<?php

namespace Tests\Unit;

use App\LeaveHoliday;
use App\LeaveType;
use App\Services\LeaveDateService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeaveEffectiveDateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('leave_holidays', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('holiday_date')->unique();
            $table->string('name');
            $table->string('category', 30);
            $table->boolean('impacts_balance')->default(false);
            $table->unsignedBigInteger('leave_type_id')->nullable();
            $table->unsignedInteger('deduction_days')->default(0);
            $table->boolean('is_national_holiday')->default(false);
            $table->boolean('is_collective_leave')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('leave_holidays');
        parent::tearDown();
    }

    public function test_collective_leave_is_removed_from_workday_count_and_date_label(): void
    {
        LeaveHoliday::create([
            'holiday_date' => '2026-08-25',
            'name' => 'Cuti Bersama',
            'category' => 'cuti_bersama',
            'is_collective_leave' => true,
            'is_active' => true,
        ]);

        $leaveType = new LeaveType(['code' => LeaveType::CODE_TAHUNAN]);
        $service = app(LeaveDateService::class);

        $this->assertSame(3, $service->countEffectiveDates('2026-08-24', '2026-08-27', $leaveType));
        $this->assertSame('24, 26-27 Agustus 2026', $service->formatEffectiveDates('2026-08-24', '2026-08-27', $leaveType));
    }

    public function test_collective_leave_is_also_removed_from_calendar_based_leave(): void
    {
        LeaveHoliday::create([
            'holiday_date' => '2026-08-25',
            'name' => 'Cuti Bersama',
            'category' => 'cuti_bersama',
            'is_collective_leave' => true,
            'is_active' => true,
        ]);

        $leaveType = new LeaveType(['code' => LeaveType::CODE_SAKIT]);

        $this->assertSame(
            3,
            app(LeaveDateService::class)->countEffectiveDates('2026-08-24', '2026-08-27', $leaveType)
        );
    }

    public function test_collective_leave_is_global_even_when_old_data_has_a_leave_type_id(): void
    {
        LeaveHoliday::create([
            'holiday_date' => '2026-08-25',
            'name' => 'Cuti Bersama',
            'category' => 'cuti_bersama',
            'leave_type_id' => 999,
            'is_collective_leave' => true,
            'is_active' => true,
        ]);

        $leaveType = new LeaveType(['code' => LeaveType::CODE_TAHUNAN]);

        $this->assertSame(
            '24, 26-27 Agustus 2026',
            app(LeaveDateService::class)->formatEffectiveDates('2026-08-24', '2026-08-27', $leaveType)
        );
    }

    public function test_inactive_collective_leave_does_not_change_the_period(): void
    {
        LeaveHoliday::create([
            'holiday_date' => '2026-08-25',
            'name' => 'Cuti Bersama Nonaktif',
            'category' => 'cuti_bersama',
            'is_collective_leave' => true,
            'is_active' => false,
        ]);

        $leaveType = new LeaveType(['code' => LeaveType::CODE_TAHUNAN]);

        $this->assertSame(
            4,
            app(LeaveDateService::class)->countEffectiveDates('2026-08-24', '2026-08-27', $leaveType)
        );
    }
}
