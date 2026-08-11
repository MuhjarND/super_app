<?php

namespace Tests\Unit;

use App\Http\Controllers\RapatController;
use App\Rapat;
use App\RapatApproval;
use App\Services\ActivityAuditService;
use App\Services\RapatApprovalService;
use App\Services\RapatDocumentService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class RapatApprovalEditingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('jabatans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('jabatan_id')->nullable();
            $table->timestamps();
        });

        Schema::create('rapats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approver_1_id')->nullable();
            $table->unsignedBigInteger('approver_2_id')->nullable();
            $table->string('status');
            $table->timestamp('participant_notified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rapat_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapat_id');
            $table->unsignedInteger('step_order');
            $table->unsignedBigInteger('approver_id');
            $table->string('approver_name_snapshot');
            $table->string('approver_jabatan_snapshot')->nullable();
            $table->string('status');
            $table->text('catatan')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('signature_mime')->nullable();
            $table->unsignedBigInteger('signature_size')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rapat_approval_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapat_id');
            $table->unsignedBigInteger('rapat_approval_id')->nullable();
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->unsignedInteger('step_order');
            $table->string('approver_name_snapshot');
            $table->string('approver_jabatan_snapshot')->nullable();
            $table->string('action');
            $table->text('catatan')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('rapat_approval_histories');
        Schema::dropIfExists('rapat_approvals');
        Schema::dropIfExists('rapats');
        Schema::dropIfExists('users');
        Schema::dropIfExists('jabatans');
        Mockery::close();

        parent::tearDown();
    }

    public function test_changing_approver_on_approved_meeting_restarts_approval(): void
    {
        $jabatanId = DB::table('jabatans')->insertGetId([
            'nama' => 'Pejabat Approval',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $parafApproverId = $this->createUser('Approver Paraf', $jabatanId);
        $oldApproverId = $this->createUser('Approver Lama', $jabatanId);
        $newApproverId = $this->createUser('Approver Baru', $jabatanId);

        $rapat = Rapat::create([
            'approver_1_id' => $oldApproverId,
            'approver_2_id' => $parafApproverId,
            'status' => 'disetujui',
        ]);
        RapatApproval::create([
            'rapat_id' => $rapat->id,
            'step_order' => 1,
            'approver_id' => $parafApproverId,
            'approver_name_snapshot' => 'Approver Paraf',
            'approver_jabatan_snapshot' => 'Pejabat Approval',
            'status' => 'approved',
            'acted_at' => now(),
        ]);
        RapatApproval::create([
            'rapat_id' => $rapat->id,
            'step_order' => 2,
            'approver_id' => $oldApproverId,
            'approver_name_snapshot' => 'Approver Lama',
            'approver_jabatan_snapshot' => 'Pejabat Approval',
            'status' => 'approved',
            'acted_at' => now(),
        ]);

        $rapat->update(['approver_1_id' => $newApproverId]);
        $this->approvalService()->syncWorkflow($rapat->fresh(), 'disetujui', false, true);

        $approvals = $rapat->approvals()->orderBy('step_order')->get();
        $this->assertSame($parafApproverId, (int) $approvals[0]->approver_id);
        $this->assertSame('pending', $approvals[0]->status);
        $this->assertNull($approvals[0]->acted_at);
        $this->assertSame($newApproverId, (int) $approvals[1]->approver_id);
        $this->assertSame('waiting', $approvals[1]->status);
        $this->assertNull($approvals[1]->acted_at);
        $this->assertSame('pending_approval', $rapat->fresh()->status);
    }

    public function test_controller_detects_changed_approval_assignment(): void
    {
        $rapat = new Rapat([
            'approver_1_id' => 10,
            'approver_2_id' => 20,
        ]);

        $controller = (new ReflectionClass(RapatController::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(RapatController::class, 'approvalAssignmentChanged');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller, $rapat, [
            'approver_1_id' => 10,
            'approver_2_id' => 20,
        ]));
        $this->assertTrue($method->invoke($controller, $rapat, [
            'approver_1_id' => 11,
            'approver_2_id' => 20,
        ]));
    }

    protected function createUser($name, $jabatanId)
    {
        return DB::table('users')->insertGetId([
            'name' => $name,
            'jabatan_id' => $jabatanId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function approvalService()
    {
        return new RapatApprovalService(
            Mockery::mock(WhatsAppNotificationService::class),
            Mockery::mock(RapatDocumentService::class),
            Mockery::mock(ActivityAuditService::class)
        );
    }
}
