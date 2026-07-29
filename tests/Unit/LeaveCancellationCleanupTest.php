<?php

namespace Tests\Unit;

use App\LeaveRequest;
use App\LeaveType;
use App\Role;
use App\Services\DocumentQrCodeService;
use App\Services\IntegratedCalendarService;
use App\Services\LeaveDocumentService;
use App\Services\PdfVerificationService;
use App\SuratKeluar;
use App\SuratKeluarCalendarEvent;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class LeaveCancellationCleanupTest extends TestCase
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
            $table->string('kode')->nullable();
            $table->string('nama')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('atasan_langsung_id')->nullable();
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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->nullable();
            $table->string('letter_number')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('leave_type_id');
            $table->string('status');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('purpose')->nullable();
            $table->string('leave_address')->nullable();
            $table->string('unit_snapshot')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
        Schema::create('surat_keluars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_source_id')->nullable();
            $table->string('nomor_surat')->unique();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
        Schema::create('surat_keluar_penerima', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('surat_keluar_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
        Schema::create('surat_keluar_calendar_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('surat_keluar_id')->unique();
            $table->string('type');
            $table->date('start_date');
            $table->timestamps();
        });
        Schema::create('pdf_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('module');
            $table->unsignedBigInteger('document_id');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('pdf_verifications');
        Schema::dropIfExists('surat_keluar_calendar_events');
        Schema::dropIfExists('surat_keluar_penerima');
        Schema::dropIfExists('surat_keluars');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('user_jabatan_delegations');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('jabatans');
        Schema::dropIfExists('units');
        Mockery::close();

        parent::tearDown();
    }

    public function test_cancellation_cleanup_removes_generated_letter_and_calendar_event(): void
    {
        [$user, $leaveType] = $this->baseData();
        $number = '901/KPTA.W31-A/KP5.3/VII/2026';
        $leaveRequest = $this->leaveRequest($user, $leaveType, $number, LeaveRequest::STATUS_SUBMITTED);
        $suratKeluar = $this->suratKeluar($number);

        SuratKeluarCalendarEvent::create([
            'surat_keluar_id' => $suratKeluar->id,
            'type' => 'cuti',
            'start_date' => '2026-07-28',
        ]);

        $this->documentService()->removeCancelledLetterArtifacts($leaveRequest);

        $this->assertNull($leaveRequest->fresh()->letter_number);
        $this->assertDatabaseMissing('surat_keluars', ['id' => $suratKeluar->id]);
        $this->assertDatabaseMissing('surat_keluar_calendar_events', ['surat_keluar_id' => $suratKeluar->id]);
    }

    public function test_satker_cancellation_clears_manual_number_without_deleting_an_existing_letter(): void
    {
        [$user, $leaveType] = $this->baseData();
        $role = Role::create(['name' => 'satker', 'display_name' => 'Satuan Kerja']);
        $user->roles()->attach($role->id);
        $number = '123/KPA/W25-A/KP5.3/07/2026';
        $leaveRequest = $this->leaveRequest($user, $leaveType, $number, LeaveRequest::STATUS_SUBMITTED);
        $suratKeluar = $this->suratKeluar($number);
        $calendarEvent = SuratKeluarCalendarEvent::create([
            'surat_keluar_id' => $suratKeluar->id,
            'type' => 'cuti',
            'start_date' => '2026-07-28',
        ]);

        $this->documentService()->removeCancelledLetterArtifacts($leaveRequest);

        $this->assertNull($leaveRequest->fresh()->letter_number);
        $this->assertDatabaseHas('surat_keluars', ['id' => $suratKeluar->id, 'nomor_surat' => $number]);
        $this->assertDatabaseMissing('surat_keluar_calendar_events', ['id' => $calendarEvent->id]);
    }

    public function test_cancelled_leave_is_not_returned_by_integrated_calendar(): void
    {
        [$user, $leaveType] = $this->baseData();
        $active = $this->leaveRequest($user, $leaveType, '902/KPTA.W31-A/KP5.3/VII/2026', LeaveRequest::STATUS_APPROVED);
        $cancelled = $this->leaveRequest($user, $leaveType, null, LeaveRequest::STATUS_CANCELLED);
        $user->setRelation('roles', collect());
        $user->setRelation('activeJabatanDelegations', collect());

        $result = (new IntegratedCalendarService())->build($user, [
            'start' => '2026-07-01',
            'end' => '2026-07-31',
            'modules' => ['cuti'],
        ]);
        $eventIds = collect($result['events'])->pluck('id');

        $this->assertTrue($eventIds->contains('cuti-' . $active->id));
        $this->assertFalse($eventIds->contains('cuti-' . $cancelled->id));
    }

    public function test_cancelled_leave_letter_calendar_event_is_not_returned(): void
    {
        [$user, $leaveType] = $this->baseData();
        $role = Role::create(['name' => 'super_admin', 'display_name' => 'Super Admin']);
        $user->roles()->attach($role->id);

        $number = '903/KPTA.W31-A/KP5.3/VII/2026';
        $cancelled = $this->leaveRequest($user, $leaveType, $number, LeaveRequest::STATUS_CANCELLED);
        $cancelled->cancelled_at = now();
        $cancelled->save();
        $suratKeluar = $this->suratKeluar($number);
        $calendarEvent = SuratKeluarCalendarEvent::create([
            'surat_keluar_id' => $suratKeluar->id,
            'type' => 'cuti',
            'start_date' => '2026-07-28',
        ]);

        $result = (new IntegratedCalendarService())->build($user->fresh(), [
            'start' => '2026-07-01',
            'end' => '2026-07-31',
            'modules' => ['cuti'],
        ]);
        $eventIds = collect($result['events'])->pluck('id');

        $this->assertFalse($eventIds->contains('cuti-' . $cancelled->id));
        $this->assertFalse($eventIds->contains('surat-keluar-calendar-' . $calendarEvent->id));
    }

    protected function baseData(): array
    {
        $user = User::create(['name' => 'Pegawai', 'unit_id' => null]);
        $leaveType = LeaveType::create(['code' => 'CT', 'name' => 'Cuti Tahunan']);

        return [$user, $leaveType];
    }

    protected function leaveRequest(User $user, LeaveType $leaveType, $number, $status): LeaveRequest
    {
        return LeaveRequest::create([
            'request_number' => 'CUTI-' . uniqid(),
            'letter_number' => $number,
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'status' => $status,
            'start_date' => '2026-07-28',
            'end_date' => '2026-07-30',
            'purpose' => 'Keperluan keluarga',
            'leave_address' => 'Manokwari',
        ]);
    }

    protected function suratKeluar($number): SuratKeluar
    {
        $suratKeluar = new SuratKeluar();
        $suratKeluar->forceFill([
            'nomor_surat' => $number,
            'file_path' => null,
        ]);
        $suratKeluar->save();

        return $suratKeluar;
    }

    protected function documentService(): LeaveDocumentService
    {
        return new LeaveDocumentService(
            Mockery::mock(DocumentQrCodeService::class),
            Mockery::mock(PdfVerificationService::class)
        );
    }
}
