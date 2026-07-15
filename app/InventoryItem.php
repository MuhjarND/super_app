<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    protected $fillable = ['legacy_source_id', 'code', 'name', 'description', 'is_active', 'created_by'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function details()
    {
        return $this->hasMany(InventoryItemDetail::class)->orderBy('sub_code');
    }

    public function maintenanceTransactions()
    {
        return $this->hasMany(InventoryMaintenanceTransaction::class)->latest('transaction_date');
    }

    public function maintenanceSchedules()
    {
        return $this->hasMany(InventoryMaintenanceSchedule::class)->latest('scheduled_at');
    }

    public function getStatusBadgeAttribute()
    {
        return '<span class="badge badge-' . ($this->is_active ? 'success' : 'secondary') . ' app-status-badge">' . ($this->is_active ? 'Aktif' : 'Nonaktif') . '</span>';
    }
}
