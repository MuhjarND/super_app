<?php

namespace Tests\Unit;

use App\LeaveRequest;
use App\Services\ActivityAuditService;
use App\Services\LeaveApprovalService;
use App\Services\LeaveBalanceService;
use App\Services\LeaveDocumentService;
use App\Services\LeaveNumberService;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class LeaveApprovalWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });
        Schema::create('jabatans', function (Blueprint $table) {
            $table->id();
            $table->string('kode');
            $table->string('nama');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('jabatan_id')->nullable();
            $table->unsignedBigInteger('atasan_langsung_id')->nullable();
            $table->unsignedBigInteger('pejabat_berwenang_id')->nullable();
            $table->unsignedInteger('hirarki')->nullable();
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
        Schema::create('user_jabatan_delegations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('jabatan_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('leave_delegations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delegator_id');
            $table->unsignedBigInteger('delegate_id');
            $table->string('scope');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('leave_delegations');
        Schema::dropIfExists('user_jabatan_delegations');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('jabatans');
        Schema::dropIfExists('units');
        Mockery::close();

        parent::tearDown();
    }

    public function test_admin_kepegawaian_is_the_mandatory_first_step(): void
    {
        $adminId = $this->createUser('Admin Kepegawaian');
        $officialId = $this->createUser('Pejabat Approval');
        $employeeId = $this->createUser('Pegawai', [
            'atasan_langsung_id' => $officialId,
            'pejabat_berwenang_id' => $officialId,
        ]);

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'admin_kepegawaian',
            'display_name' => 'Admin Kepegawaian',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('role_user')->insert([
            'user_id' => $adminId,
            'role_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = new LeaveRequest([
            'start_date' => now()->addWeek()->toDateString(),
            'is_abroad' => false,
        ]);
        $request->setRelation('user', User::findOrFail($employeeId));

        $steps = $this->service()->buildApprovalSteps($request);

        $this->assertSame(
            ['verifikator_dokumen', 'atasan_langsung', 'ppk'],
            array_column($steps, 'role_name')
        );
        $this->assertSame($adminId, (int) $steps[0]['approver_id']);
        $this->assertSame($officialId, (int) $steps[1]['approver_id']);
        $this->assertSame($officialId, (int) $steps[2]['approver_id']);
    }

    public function test_submission_chain_cannot_be_built_without_an_active_admin_kepegawaian(): void
    {
        $employeeId = $this->createUser('Pegawai');
        $request = new LeaveRequest([
            'start_date' => now()->addWeek()->toDateString(),
            'is_abroad' => false,
        ]);
        $request->setRelation('user', User::findOrFail($employeeId));

        try {
            $this->service()->buildApprovalSteps($request);
            $this->fail('Rantai approval seharusnya ditolak tanpa Admin Kepegawaian aktif.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Admin Kepegawaian aktif belum ditentukan. Tetapkan role Admin Kepegawaian pada salah satu user sebelum pengajuan cuti disubmit.',
                data_get($exception->errors(), 'approval.0')
            );
        }
    }

    protected function createUser($name, array $attributes = [])
    {
        return DB::table('users')->insertGetId(array_merge([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@example.test',
            'password' => 'test',
            'status_aktif_pegawai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    protected function service()
    {
        return new LeaveApprovalService(
            Mockery::mock(LeaveBalanceService::class),
            Mockery::mock(LeaveNumberService::class),
            Mockery::mock(LeaveDocumentService::class),
            Mockery::mock(ActivityAuditService::class)
        );
    }
}
