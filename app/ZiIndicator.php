<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZiIndicator extends Model
{
    protected $fillable = ['zi_activity_id', 'zi_guideline_indicator_id', 'name', 'description', 'weight', 'target_fulfillment_text', 'is_evidence_required', 'minimum_evidence_count', 'status', 'created_by', 'updated_by'];
    protected $casts = ['weight' => 'decimal:2', 'is_evidence_required' => 'boolean', 'minimum_evidence_count' => 'integer'];

    public function activity() { return $this->belongsTo(ZiActivity::class, 'zi_activity_id'); }
    public function guidelineIndicator() { return $this->belongsTo(ZiGuidelineIndicator::class, 'zi_guideline_indicator_id'); }
    public function evidences() { return $this->belongsToMany(ZiEvidence::class, 'zi_indicator_evidence')->withPivot('notes')->withTimestamps(); }
    public function reviews() { return $this->morphMany(ZiReview::class, 'reviewable')->latest('reviewed_at'); }
    public function latestReview() { return $this->morphOne(ZiReview::class, 'reviewable')->latest('reviewed_at'); }

    public function getProgressScoreAttribute()
    {
        $map = ['belum_diisi' => 0, 'belum_terpenuhi' => 0, 'sebagian_terpenuhi' => 50, 'terpenuhi' => 100, 'diverifikasi' => 100, 'ditolak' => 0];
        return $map[$this->status] ?? 0;
    }

    public function getStatusLabelAttribute()
    {
        $map = ['belum_diisi' => 'Belum Diisi', 'belum_terpenuhi' => 'Belum Terpenuhi', 'sebagian_terpenuhi' => 'Sebagian Terpenuhi', 'terpenuhi' => 'Terpenuhi', 'diverifikasi' => 'Diverifikasi', 'ditolak' => 'Ditolak'];
        return $map[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function getStatusBadgeAttribute()
    {
        $map = ['belum_diisi' => ['secondary', 'Belum Diisi'], 'belum_terpenuhi' => ['danger', 'Belum Terpenuhi'], 'sebagian_terpenuhi' => ['warning', 'Sebagian Terpenuhi'], 'terpenuhi' => ['info', 'Terpenuhi'], 'diverifikasi' => ['success', 'Diverifikasi'], 'ditolak' => ['danger', 'Ditolak']];
        [$class, $label] = $map[$this->status] ?? ['secondary', $this->status_label];
        return '<span class="badge badge-' . $class . ' app-status-badge">' . $label . '</span>';
    }

    public function getGuidelineReferenceLabelAttribute()
    {
        if (!$this->guidelineIndicator) {
            return null;
        }

        $subPoint = $this->guidelineIndicator->subPoint;
        $point = optional($subPoint)->point;

        if (!$subPoint || !$point) {
            return $this->guidelineIndicator->indicator_text;
        }

        return 'Poin ' . $point->code . ' / Sub ' . strtoupper($subPoint->code) . ' / Indikator ' . ($this->guidelineIndicator->code ?: '-');
    }
}
