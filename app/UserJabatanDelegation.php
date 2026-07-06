<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserJabatanDelegation extends Model
{
    protected $fillable = [
        'user_id',
        'jabatan_id',
        'delegation_type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function getTypeLabelAttribute()
    {
        return strtoupper((string) $this->delegation_type);
    }
}
