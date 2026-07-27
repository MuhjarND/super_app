<?php

namespace Tests\Unit;

use App\Http\Controllers\LeaveReportController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class SatkerLeaveBalanceManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->boolean('status_aktif_pegawai')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name');
            $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('units');

        parent::tearDown();
    }

    public function test_satker_user_is_available_for_leave_balance_management(): void
    {
        $satkerRoleId = DB::table('roles')->insertGetId([
            'name' => 'satker',
            'display_name' => 'Satuan Kerja',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $satkerUserId = DB::table('users')->insertGetId([
            'name' => 'Pegawai Satuan Kerja',
            'email' => 'satker@example.test',
            'password' => 'test',
            'status_aktif_pegawai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('role_user')->insert([
            'user_id' => $satkerUserId,
            'role_id' => $satkerRoleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $method = new ReflectionMethod(LeaveReportController::class, 'balanceUsersQuery');
        $method->setAccessible(true);
        $query = $method->invoke(app(LeaveReportController::class));

        $this->assertTrue($query->whereKey($satkerUserId)->exists());
    }
}
