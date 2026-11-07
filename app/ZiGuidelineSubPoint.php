<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZiGuidelineSubPoint extends Model
{
    protected $fillable = [
        'zi_guideline_point_id',
        'code',
        'title',
        'description',
        'sort_order',
        'evidence_guide_key',
    ];

    public function point()
    {
        return $this->belongsTo(ZiGuidelinePoint::class, 'zi_guideline_point_id');
    }

    public function getEvidenceGuideAttribute()
    {
        return app(\App\Services\ProgressZi\ZiEvidenceGuideService::class)->forSubPoint($this);
    }

    public function getDisplayCodeAttribute()
    {
        return $this->evidence_guide['code'] ?? $this->code;
    }

    public function indicators()
    {
        return $this->hasMany(ZiGuidelineIndicator::class, 'zi_guideline_sub_point_id')->orderBy('sort_order')->orderBy('id');
    }
}
