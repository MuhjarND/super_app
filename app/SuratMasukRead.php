<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SuratMasukRead extends Model
{
    protected $table = 'surat_masuk_reads';

    protected $fillable = [
        'surat_masuk_id',
        'user_id',
        'read_at',
    ];

    protected $dates = [
        'read_at',
    ];

    public function suratMasuk()
    {
        return $this->belongsTo(SuratMasuk::class, 'surat_masuk_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
