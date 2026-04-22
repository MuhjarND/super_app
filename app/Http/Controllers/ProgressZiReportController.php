<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithProgressZi;
use App\Services\ProgressZi\ZiProgressService;
use App\User;
use App\ZiActivity;
use App\ZiArea;
use App\ZiGuidelineIndicator;
use App\ZiGuidelinePoint;
use App\ZiPeriod;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;

class ProgressZiReportController extends Controller
{
    use InteractsWithProgressZi;

    protected $progressService;

    public function __construct(ZiProgressService $progressService)
    {
        $this->middleware('auth');
        $this->progressService = $progressService;
    }

    public function index(Request $request)
    {
        $this->abortUnlessCanAccessProgressZi();
        if (!$this->progressZiModuleReady()) {
            return $this->progressZiSetupResponse('Laporan Progress ZI Belum Diaktifkan');
        }

        [$activities, $filters, $summary, $areaScores, $periods, $areas, $users] = $this->buildReport($request);

        return view('progress-zi.reports.index', compact(
            'activities',
            'filters',
            'summary',
            'areaScores',
            'periods',
            'areas',
            'users'
        ));
    }

    public function pdf(Request $request)
    {
        $this->abortUnlessCanAccessProgressZi();
        if (!$this->progressZiModuleReady()) {
            return $this->progressZiSetupResponse('Laporan Progress ZI Belum Diaktifkan');
        }

        [$activities, $filters, $summary, $areaScores] = $this->buildReport($request, false);
        $pdf = PDF::loadView('progress-zi.reports.pdf', compact('activities', 'filters', 'summary', 'areaScores'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('laporan-progress-zi.pdf');
    }

    public function excel(Request $request)
    {
        $this->abortUnlessCanAccessProgressZi();
        if (!$this->progressZiModuleReady()) {
            return $this->progressZiSetupResponse('Laporan Progress ZI Belum Diaktifkan');
        }

        [$activities, $filters, $summary, $areaScores] = $this->buildReport($request, false);
        $content = view('progress-zi.reports.excel', compact('activities', 'filters', 'summary', 'areaScores'))->render();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan-progress-zi.xls"',
        ]);
    }

    public function matrix(Request $request)
    {
        $this->abortUnlessCanAccessProgressZi();
        if (!$this->progressZiModuleReady()) {
            return $this->progressZiSetupResponse('Matriks Progress ZI Belum Diaktifkan');
        }

        $periods = ZiPeriod::orderByDesc('year')->orderByDesc('is_active')->get();
        $selectedPeriod = $request->filled('period_id')
            ? ZiPeriod::find($request->period_id)
            : $periods->firstWhere('is_active', true);

        $areas = ZiArea::with([
            'pic',
            'guidelinePoints.subPoints.indicators',
            'activities' => function ($query) use ($selectedPeriod) {
                if ($selectedPeriod) {
                    $query->where('zi_period_id', $selectedPeriod->id);
                }
            },
            'activities.guidelineSubPoint.point',
            'activities.pic',
            'activities.indicators.guidelineIndicator',
        ])->orderBy('code')->get();

        if ($request->filled('area_id')) {
            $areas = $areas->where('id', (int) $request->area_id)->values();
        }

        return view('progress-zi.reports.matrix', [
            'areas' => $areas,
            'periods' => $periods,
            'selectedPeriod' => $selectedPeriod,
            'selectedAreaId' => $request->filled('area_id') ? (int) $request->area_id : null,
        ]);
    }

    protected function buildReport(Request $request, $withOptions = true)
    {
        $periods = ZiPeriod::orderByDesc('year')->orderByDesc('is_active')->get();
        $selectedPeriod = $request->filled('period_id')
            ? ZiPeriod::find($request->period_id)
            : $periods->firstWhere('is_active', true);

        $query = ZiActivity::with([
            'period',
            'area.pic',
            'area.pics',
            'pic',
            'indicators',
            'realizations.evidences',
            'guidelineSubPoint.indicators',
        ]);

        if ($selectedPeriod) {
            $query->where('zi_period_id', $selectedPeriod->id);
        }

        if ($request->filled('area_id')) {
            $query->where('zi_area_id', $request->area_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('pic_user_id')) {
            $query->where('pic_user_id', $request->pic_user_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('area', function ($areaQuery) use ($search) {
                        $areaQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('indicators', function ($indicatorQuery) use ($search) {
                        $indicatorQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
            });
        }

        if (!auth()->user()->canManageProgressZiMasterData()) {
            $query->where(function ($builder) {
                $builder->where('pic_user_id', auth()->id())
                    ->orWhereHas('area', function ($areaQuery) {
                        $areaQuery->where('pic_user_id', auth()->id())
                            ->orWhereHas('pics', function ($picQuery) {
                                $picQuery->where('users.id', auth()->id());
                            });
                    });
            });
        }

        $allActivities = (clone $query)->orderBy('name')->get();

        if ($request->filled('attention')) {
            $attention = $request->attention;
            $allActivities = $allActivities->filter(function ($activity) use ($attention) {
                if ($attention === 'overdue') {
                    return $activity->target_end_date && $activity->target_end_date->isPast() && !in_array($activity->status, ['selesai', 'sudah_terlaksana'], true);
                }
                if ($attention === 'indicator') {
                    return $activity->indicators->contains(function ($indicator) {
                        return in_array($indicator->status, ['belum_diisi', 'belum_terpenuhi', 'sebagian_terpenuhi', 'ditolak'], true);
                    });
                }
                if ($attention === 'evidence') {
                    return $activity->realizations->flatMap->evidences->contains(function ($evidence) {
                        return in_array($evidence->status, ['belum_ada', 'terupload', 'terhubung', 'revisi', 'tidak_valid'], true);
                    });
                }

                return true;
            })->values();
        }

        $summary = [
            'period_name' => optional($selectedPeriod)->name ?: 'Semua Periode',
            'area_count' => $allActivities->pluck('zi_area_id')->unique()->count(),
            'activity_count' => $allActivities->count(),
            'indicator_count' => $allActivities->sum(function ($activity) {
                return $activity->indicators->count();
            }),
            'evidence_count' => $allActivities->sum(function ($activity) {
                return $activity->realizations->sum(function ($realization) {
                    return $realization->evidences->count();
                });
            }),
            'avg_progress' => round($allActivities->avg('progress_score') ?: 0, 1),
            'overdue_count' => $allActivities->filter(function ($activity) {
                return $activity->target_end_date && $activity->target_end_date->isPast() && !in_array($activity->status, ['selesai', 'sudah_terlaksana'], true);
            })->count(),
        ];

        $areasForCoverage = ZiArea::with([
            'pic',
            'pics',
            'guidelinePoints.subPoints.indicators',
            'activities' => function ($query) use ($selectedPeriod, $request) {
                if ($selectedPeriod) {
                    $query->where('zi_period_id', $selectedPeriod->id);
                }
                if ($request->filled('area_id')) {
                    $query->where('zi_area_id', $request->area_id);
                }
                if ($request->filled('status')) {
                    $query->where('status', $request->status);
                }
                if ($request->filled('pic_user_id')) {
                    $query->where('pic_user_id', $request->pic_user_id);
                }
            },
            'activities.latestApproval',
        ])->whereIn('id', $allActivities->pluck('zi_area_id')->unique()->filter()->values())->get();
        $coverage = $this->progressService->buildSubPointCoverage($areasForCoverage);
        $summary['sub_point_count'] = $coverage['total'];
        $summary['sub_point_covered_count'] = $coverage['covered'];
        $summary['periodic_sub_point_count'] = $coverage['periodic'];

        $areasForScore = ZiArea::whereIn('id', $allActivities->pluck('zi_area_id')->unique()->filter()->values())->with(['pic', 'pics'])->orderBy('code')->get();
        $areaScores = $areasForScore->map(function ($area) use ($selectedPeriod) {
            return [
                'code' => $area->code,
                'name' => $area->name,
                'pic' => $area->pic_names,
                'score' => $this->progressService->calculateAreaScore($area, $selectedPeriod),
            ];
        });

        if ($withOptions) {
            $page = max(1, (int) $request->get('page', 1));
            $perPage = 12;
            $paged = $allActivities->forPage($page, $perPage)->values();
            $activities = new \Illuminate\Pagination\LengthAwarePaginator(
                $paged,
                $allActivities->count(),
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        } else {
            $activities = $allActivities;
        }

        $filters = [
            'period_id' => optional($selectedPeriod)->id,
            'area_id' => $request->area_id,
            'status' => $request->status,
            'pic_user_id' => $request->pic_user_id,
            'search' => $request->search,
            'attention' => $request->attention,
        ];

        if (!$withOptions) {
            return [$activities, $filters, $summary, $areaScores];
        }

        $areas = ZiArea::orderBy('code')->get();
        $users = User::ordered()->get();

        return [$activities, $filters, $summary, $areaScores, $periods, $areas, $users];
    }
}
