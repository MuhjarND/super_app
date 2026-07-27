<?php

namespace Tests\Unit;

use App\LeaveRequest;
use App\Policies\LeaveRequestPolicy;
use App\SuratKeluar;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DirectSupervisorVisibilityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('atasan_langsung_id')->nullable();
            $table->boolean('status_aktif_pegawai')->default(true);
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();
        });
        Schema::create('jabatans', function (Blueprint $table) {
            $table->id();
            $table->string('kode');
            $table->string('nama');
            $table->timestamps();
        });
        Schema::create('user_jabatan_delegations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('jabatan_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('surat_keluars', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat')->nullable();
            $table->string('perihal')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
        Schema::create('surat_keluar_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('surat_keluar_id');
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->string('template_slug')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
        Schema::create('surat_keluar_penerima', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('surat_keluar_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('surat_keluar_penerima');
        Schema::dropIfExists('surat_keluar_approvals');
        Schema::dropIfExists('surat_keluars');
        Schema::dropIfExists('user_jabatan_delegations');
        Schema::dropIfExists('jabatans');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_direct_supervisor_can_view_subordinate_leave_request(): void
    {
        [$supervisor, $subordinate] = $this->createSupervisorAndSubordinate();
        $leaveRequest = new LeaveRequest();
        $leaveRequest->forceFill(['user_id' => $subordinate->id]);
        $leaveRequest->setRelation('user', $subordinate);

        $this->assertTrue((new LeaveRequestPolicy())->view($supervisor, $leaveRequest));
        $this->assertTrue($supervisor->canAccessLeaveModule());
        $this->assertTrue($supervisor->canAccessSuratKeluarMenu());
    }

    public function test_supervisor_sees_only_subordinate_task_letters_as_related_outgoing_mail(): void
    {
        [$supervisor, $subordinate] = $this->createSupervisorAndSubordinate();
        $taskLetterId = DB::table('surat_keluars')->insertGetId([
            'nomor_surat' => '001/ST/VII/2026',
            'perihal' => 'Surat Tugas',
            'status' => 'draft',
            'created_by' => $subordinate->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $ordinaryLetterId = DB::table('surat_keluars')->insertGetId([
            'nomor_surat' => '002/SK/VII/2026',
            'perihal' => 'Surat Keluar Biasa',
            'status' => 'draft',
            'created_by' => $subordinate->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('surat_keluar_approvals')->insert([
            'surat_keluar_id' => $taskLetterId,
            'requested_by' => $subordinate->id,
            'template_slug' => 'surat-tugas',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $visibleIds = SuratKeluar::visibleTo($supervisor)->pluck('id')->all();

        $this->assertContains($taskLetterId, $visibleIds);
        $this->assertNotContains($ordinaryLetterId, $visibleIds);
        $this->assertTrue($supervisor->canViewSuratKeluar(SuratKeluar::findOrFail($taskLetterId)));
        $this->assertFalse($supervisor->canViewSuratKeluar(SuratKeluar::findOrFail($ordinaryLetterId)));
    }

    protected function createSupervisorAndSubordinate(): array
    {
        $supervisorId = DB::table('users')->insertGetId([
            'name' => 'Atasan',
            'status_aktif_pegawai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $subordinateId = DB::table('users')->insertGetId([
            'name' => 'Bawahan',
            'atasan_langsung_id' => $supervisorId,
            'status_aktif_pegawai' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [User::findOrFail($supervisorId), User::findOrFail($subordinateId)];
    }
}
