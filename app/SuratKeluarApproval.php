<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SuratKeluarApproval extends Model
{
    protected $fillable = [
        'surat_keluar_id',
        'approver_id',
        'requested_by',
        'template_slug',
        'template_name',
        'rendered_body',
        'field_values',
        'signer_name_snapshot',
        'signer_title_snapshot',
        'status',
        'note',
        'acted_at',
    ];

    protected $casts = [
        'field_values' => 'array',
        'acted_at' => 'datetime',
    ];

    public function suratKeluar()
    {
        return $this->belongsTo(SuratKeluar::class, 'surat_keluar_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function histories()
    {
        return $this->hasMany(SuratKeluarApprovalHistory::class, 'surat_keluar_approval_id');
    }

    public function getStatusLabelAttribute()
    {
        $map = [
            'pending' => 'Pending',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ];

        return $map[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeClassAttribute()
    {
        $map = [
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
        ];

        return $map[$this->status] ?? 'secondary';
    }
}
