<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithProgressZi;
use App\ZiArea;
use App\ZiGuidelineIndicator;
use App\ZiGuidelinePoint;
use App\ZiGuidelineSubPoint;
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
        ])->orderByRaw("FIELD(group_type, 'pengungkit', 'reform', 'hasil')")
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
            'canManage' => auth()->user()->canManageProgressZiMasterData(),
        ]);
    }

    public function storePoint(Request $request, ZiArea $ziArea)
    {
        $this->abortUnlessCanManageProgressZiMaster();
        abort_unless($this->guidelineModuleReady(), 404);

        $request->validate([
            'code' => 'required|string|max:20',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $ziArea->guidelinePoints()->create([
            'code' => strtoupper($request->code),
            'title' => $request->title,
            'description' => $request->description,
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->route('progress-zi.guidelines.index', ['area_id' => $ziArea->id])->with('success', 'Poin pedoman berhasil ditambahkan.');
    }

    public function storeSubPoint(Request $request, ZiGuidelinePoint $ziGuidelinePoint)
    {
        $this->abortUnlessCanManageProgressZiMaster();
        abort_unless($this->guidelineModuleReady(), 404);

        $request->validate([
            'code' => 'required|string|max:20',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $ziGuidelinePoint->subPoints()->create([
            'code' => strtolower($request->code),
            'title' => $request->title,
            'description' => $request->description,
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->route('progress-zi.guidelines.index', ['area_id' => $ziGuidelinePoint->zi_area_id])->with('success', 'Sub poin pedoman berhasil ditambahkan.');
    }

    public function updatePoint(Request $request, ZiGuidelinePoint $ziGuidelinePoint)
    {
        $this->abortUnlessCanManageProgressZiMaster();
        abort_unless($this->guidelineModuleReady(), 404);

        $request->validate([
            'code' => 'required|string|max:20',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $ziGuidelinePoint->update([
            'code' => strtoupper($request->code),
            'title' => $request->title,
            'description' => $request->description,
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->route('progress-zi.guidelines.index', ['area_id' => $ziGuidelinePoint->zi_area_id])->with('success', 'Poin pedoman berhasil diperbarui.');
    }

    public function destroyPoint(ZiGuidelinePoint $ziGuidelinePoint)
    {
        $this->abortUnlessCanManageProgressZiMaster();
        abort_unless($this->guidelineModuleReady(), 404);

        $areaId = $ziGuidelinePoint->zi_area_id;
        $ziGuidelinePoint->delete();

        return redirect()->route('progress-zi.guidelines.index', ['area_id' => $areaId])->with('success', 'Poin pedoman berhasil dihapus.');
    }

    public function storeIndicator(Request $request, ZiGuidelineSubPoint $ziGuidelineSubPoint)
    {
        $this->abortUnlessCanManageProgressZiMaster();
        abort_unless($this->guidelineModuleReady(), 404);

        $request->validate([
            'code' => 'nullable|string|max:20',
            'indicator_text' => 'required|string',
            'evidence_example' => 'nullable|string',
            'implementation_note' => 'nullable|string',
            'is_periodic' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $ziGuidelineSubPoint->indicators()->create([
            'code' => $request->code,
            'indicator_text' => $request->indicator_text,
            'evidence_example' => $request->evidence_example,
            'implementation_note' => $request->implementation_note,
            'is_periodic' => $request->boolean('is_periodic'),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->route('progress-zi.guidelines.index', ['area_id' => optional(optional($ziGuidelineSubPoint->point)->area)->id])->with('success', 'Indikator penilaian berhasil ditambahkan.');
    }

    public function updateSubPoint(Request $request, ZiGuidelineSubPoint $ziGuidelineSubPoint)
    {
        $this->abortUnlessCanManageProgressZiMaster();
        abort_unless($this->guidelineModuleReady(), 404);

        $request->validate([
            'code' => 'required|string|max:20',
            'title' => 'required|string',
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $ziGuidelineSubPoint->update([
            'code' => strtolower($request->code),
            'title' => $request->title,
            'description' => $request->description,
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->route('progress-zi.guidelines.index', ['area_id' => optional($ziGuidelineSubPoint->point)->zi_area_id])->with('success', 'Sub poin pedoman berhasil diperbarui.');
    }

    public function destroySubPoint(ZiGuidelineSubPoint $ziGuidelineSubPoint)
    {
        $this->abortUnlessCanManageProgressZiMaster();
        abort_unless($this->guidelineModuleReady(), 404);

        $areaId = optional($ziGuidelineSubPoint->point)->zi_area_id;
        $ziGuidelineSubPoint->delete();

        return redirect()->route('progress-zi.guidelines.index', ['area_id' => $areaId])->with('success', 'Sub poin pedoman berhasil dihapus.');
    }

    public function updateIndicator(Request $request, ZiGuidelineIndicator $ziGuidelineIndicator)
    {
        $this->abortUnlessCanManageProgressZiMaster();
        abort_unless($this->guidelineModuleReady(), 404);

        $request->validate([
            'code' => 'nullable|string|max:20',
            'indicator_text' => 'required|string',
            'evidence_example' => 'nullable|string',
            'implementation_note' => 'nullable|string',
            'is_periodic' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $ziGuidelineIndicator->update([
            'code' => $request->code,
            'indicator_text' => $request->indicator_text,
            'evidence_example' => $request->evidence_example,
            'implementation_note' => $request->implementation_note,
            'is_periodic' => $request->boolean('is_periodic'),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->route('progress-zi.guidelines.index', ['area_id' => optional(optional(optional($ziGuidelineIndicator->subPoint)->point)->area)->id])->with('success', 'Indikator penilaian berhasil diperbarui.');
    }

    public function destroyIndicator(ZiGuidelineIndicator $ziGuidelineIndicator)
    {
        $this->abortUnlessCanManageProgressZiMaster();
        abort_unless($this->guidelineModuleReady(), 404);

        $areaId = optional(optional(optional($ziGuidelineIndicator->subPoint)->point)->area)->id;
        $ziGuidelineIndicator->delete();

        return redirect()->route('progress-zi.guidelines.index', ['area_id' => $areaId])->with('success', 'Indikator penilaian berhasil dihapus.');
    }

    protected function guidelineModuleReady()
    {
        return Schema::hasTable('zi_guideline_points')
            && Schema::hasTable('zi_guideline_sub_points')
            && Schema::hasTable('zi_guideline_indicators');
    }
}
