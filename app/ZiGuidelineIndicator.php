<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZiGuidelineIndicator extends Model
{
    protected $fillable = [
        'zi_guideline_sub_point_id',
        'code',
        'indicator_text',
        'evidence_example',
        'implementation_note',
        'is_periodic',
        'sort_order',
    ];

    protected $casts = [
        'is_periodic' => 'boolean',
    ];

    public function subPoint()
    {
        return $this->belongsTo(ZiGuidelineSubPoint::class, 'zi_guideline_sub_point_id');
    }
}
