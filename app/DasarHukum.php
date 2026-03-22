<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DasarHukum extends Model
{
    protected $fillable = [
        'tema',
        'kategori_surat_kode_id',
        'kata_kunci',
        'uraian',
        'urutan',
        'aktif',
    ];

    protected $casts = [
        'urutan' => 'integer',
        'aktif' => 'boolean',
    ];

    public function kategoriSuratKode()
    {
        return $this->belongsTo(KlasifikasiKode::class, 'kategori_surat_kode_id');
    }
}
