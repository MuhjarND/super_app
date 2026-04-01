<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZiPeriod extends Model
{
    protected $fillable = ['name', 'year', 'target_evaluation_date', 'description', 'is_active', 'status', 'created_by', 'updated_by'];
    protected $casts = ['target_evaluation_date' => 'date', 'is_active' => 'boolean'];

    public function activities() { return $this->hasMany(ZiActivity::class, 'zi_period_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function getStatusBadgeAttribute() { return '<span class="badge badge-' . ($this->is_active ? 'success' : 'secondary') . ' app-status-badge">' . ($this->is_active ? 'Aktif' : 'Nonaktif') . '</span>'; }
}
