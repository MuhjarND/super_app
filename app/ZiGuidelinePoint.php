<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZiGuidelinePoint extends Model
{
    protected $fillable = [
        'zi_area_id',
        'code',
        'title',
        'description',
        'sort_order',
    ];

    public function area()
    {
        return $this->belongsTo(ZiArea::class, 'zi_area_id');
    }

    public function subPoints()
    {
        return $this->hasMany(ZiGuidelineSubPoint::class, 'zi_guideline_point_id')->orderBy('sort_order')->orderBy('id');
    }
}
