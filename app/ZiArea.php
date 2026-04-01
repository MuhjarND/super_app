<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZiArea extends Model
{
    protected $fillable = ['code', 'name', 'description', 'pic_user_id', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function pic() { return $this->belongsTo(User::class, 'pic_user_id'); }
    public function pics() { return $this->belongsToMany(User::class, 'zi_area_pic')->withTimestamps()->orderBy('name'); }
    public function activities() { return $this->hasMany(ZiActivity::class, 'zi_area_id'); }
    public function guidelinePoints() { return $this->hasMany(ZiGuidelinePoint::class, 'zi_area_id')->orderBy('sort_order')->orderBy('id'); }
    public function getStatusBadgeAttribute() { return '<span class="badge badge-' . ($this->is_active ? 'success' : 'secondary') . ' app-status-badge">' . ($this->is_active ? 'Aktif' : 'Nonaktif') . '</span>'; }
    public function getPicNamesAttribute()
    {
        $pics = $this->relationLoaded('pics') ? $this->pics : $this->pics()->get();

        if ($pics->isNotEmpty()) {
            return $pics->pluck('name')->implode(', ');
        }

        return optional($this->pic)->name ?: '-';
    }
}
