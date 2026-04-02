<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryMaintenanceTransaction extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'inventory_item_detail_id',
        'legacy_source_id',
        'transaction_date',
        'description',
        'amount',
        'source_type',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function detail()
    {
        return $this->belongsTo(InventoryItemDetail::class, 'inventory_item_detail_id');
    }

    public function attachments()
    {
        return $this->hasMany(InventoryTransactionAttachment::class)->latest();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getStatusBadgeAttribute()
    {
        $map = [
            'draft' => ['secondary', 'Draft'],
            'completed' => ['success', 'Selesai'],
            'imported' => ['info', 'Import'],
        ];

        $badge = $map[$this->status] ?? ['primary', ucfirst($this->status ?: 'Aktif')];

        return '<span class="badge badge-' . $badge[0] . ' app-status-badge">' . $badge[1] . '</span>';
    }
}
