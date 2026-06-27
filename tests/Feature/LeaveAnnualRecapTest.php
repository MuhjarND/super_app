<?php

namespace Tests\Feature;

use App\LeaveBalance;
use App\LeaveType;
use App\Services\LeaveBalanceService;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LeaveAnnualRecapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
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
            $table->unique(['user_id', 'leave_type_id', 'year']);
        });
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->date('start_date');
            $table->string('status');
            $table->unsignedInteger('approved_days')->default(0);
            $table->unsignedInteger('requested_days')->default(0);
            $table->unsignedInteger('workday_count')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function testAnnualRecapIsCreatedAndUpdatedForTheSameEmployeeAndYear()
    {
        $user = User::create([
            'name' => 'Pegawai Uji',
            'email' => 'pegawai@example.test',
            'password' => bcrypt('password'),
        ]);
        LeaveType::create([
            'code' => LeaveType::CODE_TAHUNAN,
            'name' => 'Cuti Tahunan',
            'requires_balance' => true,
        ]);
        $service = app(LeaveBalanceService::class);

        $service->recordAnnualRecap($user, 2026, 6, 0, 8, $user);
        $service->recordAnnualRecap($user, 2026, 4, 2, 3, $user);

        $balance = LeaveBalance::first();
        $this->assertSame(1, LeaveBalance::count());
        $this->assertSame(12, $balance->entitlement);
        $this->assertSame(4, $balance->carry_forward);
        $this->assertSame(3, $balance->used_days);
        $this->assertSame(13, $balance->remaining_balance);
        $this->assertSame(4, data_get($balance->meta_json, 'annual_recap.carry_forward_by_year.2025'));
        $this->assertSame(0, data_get($balance->meta_json, 'annual_recap.carry_forward_by_year.2024'));
        $this->assertSame($user->id, data_get($balance->meta_json, 'annual_recap.input_by'));
    }

    public function testAnnualRecapIgnoresTwoYearCarryWithoutConsecutiveUnusedMarker()
    {
        $user = User::create([
            'name' => 'Pegawai Contoh Sekma',
            'email' => 'pegawai-contoh-sekma@example.test',
            'password' => bcrypt('password'),
        ]);
        LeaveType::create([
            'code' => LeaveType::CODE_TAHUNAN,
            'name' => 'Cuti Tahunan',
            'requires_balance' => true,
        ]);
        $service = app(LeaveBalanceService::class);

        $service->recordAnnualRecap($user, 2019, 6, 6, 0, $user);

        $balance = LeaveBalance::first();
        $this->assertSame(12, $balance->entitlement);
        $this->assertSame(6, $balance->carry_forward);
        $this->assertSame(18, $balance->remaining_balance);
        $this->assertSame(6, data_get($balance->meta_json, 'annual_recap.carry_forward_by_year.2018'));
        $this->assertSame(0, data_get($balance->meta_json, 'annual_recap.carry_forward_by_year.2017'));
        $this->assertFalse(data_get($balance->meta_json, 'annual_recap.unused_two_consecutive_years'));
    }

    public function testAnnualRecapAllowsTwoYearCarryOnlyWhenAnnualLeaveWasUnusedForTwoConsecutiveYears()
    {
        $user = User::create([
            'name' => 'Pegawai Uji Dua Tahun',
            'email' => 'pegawai-dua-tahun@example.test',
            'password' => bcrypt('password'),
        ]);
        LeaveType::create([
            'code' => LeaveType::CODE_TAHUNAN,
            'name' => 'Cuti Tahunan',
            'requires_balance' => true,
        ]);
        $service = app(LeaveBalanceService::class);

        $service->recordAnnualRecap($user, 2026, 6, 6, 0, $user, true);

        $balance = LeaveBalance::first();
        $this->assertSame(12, $balance->entitlement);
        $this->assertSame(12, $balance->carry_forward);
        $this->assertSame(24, $balance->remaining_balance);
        $this->assertSame(6, data_get($balance->meta_json, 'annual_recap.carry_forward_by_year.2025'));
        $this->assertSame(6, data_get($balance->meta_json, 'annual_recap.carry_forward_by_year.2024'));
        $this->assertTrue(data_get($balance->meta_json, 'annual_recap.unused_two_consecutive_years'));
    }

    public function testAnnualBalanceWithoutHistoryDoesNotReceiveAutomaticCarryForward()
    {
        $user = User::create([
            'name' => 'Pegawai Tanpa Histori',
            'email' => 'pegawai-tanpa-histori@example.test',
            'password' => bcrypt('password'),
        ]);
        $leaveType = LeaveType::create([
            'code' => LeaveType::CODE_TAHUNAN,
            'name' => 'Cuti Tahunan',
            'requires_balance' => true,
        ]);
        $service = app(LeaveBalanceService::class);

        $snapshot = $service->getBalanceSnapshot($user, $leaveType, 2026);

        $this->assertSame(12, $snapshot['entitlement']);
        $this->assertSame(0, $snapshot['carry_forward']);
        $this->assertSame(12, $snapshot['remaining_balance']);
        $this->assertSame(0, $snapshot['carry_forward_by_year'][2025]);
        $this->assertSame(0, $snapshot['carry_forward_by_year'][2024]);
    }
}
