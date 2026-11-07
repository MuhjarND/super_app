<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithProgressZi;
use App\ZiArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProgressZiGuidelineController extends Controller
{
    use InteractsWithProgressZi;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->abortUnlessCanAccessProgressZi();
        if (!$this->guidelineModuleReady()) {
            return $this->progressZiSetupResponse('Pedoman ZI Belum Diaktifkan');
        }

        $groupOptions = ZiArea::groupOptions();
        $selectedAreaId = $request->filled('area_id') ? (int) $request->area_id : null;
        $areas = ZiArea::with([
            'pic',
            'pics',
            'guidelinePoints.subPoints.indicators',
            'guidelinePoints.subPoints' => function ($query) { $query->whereNotNull('evidence_guide_key'); },
            'guidelinePoints' => function ($query) { $query->whereHas('subPoints', function ($sub) { $sub->whereNotNull('evidence_guide_key'); }); },
        ])->whereHas('guidelinePoints.subPoints', function ($query) { $query->whereNotNull('evidence_guide_key'); })
            ->orderByRaw("FIELD(group_type, 'pengungkit', 'reform', 'hasil')")
            ->orderBy('code')
            ->get();

        $groupedAreas = ZiArea::grouped($areas);
        $selectedArea = $selectedAreaId ? $areas->firstWhere('id', $selectedAreaId) : null;
        $selectedGroupType = $request->filled('group_type') && isset($groupOptions[$request->group_type])
            ? $request->group_type
            : optional($selectedArea)->group_type;

        if (!$selectedGroupType) {
            $selectedGroupType = collect(array_keys($groupOptions))->first(function ($groupType) use ($groupedAreas) {
                return $groupedAreas->get($groupType, collect())->isNotEmpty();
            });
        }

        $visibleAreas = $selectedGroupType
            ? $groupedAreas->get($selectedGroupType, collect())
            : collect();

        if (!$selectedArea || ($selectedGroupType && $selectedArea->group_type !== $selectedGroupType)) {
            $selectedArea = $visibleAreas->first();
        }

        return view('progress-zi.guidelines.index', [
            'areas' => $areas,
            'groupedAreas' => $groupedAreas,
            'visibleAreas' => $visibleAreas,
            'selectedGroupType' => $selectedGroupType,
            'groupOptions' => $groupOptions,
            'selectedArea' => $selectedArea,
            'canManage' => false,
        ]);
    }


    protected function guidelineModuleReady()
    {
        return Schema::hasTable('zi_guideline_points')
            && Schema::hasTable('zi_guideline_sub_points')
            && Schema::hasColumn('zi_guideline_sub_points', 'evidence_guide_key');
    }
}
