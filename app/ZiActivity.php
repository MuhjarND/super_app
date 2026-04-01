<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZiActivity extends Model
{
    protected $fillable = ['zi_period_id', 'zi_area_id', 'zi_guideline_sub_point_id', 'name', 'description', 'target_start_date', 'target_end_date', 'pic_user_id', 'status', 'source_type', 'source_reference_type', 'source_reference_id', 'created_by', 'updated_by'];
    protected $casts = ['target_start_date' => 'date', 'target_end_date' => 'date'];

    public function period() { return $this->belongsTo(ZiPeriod::class, 'zi_period_id'); }
    public function area() { return $this->belongsTo(ZiArea::class, 'zi_area_id'); }
    public function guidelineSubPoint() { return $this->belongsTo(ZiGuidelineSubPoint::class, 'zi_guideline_sub_point_id'); }
    public function pic() { return $this->belongsTo(User::class, 'pic_user_id'); }
    public function indicators() { return $this->hasMany(ZiIndicator::class, 'zi_activity_id'); }
    public function realizations() { return $this->hasMany(ZiActivityRealization::class, 'zi_activity_id')->orderByDesc('realization_date'); }
    public function approvals() { return $this->hasMany(ZiActivityApproval::class, 'zi_activity_id')->latest('requested_at'); }
    public function latestApproval() { return $this->hasOne(ZiActivityApproval::class, 'zi_activity_id')->latest('requested_at'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }

    public function getProgressScoreAttribute()
    {
        $indicators = $this->relationLoaded('indicators') ? $this->indicators : $this->indicators()->get();
        if ($indicators->isEmpty()) { return 0; }
        return round($indicators->avg(function ($indicator) { return $indicator->progress_score; }), 1);
    }

    public function getStatusLabelAttribute()
    {
        $map = ['belum_mulai' => 'Belum Mulai', 'dijadwalkan' => 'Dijadwalkan', 'sedang_berjalan' => 'Sedang Berjalan', 'sudah_terlaksana' => 'Sudah Terlaksana', 'selesai' => 'Selesai', 'perlu_perbaikan' => 'Perlu Perbaikan'];
        return $map[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function getStatusBadgeAttribute()
    {
        $map = ['belum_mulai' => ['secondary', 'Belum Mulai'], 'dijadwalkan' => ['info', 'Dijadwalkan'], 'sedang_berjalan' => ['primary', 'Sedang Berjalan'], 'sudah_terlaksana' => ['success', 'Sudah Terlaksana'], 'selesai' => ['success', 'Selesai'], 'perlu_perbaikan' => ['warning', 'Perlu Perbaikan']];
        [$class, $label] = $map[$this->status] ?? ['secondary', $this->status_label];
        return '<span class="badge badge-' . $class . ' app-status-badge">' . $label . '</span>';
    }

    public function getProgressBadgeAttribute()
    {
        $score = $this->progress_score;
        $class = $score >= 100 ? 'success' : ($score >= 50 ? 'warning' : 'secondary');
        return '<span class="badge badge-' . $class . ' app-status-badge">' . rtrim(rtrim(number_format($score, 1), '0'), '.') . '%</span>';
    }

    public function getGuidelineReferenceLabelAttribute()
    {
        if (!$this->guidelineSubPoint) {
            return null;
        }

        $point = $this->guidelineSubPoint->point;
        if (!$point) {
            return $this->guidelineSubPoint->title;
        }

        return 'Poin ' . $point->code . ' / Sub Poin ' . strtoupper($this->guidelineSubPoint->code);
    }

    public function getEvidenceCountAttribute()
    {
        $realizations = $this->relationLoaded('realizations') ? $this->realizations : $this->realizations()->withCount('evidences')->get();

        return (int) $realizations->sum(function ($realization) {
            return $realization->relationLoaded('evidences')
                ? $realization->evidences->count()
                : ($realization->evidences_count ?? 0);
        });
    }

    public function getMonitoringStatusKeyAttribute()
    {
        $latestApproval = $this->relationLoaded('latestApproval') ? $this->latestApproval : $this->latestApproval()->first();

        if ($latestApproval && $latestApproval->status === 'approved') {
            return 'selesai';
        }

        if ($latestApproval && $latestApproval->status === 'pending') {
            return 'dalam_proses_review';
        }

        if ($this->evidence_count > 0) {
            return 'diupload';
        }

        return 'dijadwalkan';
    }

    public function getMonitoringStatusLabelAttribute()
    {
        $map = [
            'dijadwalkan' => 'Dijadwalkan',
            'diupload' => 'Diupload',
            'dalam_proses_review' => 'Dalam Proses Review',
            'selesai' => 'Selesai',
        ];

        return $map[$this->monitoring_status_key] ?? $this->status_label;
    }

    public function getMonitoringStatusBadgeAttribute()
    {
        $map = [
            'dijadwalkan' => ['info', 'Dijadwalkan'],
            'diupload' => ['primary', 'Diupload'],
            'dalam_proses_review' => ['warning', 'Dalam Proses Review'],
            'selesai' => ['success', 'Selesai'],
        ];

        [$class, $label] = $map[$this->monitoring_status_key] ?? ['secondary', $this->monitoring_status_label];

        return '<span class="badge badge-' . $class . ' app-status-badge">' . $label . '</span>';
    }

    public function getMonitoringPreviewUrlAttribute()
    {
        $realizations = $this->relationLoaded('realizations')
            ? $this->realizations
            : $this->realizations()->with('evidences')->get();

        $evidence = $realizations->flatMap(function ($realization) {
            return $realization->evidences;
        })->first(function ($item) {
            return !empty($item->preview_url);
        });

        return $evidence ? $evidence->preview_url : null;
    }
}
