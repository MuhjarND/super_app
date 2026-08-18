<?php

namespace Tests\Unit;

use App\LeaveBalance;
use App\LeaveHoliday;
use App\LeaveRequest;
use App\LeaveType;
use App\Services\LeaveExistingRequestSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeaveExistingRequestSyncServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('leave_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->boolean('requires_balance')->default(false);
            $table->timestamps();
        });
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('requested_days')->default(0);
            $table->unsignedInteger('approved_days')->default(0);
            $table->unsignedInteger('workday_count')->default(0);
            $table->boolean('travel_leave_requested')->default(false);
            $table->boolean('travel_leave_granted')->default(false);
            $table->string('purpose')->default('Keperluan');
            $table->string('status', 30)->default('draft');
            $table->timestamps();
        });
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
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->unsignedInteger('year');
            $table->integer('opening_balance')->default(0);
            $table->integer('entitlement')->default(0);
            $table->integer('carry_forward')->default(0);
            $table->integer('adjustment_plus')->default(0);
            $table->integer('adjustment_minus')->default(0);
            $table->integer('used_days')->default(0);
            $table->integer('reserved_days')->default(0);
            $table->integer('remaining_balance')->default(0);
            $table->json('meta_json')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_holidays');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
        parent::tearDown();
    }

    public function test_verified_request_and_reserved_balance_are_corrected(): void
    {
        [$leaveType, $leaveRequest] = $this->createAnnualRequest(LeaveRequest::STATUS_VERIFIED, 4);
        $balance = $this->createBalance($leaveType, 0, 4);
        $this->createCollectiveLeave();

        $this->assertSame(1, app(LeaveExistingRequestSyncService::class)->syncAll());

        $leaveRequest->refresh();
        $balance->refresh();
        $this->assertSame(3, $leaveRequest->requested_days);
        $this->assertSame(3, $leaveRequest->approved_days);
        $this->assertSame(3, $leaveRequest->workday_count);
        $this->assertSame(3, $balance->reserved_days);
        $this->assertSame(9, $balance->remaining_balance);
    }

    public function test_approved_request_and_used_balance_are_corrected(): void
    {
        [$leaveType, $leaveRequest] = $this->createAnnualRequest(LeaveRequest::STATUS_APPROVED, 4);
        $balance = $this->createBalance($leaveType, 4, 0);
        $this->createCollectiveLeave();

        app(LeaveExistingRequestSyncService::class)->syncAll();

        $leaveRequest->refresh();
        $balance->refresh();
        $this->assertSame(3, $leaveRequest->requested_days);
        $this->assertSame(3, $balance->used_days);
        $this->assertSame(9, $balance->remaining_balance);
    }

    public function test_deleting_holiday_resynchronizes_old_request_back_to_full_period(): void
    {
        [$leaveType, $leaveRequest] = $this->createAnnualRequest(LeaveRequest::STATUS_SUBMITTED, 3);
        $balance = $this->createBalance($leaveType, 0, 3);
        $holiday = $this->createCollectiveLeave();
        $holiday->delete();

        $updated = app(LeaveExistingRequestSyncService::class)->syncOverlappingDates(['2026-08-25']);

        $this->assertSame(1, $updated);
        $this->assertSame(4, $leaveRequest->fresh()->requested_days);
        $this->assertSame(4, $balance->fresh()->reserved_days);
        $this->assertSame(8, $balance->fresh()->remaining_balance);
    }

    public function test_submitted_travel_leave_counts_travel_inside_total_and_outside_reserved_balance(): void
    {
        [$leaveType, $leaveRequest] = $this->createAnnualRequest(LeaveRequest::STATUS_VERIFIED, 4);
        $leaveRequest->travel_leave_requested = true;
        $leaveRequest->approved_days = 4;
        $leaveRequest->save();
        $balance = $this->createBalance($leaveType, 0, 3);
        $this->createCollectiveLeave();

        $this->assertSame(1, app(LeaveExistingRequestSyncService::class)->syncAll());

        $leaveRequest->refresh();
        $balance->refresh();
        $this->assertSame(3, $leaveRequest->requested_days);
        $this->assertSame(3, $leaveRequest->approved_days);
        $this->assertSame(2, $balance->reserved_days);
        $this->assertSame(10, $balance->remaining_balance);
    }

    protected function createAnnualRequest($status, $days)
    {
        $leaveType = LeaveType::create([
            'code' => LeaveType::CODE_TAHUNAN,
            'name' => 'Cuti Tahunan',
            'requires_balance' => true,
        ]);
        $leaveRequest = LeaveRequest::create([
            'user_id' => 10,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-08-24',
            'end_date' => '2026-08-27',
            'requested_days' => $days,
            'approved_days' => $days,
            'workday_count' => $days,
            'status' => $status,
        ]);

        return [$leaveType, $leaveRequest];
    }

    protected function createBalance(LeaveType $leaveType, $usedDays, $reservedDays)
    {
        return LeaveBalance::create([
            'user_id' => 10,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'entitlement' => 12,
            'used_days' => $usedDays,
            'reserved_days' => $reservedDays,
            'remaining_balance' => 12 - $usedDays - $reservedDays,
        ]);
    }

    protected function createCollectiveLeave()
    {
        return LeaveHoliday::create([
            'holiday_date' => '2026-08-25',
            'name' => 'Cuti Bersama',
            'category' => 'cuti_bersama',
            'is_collective_leave' => true,
            'is_active' => true,
        ]);
    }
}
