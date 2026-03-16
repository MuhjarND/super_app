<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RapatApprovalHistory extends Model
{
    protected $fillable = [
        'rapat_id',
        'rapat_approval_id',
        'approver_id',
        'step_order',
        'approver_name_snapshot',
        'approver_jabatan_snapshot',
        'action',
        'catatan',
        'acted_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
    ];

    public function rapat()
    {
        return $this->belongsTo(Rapat::class);
    }

    public function approval()
    {
        return $this->belongsTo(RapatApproval::class, 'rapat_approval_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
