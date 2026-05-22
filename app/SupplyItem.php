<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SupplyItem extends Model
{
    protected $fillable = [
        'code',
        'name',
        'unit',
        'stock',
        'minimum_stock',
        'description',
        'image_path',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'stock' => 'integer',
        'minimum_stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function requestItems()
    {
        return $this->hasMany(SupplyRequestItem::class);
    }

    public function pickups()
    {
        return $this->hasMany(SupplyPickup::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getStockLabelAttribute()
    {
        return number_format((int) $this->stock, 0, ',', '.') . ' ' . $this->unit;
    }

    public function getIsLowStockAttribute()
    {
        return (int) $this->minimum_stock > 0 && (int) $this->stock <= (int) $this->minimum_stock;
    }

    public function getImageUrlAttribute()
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }
}
