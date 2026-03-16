<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'nama',
        'kode',
        'keterangan',
    ];

    public function jabatans()
    {
        return $this->hasMany(Jabatan::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
