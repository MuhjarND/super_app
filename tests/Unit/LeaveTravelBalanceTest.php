<?php

namespace Tests\Unit;

use App\LeaveBalance;
use App\LeaveRequest;
use App\LeaveType;
use App\Services\LeaveBalanceService;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeaveTravelBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->boolean('requires_balance')->default(false);
            $table->timestamps();
        });
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
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
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_requested_travel_day_does_not_add_to_reserved_balance(): void
    {
        [$request, $balance] = $this->makeRequestAndBalance();
        $service = app(LeaveBalanceService::class);

        $service->reserve($request);
        $this->assertSame(3, (int) $balance->fresh()->reserved_days);
        $this->assertSame(9, (int) $balance->fresh()->remaining_balance);

        $request->travel_leave_granted = false;
        $service->consume($request);

        $this->assertSame(0, (int) $balance->fresh()->reserved_days);
        $this->assertSame(3, (int) $balance->fresh()->used_days);
        $this->assertSame(9, (int) $balance->fresh()->remaining_balance);
    }

    public function test_granted_travel_day_does_not_require_an_extra_balance_day(): void
    {
        [$request, $balance] = $this->makeRequestAndBalance();
        $service = app(LeaveBalanceService::class);

        $service->reserve($request);
        $request->travel_leave_granted = true;
        $service->consume($request);

        $this->assertSame(0, (int) $balance->fresh()->reserved_days);
        $this->assertSame(3, (int) $balance->fresh()->used_days);
        $this->assertSame(9, (int) $balance->fresh()->remaining_balance);
    }

    protected function makeRequestAndBalance()
    {
        $user = User::create(['name' => 'Pegawai']);
        $leaveType = LeaveType::create([
            'code' => LeaveType::CODE_TAHUNAN,
            'name' => 'Cuti Tahunan',
            'requires_balance' => true,
        ]);
        $balance = LeaveBalance::create([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => 2026,
            'entitlement' => 12,
            'remaining_balance' => 12,
            'meta_json' => ['annual_recap' => ['manual_carry_forward' => true]],
        ]);
        $request = new LeaveRequest([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-03',
            'requested_days' => 3,
            'workday_count' => 3,
            'travel_leave_requested' => true,
        ]);
        $request->setRelation('user', $user);
        $request->setRelation('leaveType', $leaveType);

        return [$request, $balance];
    }
}
