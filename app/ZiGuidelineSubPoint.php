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
    ];

    public function point()
    {
        return $this->belongsTo(ZiGuidelinePoint::class, 'zi_guideline_point_id');
    }

    public function indicators()
    {
        return $this->hasMany(ZiGuidelineIndicator::class, 'zi_guideline_sub_point_id')->orderBy('sort_order')->orderBy('id');
    }
}
