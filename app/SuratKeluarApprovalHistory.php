<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SuratKeluarApprovalHistory extends Model
{
    protected $fillable = [
        'surat_keluar_approval_id',
        'surat_keluar_id',
        'approver_id',
        'action',
        'note',
        'signer_name_snapshot',
        'signer_title_snapshot',
        'acted_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function approval()
    {
        return $this->belongsTo(SuratKeluarApproval::class, 'surat_keluar_approval_id');
    }

    public function suratKeluar()
    {
        return $this->belongsTo(SuratKeluar::class, 'surat_keluar_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
