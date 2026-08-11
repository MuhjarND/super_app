<?php

namespace Tests\Unit;

use App\Services\UnifiedActionCenterService;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class UnifiedActionCenterOrphanedLeaveApprovalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('leave_request_id');
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->string('status');
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('leave_approvals');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('users');
        Mockery::close();

        parent::tearDown();
    }

    public function test_historical_items_ignore_approval_whose_leave_request_was_deleted(): void
    {
        DB::table('leave_approvals')->insert([
            'leave_request_id' => 404,
            'approver_id' => 10,
            'status' => 'approved',
            'acted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('canAccessLeaveModule')->once()->andReturn(true);
        $user->shouldReceive('effectiveAssignmentUserIds')->once()->andReturn([10]);

        $items = (new TestableOrphanedLeaveActionCenterService())->historicalLeaveItems($user);

        $this->assertCount(0, $items);
    }

    public function test_active_items_ignore_approval_whose_leave_request_was_deleted(): void
    {
        DB::table('leave_approvals')->insert([
            'leave_request_id' => 404,
            'approver_id' => 10,
            'status' => 'waiting',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('canAccessLeaveModule')->once()->andReturn(true);
        $user->shouldReceive('canAccessLeaveApproval')->once()->andReturn(true);
        $user->shouldReceive('effectiveAssignmentUserIds')->once()->andReturn([10]);

        $items = (new TestableOrphanedLeaveActionCenterService())->activeLeaveItems($user);

        $this->assertCount(0, $items);
    }
}

class TestableOrphanedLeaveActionCenterService extends UnifiedActionCenterService
{
    public function historicalLeaveItems(User $user)
    {
        return $this->buildHistoricalLeaveItems($user);
    }

    public function activeLeaveItems(User $user)
    {
        return $this->buildLeaveItems($user);
    }
}
