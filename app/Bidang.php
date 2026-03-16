<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Bidang extends Model
{
    protected $fillable = [
        'nama',
        'kode',
        'keterangan',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'bidang_id');
    }
}
