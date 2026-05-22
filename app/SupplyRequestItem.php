<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SupplyRequestItem extends Model
{
    protected $fillable = [
        'supply_request_id',
        'supply_item_id',
        'item_name_snapshot',
        'unit_snapshot',
        'quantity_requested',
        'quantity_fulfilled',
    ];

    protected $casts = [
        'quantity_requested' => 'integer',
        'quantity_fulfilled' => 'integer',
    ];

    public function request()
    {
        return $this->belongsTo(SupplyRequest::class, 'supply_request_id');
    }

    public function item()
    {
        return $this->belongsTo(SupplyItem::class, 'supply_item_id');
    }

    public function pickups()
    {
        return $this->hasMany(SupplyPickup::class);
    }

    public function getQuantityLabelAttribute()
    {
        return number_format((int) $this->quantity_requested, 0, ',', '.') . ' ' . $this->unit_snapshot;
    }
}
