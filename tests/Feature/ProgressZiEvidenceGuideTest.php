<?php

namespace Tests\Feature;

use App\Services\ProgressZi\ZiEvidenceGuideService;
use App\Services\ProgressZi\ZiProgressService;
use App\ZiActivity;
use App\ZiArea;
use App\ZiGuidelinePoint;
use App\ZiGuidelineSubPoint;
use App\ZiPeriod;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProgressZiEvidenceGuideTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->string('name'); });
        \Illuminate\Support\Facades\DB::table('users')->insert(['id' => 1, 'name' => 'PIC Lama']);
        foreach ([
            '2026_03_25_000001_create_progress_zi_tables.php' => 'CreateProgressZiTables',
            '2026_03_25_000002_create_progress_zi_guideline_tables.php' => 'CreateProgressZiGuidelineTables',
            '2026_03_25_000003_add_guideline_sub_point_id_to_zi_activities_table.php' => 'AddGuidelineSubPointIdToZiActivitiesTable',
            '2026_03_25_000005_add_progress_zi_activity_review_fields.php' => 'AddProgressZiActivityReviewFields',
        ] as $file => $class) {
            require_once database_path('migrations/' . $file);
            (new $class)->up();
        }
        Schema::table('zi_areas', function (Blueprint $table) { $table->string('group_type')->default('pengungkit'); });
        Schema::table('zi_guideline_sub_points', function (Blueprint $table) { $table->string('evidence_guide_key')->nullable()->unique(); });
    }

    public function test_all_source_questions_supporting_data_and_images_are_present()
    {
        $service = app(ZiEvidenceGuideService::class);
        $this->assertCount(14, $service->document()['areas']);
        $this->assertCount(113, $service->entries());
        $this->assertSame(24, collect($service->entries())->sum(function ($entry) { return count($entry['supporting_data']); }));
        $images = collect($service->entries())->flatMap(function ($entry) { return $entry['images']; });
        $this->assertCount(8, $images);
        foreach ($images as $image) { $this->assertFileExists(public_path($image['path'])); }
        $this->assertStringContainsString('SK Tim Kerja', $service->entries()['row-9']['guidance']);
        $this->assertStringContainsString('Jumlah Agen Perubahan', $service->entries()['row-129']['supporting_data'][0]['label']);
        $this->assertSame('hasil', $service->entries()['row-204']['group']);
        $this->assertSame('reform', $service->entries()['row-178']['group']);
    }

    public function test_sync_is_repeatable_and_preserves_existing_evidence_and_pic_assignments()
    {
        $area = ZiArea::create(['code' => 'AREA 01', 'group_type' => 'pengungkit', 'name' => 'Manajemen Perubahan', 'pic_user_id' => 1]);
        $point = $area->guidelinePoints()->create(['code' => '1', 'title' => 'Tim Kerja']);
        $subPoint = $point->subPoints()->create(['code' => 'a', 'title' => 'Unit kerja telah membentuk tim untuk melakukan pembangunan Zona Integritas']);
        $activity = $this->activity($area, $subPoint);
        $evidence = $activity->realizations()->create(['realization_date' => '2026-11-07', 'implementation_summary' => 'Dokumen lama'])
            ->evidences()->create(['title' => 'SK tersimpan', 'description' => 'Catatan tetap', 'evidence_type' => 'manual_upload', 'file_path' => 'legacy/sk.pdf', 'status' => 'terupload']);
        $service = app(ZiEvidenceGuideService::class);
        $this->assertSame(['areas' => 14, 'points' => 45, 'evidence_points' => 113], $service->sync());
        $ids = ZiGuidelineSubPoint::whereNotNull('evidence_guide_key')->pluck('id', 'evidence_guide_key')->all();
        $this->assertCount(113, $ids);
        $this->assertSame($subPoint->id, $ids['row-9']);
        $this->assertSame(14, ZiArea::count());
        $this->assertSame(45, ZiGuidelinePoint::count());
        $service->sync();
        $this->assertSame($ids, ZiGuidelineSubPoint::whereNotNull('evidence_guide_key')->pluck('id', 'evidence_guide_key')->all());
        $this->assertSame(113, ZiGuidelineSubPoint::count());
        $this->assertSame('legacy/sk.pdf', $evidence->fresh()->file_path);
        $this->assertSame('Catatan tetap', $evidence->fresh()->description);
        $this->assertSame(1, (int) $area->fresh()->pic_user_id);
        $this->assertSame($subPoint->id, (int) $activity->fresh()->zi_guideline_sub_point_id);
    }

    public function test_same_code_with_a_different_meaning_is_not_reassigned_to_another_evidence_point()
    {
        $area = ZiArea::create(['code' => 'AREA 01', 'group_type' => 'pengungkit', 'name' => 'Manajemen Perubahan']);
        $point = $area->guidelinePoints()->create(['code' => 'i', 'title' => 'Tim Kerja']);
        $legacy = $point->subPoints()->create(['code' => 'a', 'title' => 'Catatan kegiatan lokal yang berbeda']);
        app(ZiEvidenceGuideService::class)->sync();
        $this->assertNull($legacy->fresh()->evidence_guide_key);
        $this->assertNotSame($legacy->id, ZiGuidelineSubPoint::where('evidence_guide_key', 'row-9')->firstOrFail()->id);
        $this->assertSame('Catatan kegiatan lokal yang berbeda', $legacy->fresh()->title);
    }

    public function test_progress_uses_approved_evidence_and_includes_points_without_activities()
    {
        app(ZiEvidenceGuideService::class)->sync();
        $subPoint = ZiGuidelineSubPoint::where('evidence_guide_key', 'row-9')->firstOrFail();
        $area = $subPoint->point->area;
        $activity = $this->activity($area, $subPoint);
        $service = app(ZiProgressService::class);
        $this->assertSame(0, $service->calculateActivityScore($activity));
        $activity->realizations()->create(['realization_date' => '2026-11-07', 'implementation_summary' => 'Pengumpulan'])
            ->evidences()->create(['title' => 'SK', 'evidence_type' => 'google_drive', 'status' => 'terhubung']);
        $activity = $activity->fresh();
        $this->assertSame(0, $service->calculateActivityScore($activity));
        $activity->approvals()->create(['approver_id' => 1, 'status' => 'approved', 'requested_at' => now()]);
        $activity = $activity->fresh();
        $this->assertSame(100, $service->calculateActivityScore($activity));
        $this->assertSame(100, $activity->progress_score);
        $this->assertSame(8.3, $service->calculateAreaScore($area, $activity->period));
        $this->assertSame(0.9, $service->calculatePeriodScore($activity->period));
    }

    public function test_manual_guideline_and_indicator_writes_are_no_longer_routed()
    {
        $routes = app('router')->getRoutes();
        foreach (['progress-zi.guidelines.points.store', 'progress-zi.guidelines.indicators.store', 'progress-zi.indicators.store', 'progress-zi.indicators.review'] as $name) {
            $this->assertNull($routes->getByName($name));
        }
    }

    public function test_notes_from_excel_are_saved_separately_and_read_from_the_database()
    {
        require_once database_path('migrations/2026_11_07_000003_create_zi_evidence_guides_table.php');
        (new \CreateZiEvidenceGuidesTable)->up();
        $this->assertSame(113, \App\ZiEvidenceGuide::count());
        $stored = \App\ZiEvidenceGuide::findOrFail('row-9');
        $this->assertStringContainsString('SK Tim Kerja', $stored->notes);
        $this->assertSame('Ya, jika Tim telah dibentuk di dalam unit kerja.', $stored->explanation);
        $stored->update(['notes' => "Catatan server\n<script>alert('x')</script>"]);
        $point = ZiGuidelineSubPoint::where('evidence_guide_key', 'row-9')->firstOrFail();
        $guide = (new ZiEvidenceGuideService)->forSubPoint($point);
        $this->assertSame($stored->notes, $guide['notes']);
        $html = view('progress-zi.partials.evidence-guide', ['guide' => $guide])->render();
        $this->assertStringContainsString('Catatan/Keterangan/Penjelasan', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        (new \CreateZiEvidenceGuidesTable)->up();
        $this->assertSame(113, \App\ZiEvidenceGuide::count());
    }

    protected function activity(ZiArea $area, ZiGuidelineSubPoint $subPoint)
    {
        $period = ZiPeriod::firstOrCreate(['name' => 'Evaluasi ZI 2026'], ['year' => 2026]);
        return ZiActivity::create(['zi_period_id' => $period->id, 'zi_area_id' => $area->id,
            'zi_guideline_sub_point_id' => $subPoint->id, 'name' => 'Pengumpulan SK']);
    }
}
