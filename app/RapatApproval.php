<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RapatApproval extends Model
{
    protected $fillable = [
        'rapat_id',
        'step_order',
        'approver_id',
        'approver_name_snapshot',
        'approver_jabatan_snapshot',
        'status',
        'catatan',
        'acted_at',
        'notified_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function rapat()
    {
        return $this->belongsTo(Rapat::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function histories()
    {
        return $this->hasMany(RapatApprovalHistory::class)->orderByDesc('acted_at');
    }

    public function getStageLabelAttribute()
    {
        if ((int) $this->step_order === 1) {
            return 'Paraf';
        }

        if ((int) $this->step_order === 2) {
            return 'Tanda Tangani';
        }

        return 'Step ' . $this->step_order;
    }
}
