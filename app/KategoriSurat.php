<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class KategoriSurat extends Model
{
    protected $fillable = [
        'kode',
        'nama',
        'keterangan',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function suratMasuks()
    {
        return $this->hasMany(SuratMasuk::class, 'kategori_surat_id');
    }

    public function suratKeluars()
    {
        return $this->hasMany(SuratKeluar::class, 'kategori_surat_id');
    }
}
