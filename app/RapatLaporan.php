<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RapatLaporan extends Model
{
    protected $fillable = [
        'rapat_id',
        'jenis',
        'judul',
        'deskripsi',
        'bab_1_latar_belakang',
        'bab_1_dasar',
        'bab_1_tujuan',
        'bab_2_hasil_monitoring',
        'bab_3_tindak_lanjut',
        'status',
        'is_ready',
        'file_path',
        'file_nama',
        'file_mime',
        'file_size',
        'archived_at',
        'generated_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_ready' => 'boolean',
        'file_size' => 'integer',
        'archived_at' => 'datetime',
        'generated_at' => 'datetime',
    ];

    public function rapat()
    {
        return $this->belongsTo(Rapat::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getJenisLabelAttribute()
    {
        $map = [
            'gabungan' => 'Gabungan',
            'tindak_lanjut' => 'Tindak Lanjut',
        ];

        return $map[$this->jenis] ?? ucfirst(str_replace('_', ' ', (string) $this->jenis));
    }

    public function getStatusBadgeAttribute()
    {
        if ($this->archived_at) {
            return '<span class="badge badge-dark">Arsip</span>';
        }

        if ($this->file_path) {
            return '<span class="badge badge-success">Final File</span>';
        }

        if ($this->is_ready) {
            return '<span class="badge badge-info">Siap Generate</span>';
        }

        return '<span class="badge badge-warning">Belum Siap</span>';
    }
}
