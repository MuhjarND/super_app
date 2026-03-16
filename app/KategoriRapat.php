<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class KategoriRapat extends Model
{
    protected $fillable = [
        'kode',
        'nama',
        'keterangan',
        'butuh_pakaian',
        'aktif',
    ];

    protected $casts = [
        'butuh_pakaian' => 'boolean',
        'aktif' => 'boolean',
    ];
}
