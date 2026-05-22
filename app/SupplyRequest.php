<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SupplyRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'request_number',
        'user_id',
        'status',
        'purpose',
        'operator_note',
        'submitted_at',
        'processed_by',
        'processed_at',
        'fulfilled_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'processed_at' => 'datetime',
        'fulfilled_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items()
    {
        return $this->hasMany(SupplyRequestItem::class);
    }

    public function pickups()
    {
        return $this->hasMany(SupplyPickup::class);
    }

    public function getStatusLabelAttribute()
    {
        $map = [
            self::STATUS_PENDING => 'Menunggu Operator',
            self::STATUS_FULFILLED => 'Sudah Diambil',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_CANCELLED => 'Dibatalkan',
        ];

        return $map[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeAttribute()
    {
        $map = [
            self::STATUS_PENDING => ['warning', 'Menunggu'],
            self::STATUS_FULFILLED => ['success', 'Sudah Diambil'],
            self::STATUS_REJECTED => ['danger', 'Ditolak'],
            self::STATUS_CANCELLED => ['secondary', 'Dibatalkan'],
        ];

        $status = $map[$this->status] ?? ['secondary', ucfirst((string) $this->status)];

        return '<span class="badge badge-' . $status[0] . ' app-status-badge">' . $status[1] . '</span>';
    }

    public function getItemsSummaryAttribute()
    {
        return $this->items->map(function ($item) {
            return $item->item_name_snapshot;
        })->filter()->implode(', ');
    }

    public function getQuantitySummaryAttribute()
    {
        return number_format((int) $this->items->sum('quantity_requested'), 0, ',', '.') . ' barang';
    }

    public function getCanBeFulfilledAttribute()
    {
        return $this->status === self::STATUS_PENDING;
    }
}
