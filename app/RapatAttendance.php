<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RapatAttendance extends Model
{
    protected $fillable = [
        'rapat_id',
        'user_id',
        'attendance_type',
        'participant_name_snapshot',
        'participant_jabatan_snapshot',
        'guest_instansi',
        'source',
        'signature_path',
        'signature_mime',
        'signature_size',
        'attended_at',
        'created_ip',
    ];

    protected $casts = [
        'attended_at' => 'datetime',
        'signature_size' => 'integer',
    ];

    public function rapat()
    {
        return $this->belongsTo(Rapat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAttendanceBadgeAttribute()
    {
        $map = [
            'internal' => ['info', 'Peserta'],
            'guest' => ['secondary', 'External'],
        ];

        $status = $map[$this->attendance_type] ?? ['secondary', ucfirst((string) $this->attendance_type)];

        return '<span class="badge badge-' . $status[0] . '">' . $status[1] . '</span>';
    }
}
