<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZiActivityApproval extends Model
{
    protected $fillable = [
        'zi_activity_id',
        'approver_id',
        'requested_by',
        'status',
        'request_notes',
        'review_notes',
        'requested_at',
        'acted_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'acted_at' => 'datetime',
    ];

    public function activity()
    {
        return $this->belongsTo(ZiActivity::class, 'zi_activity_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function getStatusLabelAttribute()
    {
        $map = [
            'pending' => 'Pending Review',
            'approved' => 'Disetujui',
            'rejected' => 'Perlu Perbaikan',
        ];

        return $map[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeAttribute()
    {
        $map = [
            'pending' => ['warning', 'Pending Review'],
            'approved' => ['success', 'Disetujui'],
            'rejected' => ['danger', 'Perlu Perbaikan'],
        ];

        [$class, $label] = $map[$this->status] ?? ['secondary', $this->status_label];

        return '<span class="badge badge-' . $class . ' app-status-badge">' . $label . '</span>';
    }
}
