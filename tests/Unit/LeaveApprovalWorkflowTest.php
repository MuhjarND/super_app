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
            $table->string('delegation_type')->nullable();
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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->boolean('requires_balance')->default(false);
            $table->timestamps();
        });
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->nullable();
            $table->string('letter_number')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->json('approver_chain_snapshot')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('requested_days')->default(0);
            $table->unsignedInteger('approved_days')->default(0);
            $table->unsignedInteger('workday_count')->default(0);
            $table->string('purpose')->default('Cuti');
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->string('applicant_signature_path')->nullable();
            $table->string('applicant_signature_mime')->nullable();
            $table->unsignedBigInteger('applicant_signature_size')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('leave_request_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('leave_request_id');
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });
        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('leave_request_id');
            $table->unsignedInteger('step_no');
            $table->string('role_name');
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->string('status')->default('waiting');
            $table->string('action')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('signature_mime')->nullable();
            $table->unsignedBigInteger('signature_size')->nullable();
            $table->text('note')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('leave_approvals');
        Schema::dropIfExists('leave_request_documents');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
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

    public function test_kasubag_kepegawaian_is_the_mandatory_first_step(): void
    {
        $kasubagPositionId = DB::table('jabatans')->insertGetId([
            'kode' => 'KASUBAG_KEPEG',
            'nama' => 'Kasubag Kepegawaian',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $kasubagId = $this->createUser('Kasubag Kepegawaian', [
            'jabatan_id' => $kasubagPositionId,
        ]);
        $officialId = $this->createUser('Pejabat Approval');
        $employeeId = $this->createUser('Pegawai', [
            'atasan_langsung_id' => $officialId,
            'pejabat_berwenang_id' => $officialId,
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
        $this->assertSame($kasubagId, (int) $steps[0]['approver_id']);
        $this->assertSame($officialId, (int) $steps[1]['approver_id']);
        $this->assertSame($officialId, (int) $steps[2]['approver_id']);
    }

    public function test_active_plt_replaces_the_definitive_kasubag_kepegawaian(): void
    {
        $kasubagPositionId = DB::table('jabatans')->insertGetId([
            'kode' => 'KASUBAG_KEPEG',
            'nama' => 'Kasubag Kepegawaian',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createUser('Kasubag Definitif', ['jabatan_id' => $kasubagPositionId]);
        $pltId = $this->createUser('PLT Kasubag Kepegawaian');
        DB::table('user_jabatan_delegations')->insert([
            'user_id' => $pltId,
            'jabatan_id' => $kasubagPositionId,
            'delegation_type' => 'plt',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $officialId = $this->createUser('Pejabat Approval');
        $employeeId = $this->createUser('Pegawai', [
            'atasan_langsung_id' => $officialId,
            'pejabat_berwenang_id' => $officialId,
        ]);
        $request = new LeaveRequest([
            'start_date' => now()->addWeek()->toDateString(),
            'is_abroad' => false,
        ]);
        $request->setRelation('user', User::findOrFail($employeeId));

        $steps = $this->service()->buildApprovalSteps($request);

        $this->assertSame($pltId, (int) $steps[0]['approver_id']);
    }

    public function test_submission_chain_cannot_be_built_without_kasubag_or_active_plt(): void
    {
        $officialId = $this->createUser('Pejabat Approval');
        $employeeId = $this->createUser('Pegawai', [
            'atasan_langsung_id' => $officialId,
            'pejabat_berwenang_id' => $officialId,
        ]);
        $request = new LeaveRequest([
            'start_date' => now()->addWeek()->toDateString(),
            'is_abroad' => false,
        ]);
        $request->setRelation('user', User::findOrFail($employeeId));

        try {
            $this->service()->buildApprovalSteps($request);
            $this->fail('Rantai approval seharusnya ditolak tanpa Kasubag Kepegawaian atau PLT aktif.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Kasubag Kepegawaian atau PLT Kasubag Kepegawaian aktif belum ditentukan.',
                data_get($exception->errors(), 'approval.0')
            );
        }
    }

    public function test_submission_requires_direct_supervisor_and_authorized_official(): void
    {
        $kasubagPositionId = DB::table('jabatans')->insertGetId([
            'kode' => 'KASUBAG_KEPEG',
            'nama' => 'Kasubag Kepegawaian',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createUser('Kasubag Kepegawaian', ['jabatan_id' => $kasubagPositionId]);
        $employeeId = $this->createUser('Pegawai');
        $request = new LeaveRequest([
            'start_date' => now()->addWeek()->toDateString(),
            'is_abroad' => false,
        ]);
        $request->setRelation('user', User::findOrFail($employeeId));

        $this->expectException(ValidationException::class);
        $this->service()->buildApprovalSteps($request);
    }

    public function test_submission_requires_an_explicit_authorized_leave_official(): void
    {
        $kasubagPositionId = DB::table('jabatans')->insertGetId([
            'kode' => 'KASUBAG_KEPEG',
            'nama' => 'Kasubag Kepegawaian',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createUser('Kasubag Kepegawaian', ['jabatan_id' => $kasubagPositionId]);
        $supervisorId = $this->createUser('Atasan Langsung');
        $employeeId = $this->createUser('Pegawai', [
            'atasan_langsung_id' => $supervisorId,
        ]);
        $request = new LeaveRequest([
            'start_date' => now()->addWeek()->toDateString(),
            'is_abroad' => false,
        ]);
        $request->setRelation('user', User::findOrFail($employeeId));

        try {
            $this->service()->buildApprovalSteps($request);
            $this->fail('Rantai approval seharusnya ditolak tanpa pejabat berwenang cuti.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Pejabat yang berwenang memberikan cuti belum ditentukan pada data user.',
                data_get($exception->errors(), 'approval.0')
            );
        }
    }

    public function test_same_supervisor_and_authorized_official_are_approved_with_one_action(): void
    {
        $this->assertOneActionApprovesBothOfficialRoles(false);
    }

    public function test_satker_request_with_same_official_is_approved_with_one_action(): void
    {
        $this->assertOneActionApprovesBothOfficialRoles(true);
    }

    public function test_satker_submission_does_not_consume_internal_leave_number_sequence(): void
    {
        $kasubagPositionId = DB::table('jabatans')->insertGetId([
            'kode' => 'KASUBAG_KEPEG',
            'nama' => 'Kasubag Kepegawaian',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createUser('Kasubag Kepegawaian', ['jabatan_id' => $kasubagPositionId]);
        $officialId = $this->createUser('Pejabat Satker');
        $employeeId = $this->createUser('Pegawai Satker', [
            'atasan_langsung_id' => $officialId,
            'pejabat_berwenang_id' => $officialId,
        ]);
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'satker',
            'display_name' => 'Satuan Kerja',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('role_user')->insert([
            'user_id' => $employeeId,
            'role_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $leaveTypeId = DB::table('leave_types')->insertGetId([
            'code' => 'CT',
            'name' => 'Cuti Tahunan',
            'requires_balance' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $leaveRequest = LeaveRequest::create([
            'letter_number' => '123/KPA/W25-A/KP5.3/07/2026',
            'user_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
            'purpose' => 'Cuti',
            'status' => LeaveRequest::STATUS_DRAFT,
        ]);

        $balanceService = Mockery::mock(LeaveBalanceService::class);
        $balanceService->shouldReceive('reserve')->once();
        $numberService = Mockery::mock(LeaveNumberService::class);
        $numberService->shouldNotReceive('next');
        $service = new LeaveApprovalService(
            $balanceService,
            $numberService,
            Mockery::mock(LeaveDocumentService::class),
            Mockery::mock(ActivityAuditService::class)
        );

        $service->submit($leaveRequest);

        $leaveRequest->refresh();
        $this->assertNull($leaveRequest->request_number);
        $this->assertSame('123/KPA/W25-A/KP5.3/07/2026', $leaveRequest->letter_number);
        $this->assertCount(3, $leaveRequest->approvals);
    }

    protected function assertOneActionApprovesBothOfficialRoles($isSatker): void
    {
        $officialId = $this->createUser('Pejabat Bersama');
        $employeeId = $this->createUser($isSatker ? 'Pegawai Satuan Kerja' : 'Pegawai Internal', [
            'atasan_langsung_id' => $officialId,
            'pejabat_berwenang_id' => $officialId,
        ]);

        if ($isSatker) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'satker',
                'display_name' => 'Satuan Kerja',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('role_user')->insert([
                'user_id' => $employeeId,
                'role_id' => $roleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->assertTrue(User::findOrFail($employeeId)->isSatker());
        }

        $leaveTypeId = DB::table('leave_types')->insertGetId([
            'code' => 'CT',
            'name' => 'Cuti Tahunan',
            'requires_balance' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $leaveRequestId = DB::table('leave_requests')->insertGetId([
            'user_id' => $employeeId,
            'leave_type_id' => $leaveTypeId,
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeek()->toDateString(),
            'status' => LeaveRequest::STATUS_VERIFIED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $supervisorApprovalId = DB::table('leave_approvals')->insertGetId([
            'leave_request_id' => $leaveRequestId,
            'step_no' => 2,
            'role_name' => 'atasan_langsung',
            'approver_id' => $officialId,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('leave_approvals')->insert([
            'leave_request_id' => $leaveRequestId,
            'step_no' => 3,
            'role_name' => 'ppk',
            'approver_id' => $officialId,
            'status' => 'waiting',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $balanceService = Mockery::mock(LeaveBalanceService::class);
        $balanceService->shouldReceive('consume')->once();
        $documentService = Mockery::mock(LeaveDocumentService::class);
        $documentService->shouldReceive('ensureLetterNumber')->once();
        $documentService->shouldReceive('syncSuratKeluar')->once();
        $auditService = Mockery::mock(ActivityAuditService::class);
        $auditService->shouldReceive('log')->twice();
        $service = new LeaveApprovalService(
            $balanceService,
            Mockery::mock(LeaveNumberService::class),
            $documentService,
            $auditService
        );

        $service->approve(
            \App\LeaveApproval::findOrFail($supervisorApprovalId),
            User::findOrFail($officialId),
            'Disetujui'
        );

        $approvals = \App\LeaveApproval::where('leave_request_id', $leaveRequestId)
            ->orderBy('step_no')
            ->get();

        $this->assertCount(2, $approvals);
        $this->assertSame(['approved', 'approved'], $approvals->pluck('status')->all());
        $this->assertNotSame($approvals[0]->id, $approvals[1]->id);
        $this->assertSame($approvals[0]->acted_at->timestamp, $approvals[1]->acted_at->timestamp);
        $this->assertTrue((bool) data_get($approvals[1]->meta_json, 'approved_automatically'));
        $this->assertSame($approvals[0]->id, (int) data_get($approvals[1]->meta_json, 'approved_with_step_id'));
        $this->assertSame(LeaveRequest::STATUS_APPROVED, LeaveRequest::findOrFail($leaveRequestId)->status);
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
