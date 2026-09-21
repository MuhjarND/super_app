<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SupplyOpnameReport extends Model
{
    protected $fillable = [
        'report_period',
        'opname_date',
        'surat_keluar_id',
        'nomor_surat',
        'nomor_urut',
        'tahun_surat',
        'snapshot',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'report_period' => 'date',
        'opname_date' => 'date',
        'snapshot' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function suratKeluar()
    {
        return $this->belongsTo(SuratKeluar::class, 'surat_keluar_id');
    }
}
