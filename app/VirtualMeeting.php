<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class VirtualMeeting extends Model
{
    protected $fillable = [
        'surat_masuk_id',
        'judul',
        'tanggal_kegiatan',
        'waktu_mulai',
        'waktu_selesai',
        'zoom_link',
        'catatan',
        'created_by',
        'updated_by',
        'last_notified_at',
    ];

    protected $casts = [
        'tanggal_kegiatan' => 'date',
        'last_notified_at' => 'datetime',
    ];

    public function suratMasuk()
    {
        return $this->belongsTo(SuratMasuk::class, 'surat_masuk_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'virtual_meeting_user')
            ->withPivot('urutan')
            ->withTimestamps()
            ->orderBy('pivot_urutan');
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->canManageVirtualMeetings()) {
            return $query;
        }

        return $query->whereHas('participants', function ($participantQuery) use ($user) {
            $participantQuery->where('users.id', $user->id);
        });
    }

    public function getTanggalFormattedAttribute()
    {
        return $this->tanggal_kegiatan
            ? $this->tanggal_kegiatan->copy()->timezone('Asia/Jayapura')->translatedFormat('d M Y')
            : '-';
    }

    public function getWaktuMulaiFormattedAttribute()
    {
        return $this->formatTime($this->waktu_mulai);
    }

    public function getWaktuSelesaiFormattedAttribute()
    {
        return $this->formatTime($this->waktu_selesai);
    }

    public function getJadwalFormattedAttribute()
    {
        $time = $this->waktu_mulai_formatted . ' WIT';
        if ($this->waktu_selesai) {
            $time .= ' - ' . $this->waktu_selesai_formatted . ' WIT';
        }

        return $this->tanggal_formatted . ', ' . $time;
    }

    protected function formatTime($time)
    {
        if (!$time) {
            return '-';
        }

        try {
            return Carbon::parse($time, 'Asia/Jayapura')->format('H:i');
        } catch (\Throwable $e) {
            return (string) $time;
        }
    }
}
