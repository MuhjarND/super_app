<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RapatNotulensiTindakLanjut extends Model
{
    protected $fillable = [
        'rapat_notulensi_id',
        'item_index',
        'user_id',
        'public_token',
        'status',
        'deskripsi_snapshot',
        'catatan_penyelesaian',
        'eviden_path',
        'eviden_name',
        'eviden_mime',
        'eviden_size',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'eviden_size' => 'integer',
    ];

    public function notulensi()
    {
        return $this->belongsTo(RapatNotulensi::class, 'rapat_notulensi_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function getStatusBadgeAttribute()
    {
        $map = [
            'pending' => ['warning', 'Belum Ditindaklanjuti'],
            'process' => ['info', 'Proses'],
            'completed' => ['success', 'Selesai'],
        ];

        $status = $map[$this->status] ?? ['secondary', ucfirst((string) $this->status)];

        return '<span class="badge badge-' . $status[0] . '">' . $status[1] . '</span>';
    }

    public function getPublicEvidenceUrlAttribute()
    {
        if (!$this->public_token || !$this->eviden_path) {
            return null;
        }

        return route('rapat.notulensi.follow-ups.eviden.public', $this->public_token);
    }
}
