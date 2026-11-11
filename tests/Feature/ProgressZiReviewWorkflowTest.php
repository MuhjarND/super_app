<?php

namespace Tests\Feature;

use App\Notifications\ProgressZiWorkflowNotification;
use App\Services\ActivityAuditService;
use App\Services\ProgressZi\ZiActivityApprovalService;
use App\Services\WhatsAppNotificationService;
use App\User;
use App\ZiActivity;
use App\ZiActivityApproval;
use App\ZiArea;
use App\ZiPeriod;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProgressZiReviewWorkflowTest extends TestCase
{
    protected $sekretaris;
    protected $panitera;
    protected $requester;
    protected $activity;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->instance(ActivityAuditService::class, \Mockery::mock(ActivityAuditService::class)->shouldIgnoreMissing());
        $this->instance(WhatsAppNotificationService::class, \Mockery::mock(WhatsAppNotificationService::class)->shouldIgnoreMissing());
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->integer('hirarki')->nullable(); $t->unsignedBigInteger('jabatan_id')->nullable();
            $t->unsignedBigInteger('unit_id')->nullable(); $t->boolean('status_aktif_pegawai')->default(true);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('name'); });
        Schema::create('role_user', function (Blueprint $t) { $t->unsignedBigInteger('role_id'); $t->unsignedBigInteger('user_id'); });
        Schema::create('jabatans', function (Blueprint $t) { $t->id(); $t->string('kode'); $t->string('nama'); });
        Schema::create('user_jabatan_delegations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('jabatan_id'); $t->boolean('is_active')->default(true);
        });
        foreach ([
            '2026_03_25_000001_create_progress_zi_tables.php' => 'CreateProgressZiTables',
            '2026_03_25_000002_create_progress_zi_guideline_tables.php' => 'CreateProgressZiGuidelineTables',
            '2026_03_25_000003_add_guideline_sub_point_id_to_zi_activities_table.php' => 'AddGuidelineSubPointIdToZiActivitiesTable',
            '2026_03_25_000005_add_progress_zi_activity_review_fields.php' => 'AddProgressZiActivityReviewFields',
        ] as $file => $class) { require_once database_path('migrations/' . $file); (new $class)->up(); }
        Schema::create('zi_area_pic', function (Blueprint $t) { $t->unsignedBigInteger('zi_area_id'); $t->unsignedBigInteger('user_id'); $t->timestamps(); });
        $this->requester = $this->user('PIC', 'pegawai', 20);
        $this->sekretaris = $this->user('Sekretaris', 'sekretaris', 3);
        $this->panitera = $this->user('Panitera', 'panitera', 4);
        $area = ZiArea::create(['code' => 'AREA 1', 'name' => 'Manajemen Perubahan', 'pic_user_id' => $this->requester->id]);
        $period = ZiPeriod::create(['name' => 'ZI 2026', 'year' => 2026]);
        $this->activity = ZiActivity::create(['zi_area_id' => $area->id, 'zi_period_id' => $period->id, 'pic_user_id' => $this->requester->id, 'name' => 'Eviden Tim Kerja']);
        $this->activity->realizations()->create(['realization_date' => '2026-11-08', 'implementation_summary' => 'Pengumpulan'])
            ->evidences()->create(['title' => 'Eviden tersedia', 'evidence_type' => 'google_drive', 'status' => 'terhubung']);
        $this->actingAs($this->requester);
    }

    public function test_submission_notifies_both_reviewers_and_never_routes_to_ketua_or_admin()
    {
        $ketua = $this->user('Ketua', 'ketua', 1);
        $wakil = $this->user('Wakil', 'wakil_ketua', 2);
        $admin = $this->user('Admin', 'super_admin', 0);
        $wa = app(WhatsAppNotificationService::class);
        foreach ([$this->sekretaris, $this->panitera] as $reviewer) {
            $wa->shouldReceive('notifyProgressZiApprovalPending')->once()->with(
                \Mockery::type(ZiActivityApproval::class),
                \Mockery::on(function ($user) use ($reviewer) { return $user->id === $reviewer->id; })
            );
        }
        $this->post(route('progress-zi.activities.submit-review', $this->activity))->assertRedirect()->assertSessionHasNoErrors();
        $approval = ZiActivityApproval::firstOrFail();
        $this->assertSame($this->sekretaris->id, (int) $approval->approver_id);
        Notification::assertSentTo([$this->sekretaris, $this->panitera], ProgressZiWorkflowNotification::class);
        Notification::assertNotSentTo([$ketua, $wakil, $admin], ProgressZiWorkflowNotification::class);
    }

    public function test_panitera_can_approve_the_shared_request_and_is_recorded_as_the_actual_reviewer()
    {
        $approval = $this->pending();
        $this->actingAs($this->panitera)->post(route('progress-zi.approvals.approve', $approval), ['review_notes' => 'Sesuai'])->assertRedirect();
        $approval->refresh();
        $this->assertSame('approved', $approval->status);
        $this->assertSame($this->panitera->id, (int) $approval->approver_id);
        $this->assertSame('selesai', $this->activity->fresh()->status);
        $this->assertNotNull($approval->acted_at);
    }

    public function test_sekretaris_can_return_evidence_for_revision()
    {
        $approval = $this->pending();
        $this->actingAs($this->sekretaris)->post(route('progress-zi.approvals.reject', $approval), ['review_notes' => 'Lengkapi dokumen'])->assertRedirect();
        $this->assertSame('rejected', $approval->fresh()->status);
        $this->assertSame('perlu_perbaikan', $this->activity->fresh()->status);
        $this->assertSame('Lengkapi dokumen', $approval->fresh()->review_notes);
    }

    public function test_direct_evidence_review_uses_the_same_reviewer_restriction()
    {
        $evidence = $this->activity->realizations->first()->evidences->first();
        $data = ['status' => 'valid', 'review_decision' => 'approved', 'review_notes' => 'Sesuai'];
        $this->actingAs($this->user('Ketua', 'approval', 1))
            ->post(route('progress-zi.evidences.review', $evidence), $data)->assertForbidden();
        $this->assertSame(0, $evidence->reviews()->count());
        $this->actingAs($this->panitera)->post(route('progress-zi.evidences.review', $evidence), $data)
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($this->panitera->id, (int) $evidence->reviews()->firstOrFail()->reviewed_by);
    }

    /** @dataProvider forbiddenRoles */
    public function test_other_roles_cannot_review_even_if_they_are_the_old_assignee($role)
    {
        $user = $this->user('Tidak berwenang', $role, 1);
        $approval = $this->pending($user->id);
        $this->actingAs($user)->post(route('progress-zi.approvals.approve', $approval))->assertForbidden();
        $this->post(route('progress-zi.approvals.reject', $approval), ['review_notes' => 'Tidak boleh'])->assertForbidden();
        $this->assertSame('pending', $approval->fresh()->status);
    }

    public function forbiddenRoles()
    {
        return array_map(function ($role) { return [$role]; }, ['ketua', 'wakil_ketua', 'approval', 'admin', 'super_admin', 'kabag', 'kasubag', 'pegawai']);
    }

    public function test_a_second_decision_using_a_stale_request_is_rejected()
    {
        $approval = $this->pending();
        $stale = $approval->fresh();
        app(ZiActivityApprovalService::class)->approve($approval, $this->sekretaris, 'Disetujui');
        try {
            app(ZiActivityApprovalService::class)->reject($stale, $this->panitera, 'Keputusan kedua');
            $this->fail('A completed review must not be processed again.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
        $this->assertSame('approved', $approval->fresh()->status);
        $this->assertSame('Disetujui', $approval->fresh()->review_notes);
        $this->assertSame($this->sekretaris->id, (int) $approval->fresh()->approver_id);
    }

    public function test_submission_fails_clearly_when_no_eligible_active_reviewer_exists()
    {
        $this->sekretaris->update(['status_aktif_pegawai' => false]);
        $this->panitera->forceFill(['is_active' => false])->save();
        $this->user('Ketua', 'approval', 1);
        $this->actingAs($this->requester)->postJson(route('progress-zi.activities.submit-review', $this->activity))
            ->assertStatus(422)->assertJson(['message' => 'Belum ada Sekretaris atau Panitera aktif yang dapat menerima review eviden ZI.']);
        $this->assertSame(0, ZiActivityApproval::count());
    }

    public function test_structural_positions_and_active_delegations_are_eligible_without_the_special_role()
    {
        DB::table('jabatans')->insert([['id' => 1, 'kode' => 'SEK', 'nama' => 'Sekretaris'], ['id' => 2, 'kode' => 'PAN', 'nama' => 'Panitera']]);
        $structural = $this->user('Sekretaris jabatan', 'pegawai', 5);
        $structural->update(['jabatan_id' => 1]);
        $delegated = $this->user('Plt Panitera', 'pegawai', 6);
        DB::table('user_jabatan_delegations')->insert(['user_id' => $delegated->id, 'jabatan_id' => 2, 'is_active' => true]);
        $reviewers = app(ZiActivityApprovalService::class)->reviewers();
        $this->assertTrue($reviewers->contains('id', $structural->id));
        $this->assertTrue($reviewers->contains('id', $delegated->id));
        $this->assertTrue($structural->fresh()->canVerifyProgressZi());
        $this->assertTrue($delegated->fresh()->canVerifyProgressZi());
        DB::table('user_jabatan_delegations')->where('user_id', $delegated->id)->update(['is_active' => false]);
        $this->assertFalse($delegated->fresh()->canVerifyProgressZi());
        $this->assertFalse(app(ZiActivityApprovalService::class)->reviewers()->contains('id', $delegated->id));
    }

    public function test_migration_redirects_only_pending_requests_and_preserves_completed_reviews()
    {
        $ketua = $this->user('Ketua', 'ketua', 1);
        $pending = $this->pending($ketua->id);
        $completed = $this->activity->approvals()->create(['approver_id' => $ketua->id, 'status' => 'approved', 'acted_at' => now(), 'review_notes' => 'Keputusan lama']);
        require_once database_path('migrations/2026_11_08_000001_reassign_pending_zi_reviews_to_sekretaris_panitera.php');
        (new \ReassignPendingZiReviewsToSekretarisPanitera)->up();
        $this->assertSame($this->sekretaris->id, (int) $pending->fresh()->approver_id);
        $this->assertSame($ketua->id, (int) $completed->fresh()->approver_id);
        $this->assertSame('Keputusan lama', $completed->fresh()->review_notes);
        $this->assertSame(0, app(ZiActivityApprovalService::class)->reassignPendingReviews());
        Notification::assertNothingSent();
    }

    public function test_both_reviewers_see_the_same_pending_queue_while_ketua_sees_none()
    {
        $this->pending();
        $method = new \ReflectionMethod(\App\Http\Controllers\ApprovalCenterController::class, 'resolvePendingCount');
        $method->setAccessible(true);
        $controller = app(\App\Http\Controllers\ApprovalCenterController::class);
        foreach ([$this->sekretaris, $this->panitera] as $user) {
            $this->actingAs($user);
            $this->assertSame(1, $method->invoke($controller, 'progress_zi'));
        }
        $this->actingAs($this->user('Ketua', 'ketua', 1));
        $this->assertSame(0, $method->invoke($controller, 'progress_zi'));
    }

    protected function pending($userId = null)
    {
        return $this->activity->approvals()->create(['approver_id' => $userId ?: $this->sekretaris->id, 'requested_by' => $this->requester->id, 'status' => 'pending', 'requested_at' => now()]);
    }

    protected function user($name, $role, $hierarchy)
    {
        $user = User::create(['name' => $name, 'hirarki' => $hierarchy, 'status_aktif_pegawai' => true]);
        $roleId = DB::table('roles')->where('name', $role)->value('id') ?: DB::table('roles')->insertGetId(['name' => $role]);
        DB::table('role_user')->insert(['user_id' => $user->id, 'role_id' => $roleId]);
        return $user;
    }
}
