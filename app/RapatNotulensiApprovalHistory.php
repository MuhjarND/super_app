<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RapatNotulensiApprovalHistory extends Model
{
    protected $fillable = [
        'rapat_notulensi_id',
        'rapat_notulensi_approval_id',
        'approver_id',
        'approver_name_snapshot',
        'approver_jabatan_snapshot',
        'action',
        'catatan',
        'acted_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function notulensi()
    {
        return $this->belongsTo(RapatNotulensi::class, 'rapat_notulensi_id');
    }

    public function approval()
    {
        return $this->belongsTo(RapatNotulensiApproval::class, 'rapat_notulensi_approval_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
