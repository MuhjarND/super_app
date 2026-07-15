<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryMaintenanceSchedule extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'inventory_item_detail_id',
        'scheduled_at',
        'description',
        'status',
        'notification_completed_at',
        'completed_at',
        'completed_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'notification_completed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function detail()
    {
        return $this->belongsTo(InventoryItemDetail::class, 'inventory_item_detail_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function notifications()
    {
        return $this->hasMany(InventoryMaintenanceScheduleNotification::class);
    }

    public function getStatusLabelAttribute()
    {
        return [
            'scheduled' => 'Terjadwal',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        ][$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusColorAttribute()
    {
        if ($this->status === 'completed') {
            return 'success';
        }

        if ($this->status === 'cancelled') {
            return 'secondary';
        }

        return $this->scheduled_at && $this->scheduled_at->isPast() ? 'danger' : 'primary';
    }

    public function getAssetLabelAttribute()
    {
        if ($this->detail) {
            return trim(($this->detail->sub_code ?: $this->detail->nup ?: '') . ' - ' . $this->detail->name, ' -');
        }

        return $this->item ? trim(($this->item->code ?: '') . ' - ' . $this->item->name, ' -') : '-';
    }
}
