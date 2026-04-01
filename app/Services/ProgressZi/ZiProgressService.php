<?php

namespace App\Services\ProgressZi;

use App\ZiActivity;
use App\ZiArea;
use App\ZiEvidence;
use App\ZiGuidelineSubPoint;
use App\ZiIndicator;
use App\ZiPeriod;
use Illuminate\Support\Collection;

class ZiProgressService
{
    public function subPointRequiresPeriodic($subPoint)
    {
        if (!$subPoint) {
            return false;
        }

        $indicators = $subPoint->relationLoaded('indicators')
            ? $subPoint->indicators
            : $subPoint->indicators()->get();

        return $indicators->contains(function ($indicator) {
            return (bool) $indicator->is_periodic;
        });
    }

    public function isSubPointCovered($subPoint, Collection $activities)
    {
        if (!$subPoint) {
            return false;
        }

        $subPointActivities = $activities->where('zi_guideline_sub_point_id', $subPoint->id)->values();
        if ($subPointActivities->isEmpty()) {
            return false;
        }

        if (!$this->subPointRequiresPeriodic($subPoint)) {
            return true;
        }

        $rapatActivities = $subPointActivities->filter(function ($activity) {
            return $activity->source_type === 'rapat' || $activity->source_reference_type === 'rapat';
        });

        if ($rapatActivities->count() > 1) {
            return true;
        }

        return $subPointActivities->contains(function ($activity) {
            $latestApproval = $activity->relationLoaded('latestApproval')
                ? $activity->latestApproval
                : $activity->latestApproval()->first();

            return $latestApproval && $latestApproval->status === 'approved';
        });
    }

    public function buildSubPointCoverage(Collection $areas)
    {
        $total = 0;
        $covered = 0;
        $periodic = 0;

        foreach ($areas as $area) {
            $activities = $area->relationLoaded('activities') ? $area->activities : collect();
            $points = $area->relationLoaded('guidelinePoints') ? $area->guidelinePoints : collect();

            foreach ($points as $point) {
                $subPoints = $point->relationLoaded('subPoints') ? $point->subPoints : collect();
                foreach ($subPoints as $subPoint) {
                    $total++;
                    if ($this->subPointRequiresPeriodic($subPoint)) {
                        $periodic++;
                    }
                    if ($this->isSubPointCovered($subPoint, $activities)) {
                        $covered++;
                    }
                }
            }
        }

        return [
            'total' => $total,
            'covered' => $covered,
            'remaining' => max($total - $covered, 0),
            'periodic' => $periodic,
        ];
    }

    public function scoreFromIndicatorStatus($status)
    {
        $map = ['belum_diisi' => 0, 'belum_terpenuhi' => 0, 'sebagian_terpenuhi' => 50, 'terpenuhi' => 100, 'diverifikasi' => 100, 'ditolak' => 0];
        return $map[$status] ?? 0;
    }

    public function calculateActivityScore(ZiActivity $activity)
    {
        $indicators = $activity->relationLoaded('indicators') ? $activity->indicators : $activity->indicators()->get();
        if ($indicators->isEmpty()) { return 0; }
        return round($indicators->avg(function ($indicator) { return $this->scoreFromIndicatorStatus($indicator->status); }), 1);
    }

    public function calculateAreaScore(ZiArea $area, ZiPeriod $period = null)
    {
        $query = $area->activities();
        if ($period) { $query->where('zi_period_id', $period->id); }
        $activities = $query->with('indicators')->get();
        if ($activities->isEmpty()) { return 0; }
        return round($activities->avg(function ($activity) { return $this->calculateActivityScore($activity); }), 1);
    }

    public function calculatePeriodScore(ZiPeriod $period)
    {
        $areas = ZiArea::whereHas('activities', function ($query) use ($period) { $query->where('zi_period_id', $period->id); })->get();
        if ($areas->isEmpty()) { return 0; }
        return round($areas->avg(function ($area) use ($period) { return $this->calculateAreaScore($area, $period); }), 1);
    }

    public function buildDashboard(?ZiPeriod $period = null)
    {
        $periodId = optional($period)->id;
        $activities = ZiActivity::query()->with(['area', 'pic', 'indicators']);
        $indicators = ZiIndicator::query()->with('activity.area');
        $evidences = ZiEvidence::query()->with('realization.activity');
        if ($periodId) {
            $activities->where('zi_period_id', $periodId);
            $indicators->whereHas('activity', function ($query) use ($periodId) { $query->where('zi_period_id', $periodId); });
            $evidences->whereHas('realization.activity', function ($query) use ($periodId) { $query->where('zi_period_id', $periodId); });
        }
        $activities = $activities->get();
        $indicators = $indicators->get();
        $evidences = $evidences->get();
        $areas = ZiArea::with([
            'pic',
            'pics',
            'guidelinePoints.subPoints.indicators',
            'activities' => function ($query) use ($periodId) {
                if ($periodId) {
                    $query->where('zi_period_id', $periodId);
                }
            },
            'activities.latestApproval',
        ])->whereHas('guidelinePoints.subPoints')->get();
        $coverage = $this->buildSubPointCoverage($areas);
        $areaProgress = $areas->map(function ($area) use ($period) {
            return [
                'name' => $area->name,
                'score' => $this->calculateAreaScore($area, $period),
                'pic' => $area->pic_names,
                'coverage' => $this->buildSubPointCoverage(collect([$area])),
            ];
        })->sortByDesc('score')->values();
        $overdueActivities = $activities->filter(function ($activity) { return $activity->target_end_date && $activity->target_end_date->lt(today()) && !in_array($activity->status, ['sudah_terlaksana', 'selesai'], true); })->sortBy('target_end_date')->values();
        $indicatorAttention = $indicators->filter(function ($indicator) { return in_array($indicator->status, ['belum_diisi', 'belum_terpenuhi', 'sebagian_terpenuhi', 'ditolak'], true); })->take(8)->values();
        $evidenceAttention = $evidences->filter(function ($evidence) { return in_array($evidence->status, ['belum_ada', 'terupload', 'terhubung', 'revisi', 'tidak_valid'], true); })->take(8)->values();
        $periodScore = $period ? $this->calculatePeriodScore($period) : ($areaProgress->avg('score') ?: 0);
        return [
            'summary' => [
                'area_count' => $areas->count(),
                'sub_point_count' => $coverage['total'],
                'sub_point_covered_count' => $coverage['covered'],
                'periodic_sub_point_count' => $coverage['periodic'],
                'activity_count' => $activities->count(),
                'indicator_count' => $indicators->count(),
                'evidence_count' => $evidences->count(),
                'period_score' => round($periodScore, 1),
                'overdue_count' => $overdueActivities->count(),
                'indicator_attention_count' => $indicatorAttention->count(),
                'evidence_attention_count' => $evidenceAttention->count()
            ],
            'area_progress' => $areaProgress,
            'overdue_activities' => $overdueActivities,
            'indicator_attention' => $indicatorAttention,
            'evidence_attention' => $evidenceAttention,
            'status_chart' => [
                'belum_mulai' => $activities->where('status', 'belum_mulai')->count(),
                'berjalan' => $activities->where('status', 'sedang_berjalan')->count(),
                'selesai' => $activities->whereIn('status', ['sudah_terlaksana', 'selesai'])->count(),
                'perlu_perbaikan' => $activities->where('status', 'perlu_perbaikan')->count()
            ],
            'period_trends' => $this->buildPeriodTrends(),
        ];
    }

    protected function buildPeriodTrends($limit = 6)
    {
        return ZiPeriod::orderByDesc('year')->take($limit)->get()->sortBy('year')->values()->map(function ($period) {
            return [
                'id' => $period->id,
                'name' => $period->name,
                'year' => $period->year,
                'score' => $this->calculatePeriodScore($period),
                'activity_count' => ZiActivity::where('zi_period_id', $period->id)->count(),
                'indicator_count' => ZiIndicator::whereHas('activity', function ($query) use ($period) {
                    $query->where('zi_period_id', $period->id);
                })->count(),
            ];
        });
    }
}
