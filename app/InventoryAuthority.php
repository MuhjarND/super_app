<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryAuthority extends Model
{
    protected $fillable = ['legacy_source_id', 'name', 'nip', 'position', 'notes', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
