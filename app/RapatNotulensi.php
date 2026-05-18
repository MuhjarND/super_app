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
        'rekomendasi_items',
        'dokumentasi_rapat',
        'dokumentasi_files',
        'catatan',
        'file_path',
        'file_nama',
        'file_mime',
        'file_size',
        'notulis_signature_path',
        'notulis_signature_mime',
        'notulis_signature_size',
        'approval_ready',
        'submitted_at',
    ];

    protected $casts = [
        'tidak_membuat_notulen' => 'boolean',
        'approval_ready' => 'boolean',
        'file_size' => 'integer',
        'notulis_signature_size' => 'integer',
        'submitted_at' => 'datetime',
        'rekomendasi_items' => 'array',
        'dokumentasi_files' => 'array',
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

    public function tindakLanjuts()
    {
        return $this->hasMany(RapatNotulensiTindakLanjut::class, 'rapat_notulensi_id');
    }

    public function pendingTindakLanjuts()
    {
        return $this->hasMany(RapatNotulensiTindakLanjut::class, 'rapat_notulensi_id')->where('status', 'pending');
    }

    public function approval()
    {
        return $this->hasOne(RapatNotulensiApproval::class, 'rapat_notulensi_id');
    }

    public function approvalHistories()
    {
        return $this->hasMany(RapatNotulensiApprovalHistory::class, 'rapat_notulensi_id')->orderByDesc('acted_at');
    }

    public function getStatusBadgeAttribute()
    {
        $map = [
            'draft' => ['secondary', 'Draft'],
            'siap' => ['info', 'Siap'],
            'pending_approval' => ['warning', 'Pending Approval'],
            'ditolak' => ['danger', 'Ditolak'],
            'selesai' => ['success', 'Selesai'],
            'tanpa_notulen' => ['dark', 'Tanpa Notulen'],
        ];

        $status = $map[$this->status] ?? ['secondary', ucfirst((string) $this->status)];

        return '<span class="badge badge-' . $status[0] . '">' . $status[1] . '</span>';
    }
}
