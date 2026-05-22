<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SupplyPickup extends Model
{
    protected $fillable = [
        'supply_request_id',
        'supply_request_item_id',
        'supply_item_id',
        'user_id',
        'item_name_snapshot',
        'unit_snapshot',
        'quantity',
        'purpose',
        'pickup_date',
        'receiver_signature_path',
        'receiver_signature_mime',
        'receiver_signature_size',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'pickup_date' => 'date',
        'receiver_signature_size' => 'integer',
    ];

    public function request()
    {
        return $this->belongsTo(SupplyRequest::class, 'supply_request_id');
    }

    public function requestItem()
    {
        return $this->belongsTo(SupplyRequestItem::class, 'supply_request_item_id');
    }

    public function item()
    {
        return $this->belongsTo(SupplyItem::class, 'supply_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getQuantityLabelAttribute()
    {
        return number_format((int) $this->quantity, 0, ',', '.') . ' ' . $this->unit_snapshot;
    }
}
