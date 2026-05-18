<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RapatNotulensiApproval extends Model
{
    protected $fillable = [
        'rapat_notulensi_id',
        'approver_id',
        'approver_name_snapshot',
        'approver_jabatan_snapshot',
        'status',
        'catatan',
        'acted_at',
        'signature_path',
        'signature_mime',
        'signature_size',
        'notified_at',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
        'notified_at' => 'datetime',
    ];

    public function notulensi()
    {
        return $this->belongsTo(RapatNotulensi::class, 'rapat_notulensi_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function histories()
    {
        return $this->hasMany(RapatNotulensiApprovalHistory::class)->orderByDesc('acted_at');
    }

    public function getStageLabelAttribute()
    {
        return 'Tanda Tangani Notulen';
    }
}
