<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithProgressZi;
use App\Services\ProgressZi\ZiActivityApprovalService;
use App\Services\ProgressZi\ZiEvidenceSourceService;
use App\Services\ProgressZi\ZiEvidenceRecommendationService;
use App\Services\RapatDocumentService;
use App\ZiActivity;
use App\ZiArea;
use App\ZiGuidelineIndicator;
use App\ZiGuidelineSubPoint;
use App\ZiIndicator;
use App\ZiPeriod;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgressZiMasterController extends Controller
{
    use InteractsWithProgressZi;

    protected $evidenceSourceService;
    protected $recommendationService;
    protected $rapatDocumentService;
    protected $activityApprovalService;

    public function __construct(
        ZiEvidenceSourceService $evidenceSourceService,
        ZiEvidenceRecommendationService $recommendationService,
        RapatDocumentService $rapatDocumentService,
        ZiActivityApprovalService $activityApprovalService
    )
    {
        $this->middleware('auth');
        $this->evidenceSourceService = $evidenceSourceService;
        $this->recommendationService = $recommendationService;
        $this->rapatDocumentService = $rapatDocumentService;
        $this->activityApprovalService = $activityApprovalService;
    }

    public function periods()
    {
        $this->abortUnlessCanAccessProgressZi();
        if (!$this->progressZiModuleReady()) { return $this->progressZiSetupResponse(); }

        return view('progress-zi.periods.index', [
            'periods' => ZiPeriod::orderByDesc('year')->get(),
            'canManage' => auth()->user()->canManageProgressZiMasterData(),
        ]);
    }

    public function storePeriod(Request $request)
    {
        $this->abortUnlessCanManageProgressZiMaster();
        $request->validate(['name' => 'required|string|max:255', 'year' => 'required|integer|min:2020|max:2100', 'target_evaluation_date' => 'nullable|date', 'description' => 'nullable|string', 'is_active' => 'nullable|boolean']);
        if ($request->boolean('is_active')) { ZiPeriod::query()->update(['is_active' => false, 'status' => 'inactive']); }
        ZiPeriod::create(['name' => $request->name, 'year' => $request->year, 'target_evaluation_date' => $request->target_evaluation_date, 'description' => $request->description, 'is_active' => $request->boolean('is_active'), 'status' => $request->boolean('is_active') ? 'active' : 'inactive', 'created_by' => auth()->id(), 'updated_by' => auth()->id()]);
        return redirect()->route('progress-zi.periods.index')->with('success', 'Periode ZI berhasil ditambahkan.');
    }

    public function updatePeriod(Request $request, ZiPeriod $ziPeriod)
    {
        $this->abortUnlessCanManageProgressZiMaster();
        $request->validate(['name' => 'required|string|max:255', 'year' => 'required|integer|min:2020|max:2100', 'target_evaluation_date' => 'nullable|date', 'description' => 'nullable|string', 'is_active' => 'nullable|boolean']);
        if ($request->boolean('is_active')) { ZiPeriod::where('id', '!=', $ziPeriod->id)->update(['is_active' => false, 'status' => 'inactive']); }
        $ziPeriod->update(['name' => $request->name, 'year' => $request->year, 'target_evaluation_date' => $request->target_evaluation_date, 'description' => $request->description, 'is_active' => $request->boolean('is_active'), 'status' => $request->boolean('is_active') ? 'active' : 'inactive', 'updated_by' => auth()->id()]);
        return redirect()->route('progress-zi.periods.index')->with('success', 'Periode ZI berhasil diperbarui.');
    }

    public function areas()
    {
        $this->abortUnlessCanAccessProgressZi();
        if (!$this->progressZiModuleReady()) { return $this->progressZiSetupResponse(); }
        $areas = ZiArea::with(['pic', 'pics'])
            ->orderByRaw("FIELD(group_type, 'pengungkit', 'reform', 'hasil')")
            ->orderBy('code')
            ->get();

        return view('progress-zi.areas.index', [
            'areas' => $areas,
            'groupedAreas' => ZiArea::grouped($areas),
            'groupOptions' => ZiArea::groupOptions(),
            'users' => User::active()->ordered()->get(),
            'canManage' => auth()->user()->canManageProgressZiMasterData(),
        ]);
    }

    public function storeArea(Request $request)
    {
        $this->abortUnlessCanManageProgressZiMaster();
        $request->validate([
            'code' => 'required|string|max:30|unique:zi_areas,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'group_type' => 'required|in:pengungkit,reform,hasil',
            'pic_user_ids' => 'nullable|array',
            'pic_user_ids.*' => Rule::exists('users', 'id')->where('status_aktif_pegawai', true),
            'is_active' => 'nullable|boolean',
        ]);
        $picUserIds = collect($request->input('pic_user_ids', []))->filter()->unique()->values();
        $area = ZiArea::create([
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'description' => $request->description,
            'group_type' => $request->group_type,
            'pic_user_id' => $picUserIds->first(),
            'is_active' => $request->boolean('is_active', true),
        ]);
        $area->pics()->sync($picUserIds->all());
        return redirect()->route('progress-zi.areas.index')->with('success', 'Area ZI berhasil ditambahkan.');
    }

    public function updateArea(Request $request, ZiArea $ziArea)
    {
        $this->abortUnlessCanManageProgressZiMaster();
        $request->validate([
            'code' => 'required|string|max:30|unique:zi_areas,code,' . $ziArea->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'group_type' => 'required|in:pengungkit,reform,hasil',
            'pic_user_ids' => 'nullable|array',
            'pic_user_ids.*' => Rule::exists('users', 'id')->where('status_aktif_pegawai', true),
            'is_active' => 'nullable|boolean',
        ]);
        $picUserIds = collect($request->input('pic_user_ids', []))->filter()->unique()->values();
        $ziArea->update([
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'description' => $request->description,
            'group_type' => $request->group_type,
            'pic_user_id' => $picUserIds->first(),
            'is_active' => $request->boolean('is_active', true),
        ]);
        $ziArea->pics()->sync($picUserIds->all());
        return redirect()->route('progress-zi.areas.index')->with('success', 'Area ZI berhasil diperbarui.');
    }

    public function activities(Request $request)
    {
        $this->abortUnlessCanAccessProgressZi();
        if (!$this->progressZiModuleReady()) { return $this->progressZiSetupResponse(); }

        $groupOptions = ZiArea::groupOptions();
        $periods = ZiPeriod::orderByDesc('year')->orderByDesc('is_active')->get();
        $selectedPeriod = $request->filled('period_id')
            ? ZiPeriod::find($request->period_id)
            : $periods->firstWhere('is_active', true);

        $areasQuery = ZiArea::with([
            'pic',
            'pics',
            'guidelinePoints.subPoints.indicators',
            'activities' => function ($query) use ($selectedPeriod, $request) {
                if ($selectedPeriod) {
                    $query->where('zi_period_id', $selectedPeriod->id);
                }
                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                }
                if ($request->filled('pic_user_id')) {
                    $query->where('pic_user_id', $request->pic_user_id);
                }
            },
            'activities.pic',
            'activities.guidelineSubPoint.point',
            'activities.indicators',
            'activities.realizations.evidences',
            'activities.latestApproval',
        ])->orderByRaw("FIELD(group_type, 'pengungkit', 'reform', 'hasil')")
            ->orderBy('code');

        if ($request->filled('area_id')) {
            $areasQuery->where('id', $request->area_id);
        }

        $areas = $areasQuery->get();
        $groupedAreas = ZiArea::grouped($areas);
        $selectedGroupType = $request->filled('group_type') && isset($groupOptions[$request->group_type])
            ? $request->group_type
            : collect(array_keys($groupOptions))->first(function ($groupType) use ($groupedAreas) {
                return $groupedAreas->get($groupType, collect())->isNotEmpty();
            });

        if (!auth()->user()->canManageProgressZiMasterData()) {
            $areas = $areas->filter(function ($area) {
                if (auth()->user()->canManageProgressZiArea($area)) {
                    return true;
                }

                return $area->activities->contains(function ($activity) {
                    return auth()->user()->canManageProgressZiActivity($activity);
                });
            })->values();
            $groupedAreas = ZiArea::grouped($areas);

            if ($selectedGroupType && $groupedAreas->get($selectedGroupType, collect())->isEmpty()) {
                $selectedGroupType = collect(array_keys($groupOptions))->first(function ($groupType) use ($groupedAreas) {
                    return $groupedAreas->get($groupType, collect())->isNotEmpty();
                });
            }
        }

        $subPointRecommendations = [];
        foreach ($areas as $area) {
            foreach ($area->guidelinePoints as $point) {
                foreach ($point->subPoints as $subPoint) {
                    $subPointRecommendations[$subPoint->id] = $this->recommendationService
                        ->recommendForSubPoint($area, $subPoint, auth()->user())
                        ->values()
                        ->all();
                }
            }
        }

        $evidenceSourceOptions = collect($this->evidenceSourceService->buildOptionsForUser(auth()->user()))
            ->flatten(1)
            ->values();

        return view('progress-zi.activities.index', [
            'areas' => $areas,
            'groupedAreas' => $groupedAreas,
            'visibleAreas' => $selectedGroupType ? $groupedAreas->get($selectedGroupType, collect()) : collect(),
            'selectedGroupType' => $selectedGroupType,
            'groupOptions' => $groupOptions,
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'users' => User::active()->ordered()->get(),
            'guidelineSubPoints' => $this->guidelineSubPointOptions(),
            'evidenceSourceOptions' => $evidenceSourceOptions,
            'kategoriSuratOptions' => $this->rapatDocumentService->getKategoriSuratLeafOptions(),
            'meetingParticipants' => User::with(['unit', 'jabatan', 'roles'])->active()->ordered()->get(),
            'meetingApprovers' => User::withRoleOrDelegatedJabatan(['admin', 'approval', 'super_admin'])
                ->active()
                ->with('jabatan')
                ->ordered()
                ->get(),
            'subPointRecommendations' => $subPointRecommendations,
            'filters' => $request->only(['period_id', 'area_id', 'status', 'pic_user_id', 'group_type']),
            'canManage' => auth()->user()->canManageProgressZiMasterData(),
        ]);
    }

    public function storeActivity(Request $request)
    {
        $request->validate(['zi_period_id' => 'required|exists:zi_periods,id', 'zi_area_id' => 'required|exists:zi_areas,id', 'zi_guideline_sub_point_id' => 'nullable|exists:zi_guideline_sub_points,id', 'name' => 'required|string|max:255', 'description' => 'nullable|string', 'target_start_date' => 'nullable|date', 'target_end_date' => 'nullable|date', 'pic_user_id' => ['nullable', Rule::exists('users', 'id')->where('status_aktif_pegawai', true)], 'status' => 'required|in:belum_mulai,dijadwalkan,sedang_berjalan,sudah_terlaksana,selesai,perlu_perbaikan', 'source_type' => 'nullable|in:manual,persuratan,rapat,cuti']);
        $area = ZiArea::findOrFail($request->zi_area_id);
        abort_unless(auth()->user()->canManageProgressZiArea($area), 403);
        $guidelineSubPointId = $this->validatedGuidelineSubPointId($request->zi_area_id, $request->zi_guideline_sub_point_id);
        ZiActivity::create($request->only(['zi_period_id', 'zi_area_id', 'name', 'description', 'target_start_date', 'target_end_date', 'pic_user_id', 'status']) + ['zi_guideline_sub_point_id' => $guidelineSubPointId, 'source_type' => $request->input('source_type', 'manual'), 'created_by' => auth()->id(), 'updated_by' => auth()->id()]);
        return redirect()->route('progress-zi.activities.index')->with('success', 'Kegiatan ZI berhasil ditambahkan.');
    }

    public function updateActivity(Request $request, ZiActivity $ziActivity)
    {
        $this->abortUnlessCanManageProgressZiMaster();
        $request->validate(['zi_period_id' => 'required|exists:zi_periods,id', 'zi_area_id' => 'required|exists:zi_areas,id', 'zi_guideline_sub_point_id' => 'nullable|exists:zi_guideline_sub_points,id', 'name' => 'required|string|max:255', 'description' => 'nullable|string', 'target_start_date' => 'nullable|date', 'target_end_date' => 'nullable|date', 'pic_user_id' => ['nullable', Rule::exists('users', 'id')->where('status_aktif_pegawai', true)], 'status' => 'required|in:belum_mulai,dijadwalkan,sedang_berjalan,sudah_terlaksana,selesai,perlu_perbaikan']);
        $guidelineSubPointId = $this->validatedGuidelineSubPointId($request->zi_area_id, $request->zi_guideline_sub_point_id);
        $ziActivity->update($request->only(['zi_period_id', 'zi_area_id', 'name', 'description', 'target_start_date', 'target_end_date', 'pic_user_id', 'status']) + ['zi_guideline_sub_point_id' => $guidelineSubPointId, 'updated_by' => auth()->id()]);
        return redirect()->route('progress-zi.activities.index')->with('success', 'Kegiatan ZI berhasil diperbarui.');
    }

    public function showActivity(ZiActivity $ziActivity)
    {
        return redirect()->route('progress-zi.activities.index', [
            'period_id' => $ziActivity->zi_period_id,
            'area_id' => $ziActivity->zi_area_id,
        ])->with('info', 'Detail kegiatan telah disatukan ke halaman Monitoring Kegiatan ZI.');
    }

    public function submitLeadershipReview(ZiActivity $ziActivity, Request $request)
    {
        $this->abortUnlessCanAccessProgressZi();
        abort_unless(auth()->user()->canManageProgressZiActivity($ziActivity), 403);

        $request->validate([
            'request_notes' => 'nullable|string',
        ]);

        $this->activityApprovalService->submit($ziActivity->loadMissing('realizations.evidences', 'latestApproval'), auth()->user(), $request->request_notes);

        return redirect()->route('progress-zi.activities.index', [
            'period_id' => $ziActivity->zi_period_id,
            'area_id' => $ziActivity->zi_area_id,
        ])->with('success', 'Review pimpinan berhasil diajukan ke approval.');
    }

    protected function guidelineSubPointOptions()
    {
        return ZiGuidelineSubPoint::with(['point.area'])
            ->whereHas('point.area')
            ->orderBy('id')
            ->get();
    }

    protected function validatedGuidelineSubPointId($areaId, $guidelineSubPointId)
    {
        if (!$guidelineSubPointId) {
            return null;
        }

        $subPoint = ZiGuidelineSubPoint::with('point')->findOrFail($guidelineSubPointId);
        abort_unless($subPoint->point && (int) $subPoint->point->zi_area_id === (int) $areaId, 422, 'Sub poin pedoman tidak sesuai dengan area kegiatan.');

        return $subPoint->id;
    }

    public function storeIndicator(Request $request, ZiActivity $ziActivity)
    {
        abort_unless(auth()->user()->canManageProgressZiActivity($ziActivity), 403);
        $request->validate(['zi_guideline_indicator_id' => 'nullable|exists:zi_guideline_indicators,id', 'name' => 'required|string|max:255', 'description' => 'nullable|string', 'weight' => 'nullable|numeric|min:0', 'target_fulfillment_text' => 'nullable|string|max:255', 'is_evidence_required' => 'nullable|boolean', 'minimum_evidence_count' => 'nullable|integer|min:0', 'status' => 'required|in:belum_diisi,belum_terpenuhi,sebagian_terpenuhi,terpenuhi,diverifikasi,ditolak']);
        $guidelineIndicatorId = $this->validatedGuidelineIndicatorId($ziActivity, $request->zi_guideline_indicator_id);
        $ziActivity->indicators()->create($request->only(['name', 'description', 'weight', 'target_fulfillment_text', 'minimum_evidence_count', 'status']) + ['zi_guideline_indicator_id' => $guidelineIndicatorId, 'is_evidence_required' => $request->boolean('is_evidence_required', true), 'created_by' => auth()->id(), 'updated_by' => auth()->id()]);
        return redirect()->route('progress-zi.activities.index', [
            'period_id' => $ziActivity->zi_period_id,
            'area_id' => $ziActivity->zi_area_id,
        ])->with('success', 'Indikator berhasil ditambahkan.');
    }

    public function updateIndicator(Request $request, ZiIndicator $ziIndicator)
    {
        $activity = $ziIndicator->activity()->with('area', 'guidelineSubPoint')->first();
        abort_unless(auth()->user()->canManageProgressZiActivity($activity), 403);
        $request->validate(['zi_guideline_indicator_id' => 'nullable|exists:zi_guideline_indicators,id', 'name' => 'required|string|max:255', 'description' => 'nullable|string', 'weight' => 'nullable|numeric|min:0', 'target_fulfillment_text' => 'nullable|string|max:255', 'is_evidence_required' => 'nullable|boolean', 'minimum_evidence_count' => 'nullable|integer|min:0', 'status' => 'required|in:belum_diisi,belum_terpenuhi,sebagian_terpenuhi,terpenuhi,diverifikasi,ditolak']);
        $guidelineIndicatorId = $this->validatedGuidelineIndicatorId($activity, $request->zi_guideline_indicator_id);
        $ziIndicator->update($request->only(['name', 'description', 'weight', 'target_fulfillment_text', 'minimum_evidence_count', 'status']) + ['zi_guideline_indicator_id' => $guidelineIndicatorId, 'is_evidence_required' => $request->boolean('is_evidence_required', true), 'updated_by' => auth()->id()]);
        return redirect()->route('progress-zi.activities.index', [
            'period_id' => $activity->zi_period_id,
            'area_id' => $activity->zi_area_id,
        ])->with('success', 'Indikator berhasil diperbarui.');
    }

    protected function guidelineIndicatorOptionsForActivity(ZiActivity $activity)
    {
        if (!$activity->guidelineSubPoint) {
            return collect();
        }

        return ZiGuidelineIndicator::where('zi_guideline_sub_point_id', $activity->zi_guideline_sub_point_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    protected function validatedGuidelineIndicatorId(ZiActivity $activity, $guidelineIndicatorId)
    {
        if (!$guidelineIndicatorId) {
            return null;
        }

        abort_unless($activity->guidelineSubPoint, 422, 'Kegiatan belum memiliki acuan sub poin pedoman.');
        $guidelineIndicator = ZiGuidelineIndicator::findOrFail($guidelineIndicatorId);
        abort_unless((int) $guidelineIndicator->zi_guideline_sub_point_id === (int) $activity->zi_guideline_sub_point_id, 422, 'Indikator pedoman tidak sesuai dengan acuan kegiatan.');

        return $guidelineIndicator->id;
    }
}
