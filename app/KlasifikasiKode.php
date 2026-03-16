<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class KlasifikasiKode extends Model
{
    protected $fillable = ['kode', 'nama', 'tipe', 'parent_id'];

    public function parent()
    {
        return $this->belongsTo(KlasifikasiKode::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(KlasifikasiKode::class, 'parent_id');
    }

    public function getFullKodeAttribute()
    {
        return $this->kode . ' - ' . $this->nama;
    }
}
