<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryBrand extends Model
{
    protected $fillable = ['legacy_source_id', 'name', 'description'];

    public function itemDetails()
    {
        return $this->hasMany(InventoryItemDetail::class);
    }
}
