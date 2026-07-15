<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InventoryItemDetail extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'legacy_source_id',
        'sub_code',
        'nup',
        'name',
        'acquisition_date',
        'acquisition_value',
        'inventory_unit_id',
        'inventory_condition_id',
        'inventory_room_id',
        'inventory_brand_id',
        'notes',
        'photo_path',
        'photo_original_name',
        'qr_token',
        'is_active',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'acquisition_value' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->qr_token)) {
                $model->qr_token = (string) Str::uuid();
            }
        });
    }

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function unit()
    {
        return $this->belongsTo(InventoryUnit::class, 'inventory_unit_id');
    }

    public function condition()
    {
        return $this->belongsTo(InventoryCondition::class, 'inventory_condition_id');
    }

    public function room()
    {
        return $this->belongsTo(InventoryRoom::class, 'inventory_room_id');
    }

    public function brand()
    {
        return $this->belongsTo(InventoryBrand::class, 'inventory_brand_id');
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
