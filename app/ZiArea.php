<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ZiArea extends Model
{
    public const GROUP_PENGUNGKIT = 'pengungkit';
    public const GROUP_REFORM = 'reform';
    public const GROUP_HASIL = 'hasil';

    protected $fillable = ['code', 'name', 'description', 'group_type', 'pic_user_id', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function pic() { return $this->belongsTo(User::class, 'pic_user_id'); }
    public function pics() { return $this->belongsToMany(User::class, 'zi_area_pic')->withTimestamps()->orderByRaw('CASE WHEN hirarki IS NULL THEN 1 ELSE 0 END')->orderBy('hirarki')->orderBy('name'); }
    public function activities() { return $this->hasMany(ZiActivity::class, 'zi_area_id'); }
    public function guidelinePoints() { return $this->hasMany(ZiGuidelinePoint::class, 'zi_area_id')->orderBy('sort_order')->orderBy('id'); }
    public function getStatusBadgeAttribute() { return '<span class="badge badge-' . ($this->is_active ? 'success' : 'secondary') . ' app-status-badge">' . ($this->is_active ? 'Aktif' : 'Nonaktif') . '</span>'; }
    public function getGroupLabelAttribute()
    {
        $map = [
            self::GROUP_PENGUNGKIT => 'Pengungkit',
            self::GROUP_REFORM => 'Reform',
            self::GROUP_HASIL => 'Hasil',
        ];

        return $map[$this->group_type] ?? ucfirst((string) $this->group_type);
    }

    public function getGroupBadgeAttribute()
    {
        $map = [
            self::GROUP_PENGUNGKIT => ['primary', 'Pengungkit'],
            self::GROUP_REFORM => ['warning', 'Reform'],
            self::GROUP_HASIL => ['success', 'Hasil'],
        ];

        [$class, $label] = $map[$this->group_type] ?? ['secondary', $this->group_label];

        return '<span class="badge badge-' . $class . ' app-status-badge">' . $label . '</span>';
    }

    public static function groupOptions()
    {
        return [
            self::GROUP_PENGUNGKIT => 'Pengungkit',
            self::GROUP_REFORM => 'Reform',
            self::GROUP_HASIL => 'Hasil',
        ];
    }

    public static function grouped(Collection $areas)
    {
        $grouped = $areas->groupBy('group_type');

        return collect([
            self::GROUP_PENGUNGKIT => $grouped->get(self::GROUP_PENGUNGKIT, collect()),
            self::GROUP_REFORM => $grouped->get(self::GROUP_REFORM, collect()),
            self::GROUP_HASIL => $grouped->get(self::GROUP_HASIL, collect()),
        ]);
    }

    public function getPicNamesAttribute()
    {
        $pics = $this->relationLoaded('pics') ? $this->pics : $this->pics()->get();

        if ($pics->isNotEmpty()) {
            return $pics->pluck('name')->implode(', ');
        }

        return optional($this->pic)->name ?: '-';
    }
}
