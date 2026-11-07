<?php

namespace Tests\Feature;

use App\Role;
use App\Rules\GoogleDriveUrl;
use App\Services\ProgressZi\ZiActivityApprovalService;
use App\Services\ProgressZi\ZiEvidenceBundleService;
use App\User;
use App\ZiActivity;
use App\ZiArea;
use App\ZiEvidence;
use App\ZiGuidelinePoint;
use App\ZiGuidelineSubPoint;
use App\ZiPeriod;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProgressZiDriveEvidenceTest extends TestCase
{
    protected $area;
    protected $period;
    protected $subPoint;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->integer('hirarki')->nullable(); $table->timestamps();
        });
        foreach ([
            '2026_03_25_000001_create_progress_zi_tables.php' => 'CreateProgressZiTables',
            '2026_03_25_000002_create_progress_zi_guideline_tables.php' => 'CreateProgressZiGuidelineTables',
            '2026_03_25_000003_add_guideline_sub_point_id_to_zi_activities_table.php' => 'AddGuidelineSubPointIdToZiActivitiesTable',
            '2026_03_25_000005_add_progress_zi_activity_review_fields.php' => 'AddProgressZiActivityReviewFields',
            '2026_11_07_000001_add_drive_url_to_zi_evidences.php' => 'AddDriveUrlToZiEvidences',
        ] as $file => $class) {
            require_once database_path('migrations/' . $file);
            (new $class)->up();
        }
        Schema::create('zi_area_pic', function (Blueprint $table) {
            $table->unsignedBigInteger('zi_area_id'); $table->unsignedBigInteger('user_id'); $table->timestamps();
        });
        Schema::table('zi_guideline_sub_points', function (Blueprint $table) { $table->string('evidence_guide_key')->nullable()->unique(); });
        $user = User::create(['name' => 'Admin QA']);
        $user->setRelation('roles', collect([new Role(['name' => 'admin'])]));
        $user->setRelation('jabatan', null);
        $user->setRelation('activeJabatanDelegations', collect());
        $this->actingAs($user);
        $this->area = ZiArea::create(['code' => 'I', 'name' => 'Manajemen Perubahan']);
        $this->period = ZiPeriod::create(['name' => 'ZI 2026', 'year' => 2026]);
        $point = ZiGuidelinePoint::create(['zi_area_id' => $this->area->id, 'code' => '1', 'title' => 'Tim Kerja']);
        $this->subPoint = ZiGuidelineSubPoint::create(['zi_guideline_point_id' => $point->id, 'code' => 'a', 'title' => 'Pembentukan Tim', 'evidence_guide_key' => 'row-9']);
    }

    public function test_activity_accepts_drive_link_without_storing_files_and_is_ready_for_review()
    {
        $activity = $this->activity();
        $url = 'https://drive.google.com/drive/folders/qa_folder?usp=sharing';
        $this->post(route('progress-zi.activities.evidences.store', $activity), ['drive_url' => $url, 'title' => 'SK Tim'])
            ->assertRedirect();
        $evidence = ZiEvidence::firstOrFail();
        $this->assertSame($url, $evidence->drive_url);
        $this->assertSame($url, $evidence->preview_url);
        $this->assertSame('Google Drive', $evidence->source_reference_label);
        $this->assertSame('terhubung', $evidence->status);
        $this->assertNull($evidence->file_path);
        $this->assertFalse($evidence->is_auto_linked);
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertTrue(app(ZiActivityApprovalService::class)->isReadyForReview($activity->fresh()));
    }

    public function test_sub_point_can_collect_drive_evidence_without_a_meeting()
    {
        $this->post(route('progress-zi.sub-points.evidences.store', $this->subPoint), [
            'zi_period_id' => $this->period->id, 'zi_area_id' => $this->area->id,
            'drive_url' => 'https://docs.google.com/document/d/qa_document/edit',
        ])->assertRedirect();
        $this->assertSame(1, ZiActivity::count());
        $this->assertSame('sedang_berjalan', ZiActivity::first()->status);
        $this->assertSame('Eviden Google Drive', ZiEvidence::first()->title);
        $this->assertSame($this->subPoint->id, (int) ZiActivity::first()->zi_guideline_sub_point_id);
    }

    public function test_collection_uses_the_fixed_guide_instead_of_manual_indicator_links()
    {
        $activity = $this->activity();
        $realization = $this->realization($activity);
        $indicator = $activity->indicators()->create(['name' => 'SK tersedia']);
        $foreignIndicator = $this->activity()->indicators()->create(['name' => 'Indikator lain']);
        $route = route('progress-zi.evidences.store', $realization);
        $this->postJson($route, ['drive_url' => 'https://drive.google.com/file/d/qa_file/view', 'indicator_ids' => [$foreignIndicator->id]])
            ->assertStatus(422)->assertJsonValidationErrors('indicator_ids');
        $this->assertSame(0, ZiEvidence::count());
        $this->post($route, ['drive_url' => 'https://drive.google.com/file/d/qa_file/view'])->assertRedirect();
        $this->assertSame([], ZiEvidence::first()->indicators->modelKeys());
    }

    public function test_missing_or_invalid_link_leaves_no_empty_activity_or_realization()
    {
        $route = route('progress-zi.sub-points.evidences.store', $this->subPoint);
        foreach ([null, 'https://example.com/file', 'javascript:alert(1)'] as $url) {
            $this->postJson($route, ['zi_period_id' => $this->period->id, 'zi_area_id' => $this->area->id, 'drive_url' => $url])
                ->assertStatus(422)->assertJsonValidationErrors('drive_url');
        }
        $this->assertSame(0, ZiActivity::count());
        $activity = $this->activity();
        $this->postJson(route('progress-zi.activities.evidences.store', $activity), [])->assertStatus(422);
        $this->assertSame(0, $activity->realizations()->count());
    }

    public function test_long_guide_question_keeps_the_activity_name_within_the_database_limit()
    {
        $title = app(\App\Services\ProgressZi\ZiEvidenceGuideService::class)->entries()['row-193']['title'];
        $this->assertGreaterThan(255, mb_strlen($title));
        $this->subPoint->update(['title' => $title]);
        $this->post(route('progress-zi.sub-points.evidences.store', $this->subPoint), [
            'zi_period_id' => $this->period->id, 'zi_area_id' => $this->area->id,
            'title' => 'Bukti inovasi pelayanan', 'drive_url' => 'https://drive.google.com/drive/folders/qa_layanan',
        ])->assertRedirect();
        $activity = ZiActivity::firstOrFail();
        $this->assertLessThanOrEqual(255, mb_strlen($activity->name));
        $this->assertSame($title, $activity->realizations()->first()->result_summary);
    }

    public function test_file_upload_and_old_linked_mode_are_rejected()
    {
        $route = route('progress-zi.activities.evidences.store', $this->activity());
        $this->postJson($route, ['drive_url' => 'https://drive.google.com/file/d/qa_file/view', 'file' => UploadedFile::fake()->image('eviden.png')])
            ->assertStatus(422)->assertJsonValidationErrors('file');
        $this->postJson($route, ['mode' => 'linked', 'linked_source' => 'rapat:1'])
            ->assertStatus(422)->assertJsonValidationErrors(['mode', 'drive_url', 'linked_source']);
        $this->assertSame(0, ZiEvidence::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_user_without_area_access_cannot_create_an_activity_via_sub_point()
    {
        $user = auth()->user();
        $user->setRelation('roles', collect([new Role(['name' => 'pegawai'])]));
        $this->postJson(route('progress-zi.sub-points.evidences.store', $this->subPoint), [
            'zi_period_id' => $this->period->id, 'zi_area_id' => $this->area->id,
            'drive_url' => 'https://drive.google.com/drive/folders/qa_folder',
        ])->assertForbidden();
        $this->assertSame(0, ZiActivity::count());
    }

    public function test_old_files_remain_accessible_after_migration()
    {
        Storage::disk('public')->put('zi/legacy.pdf', '%PDF-1.4 legacy');
        $evidence = $this->realization($this->activity())->evidences()->create([
            'title' => 'Eviden Lama', 'evidence_type' => 'manual_upload',
            'file_path' => 'zi/legacy.pdf', 'file_name' => 'legacy.pdf', 'status' => 'terupload',
        ]);
        $this->assertNull($evidence->drive_url);
        $this->get(route('progress-zi.evidences.file', $evidence))->assertOk();
        $this->assertSame(route('progress-zi.evidences.file', $evidence), $evidence->preview_url);
    }

    public function test_pdf_bundle_preserves_clickable_drive_links()
    {
        $activity = $this->activity();
        $url = 'https://drive.google.com/drive/folders/qa_bundle';
        $this->realization($activity)->evidences()->create([
            'title' => 'Folder Eviden', 'evidence_type' => 'google_drive', 'drive_url' => $url, 'status' => 'terhubung',
        ]);
        $bundle = app(ZiEvidenceBundleService::class)->createBundle($activity);
        try {
            $pdf = file_get_contents($bundle['path']);
            $this->assertStringStartsWith('%PDF-', $pdf);
            $this->assertStringContainsString('/URI (' . $url . ')', $pdf);
        } finally {
            @unlink($bundle['path']);
        }
    }

    /** @dataProvider driveUrls */
    public function test_google_drive_url_validation($url, $expected)
    {
        $this->assertSame($expected, (new GoogleDriveUrl)->passes('drive_url', $url));
    }

    public function driveUrls()
    {
        return [
            ['https://drive.google.com/file/d/abc-123/view?resourcekey=key', true],
            ['https://drive.google.com/drive/u/0/folders/abc_123', true],
            ['https://drive.google.com/open?id=abc-123', true],
            ['https://docs.google.com/spreadsheets/d/abc/edit#gid=0', true],
            ['https://docs.google.com/presentation/d/abc/edit', true],
            ['https://drive.google.com', false],
            ['https://drive.google.com/drive/my-drive', false],
            ['https://drive.google.com.evil.test/file/d/abc/view', false],
            ['https://drive.google.com@evil.test/file/d/abc/view', false],
            ['https://user@drive.google.com/file/d/abc/view', false],
            ['http://drive.google.com/file/d/abc/view', false],
            ['https://example.com/file/d/abc/view', false],
            ['https://drive.google.com/open?id[]=abc', false],
            ['https://drive.google.com\\@evil.test/file/d/abc/view', false],
            ['javascript:alert(1)', false],
        ];
    }

    protected function activity()
    {
        return ZiActivity::create(['zi_period_id' => $this->period->id, 'zi_area_id' => $this->area->id,
            'zi_guideline_sub_point_id' => $this->subPoint->id, 'name' => 'Pengumpulan Eviden', 'pic_user_id' => auth()->id()]);
    }

    protected function realization(ZiActivity $activity)
    {
        return $activity->realizations()->create(['realization_date' => '2026-11-07', 'implementation_summary' => 'Pengumpulan eviden']);
    }
}
