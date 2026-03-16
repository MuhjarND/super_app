<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RapatNotulensi extends Model
{
    protected $fillable = [
        'rapat_id',
        'notulis_id',
        'created_by',
        'updated_by',
        'mode',
        'status',
        'tidak_membuat_notulen',
        'judul',
        'uraian_kegiatan',
        'agenda_rapat',
        'susunan_agenda',
        'hasil_rapat',
        'rekomendasi',
        'dokumentasi_rapat',
        'catatan',
        'file_path',
        'file_nama',
        'file_mime',
        'file_size',
        'approval_ready',
        'submitted_at',
    ];

    protected $casts = [
        'tidak_membuat_notulen' => 'boolean',
        'approval_ready' => 'boolean',
        'file_size' => 'integer',
        'submitted_at' => 'datetime',
    ];

    public function rapat()
    {
        return $this->belongsTo(Rapat::class);
    }

    public function notulis()
    {
        return $this->belongsTo(User::class, 'notulis_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getStatusBadgeAttribute()
    {
        $map = [
            'draft' => ['secondary', 'Draft'],
            'siap' => ['info', 'Siap'],
            'selesai' => ['success', 'Selesai'],
            'tanpa_notulen' => ['dark', 'Tanpa Notulen'],
        ];

        $status = $map[$this->status] ?? ['secondary', ucfirst((string) $this->status)];

        return '<span class="badge badge-' . $status[0] . '">' . $status[1] . '</span>';
    }
}
