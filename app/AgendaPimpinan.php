<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AgendaPimpinan extends Model
{
    protected $fillable = [
        'surat_masuk_id',
        'tanggal_kegiatan',
        'judul_agenda',
        'tempat',
        'waktu',
        'yang_menghadiri',
        'seragam_pakaian',
        'nomor_naskah_dinas',
        'lampiran_link',
        'catatan',
        'created_by',
        'updated_by',
        'last_notified_at',
    ];

    protected $casts = [
        'tanggal_kegiatan' => 'date',
        'last_notified_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function suratMasuk()
    {
        return $this->belongsTo(SuratMasuk::class, 'surat_masuk_id');
    }

    public function recipients()
    {
        return $this->belongsToMany(User::class, 'agenda_pimpinan_user')
            ->withPivot('urutan')
            ->orderBy('pivot_urutan');
    }

    public function scopeVisibleTo($query, User $user)
    {
        if ($user->canManageAgendaPimpinanParticipants()) {
            return $query;
        }

        return $query->whereHas('recipients', function ($recipientQuery) use ($user) {
            $recipientQuery->where('users.id', $user->id);
        });
    }

    public function getWaktuFormattedAttribute()
    {
        if (!$this->waktu) {
            return '-';
        }

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                return Carbon::createFromFormat($format, $this->waktu, 'Asia/Jayapura')->format('H:i');
            } catch (\Throwable $e) {
                // Continue to the next supported time format.
            }
        }

        try {
            return Carbon::parse($this->waktu, 'Asia/Jayapura')->format('H:i');
        } catch (\Throwable $e) {
            return (string) $this->waktu;
        }
    }

    public function getTanggalFormattedAttribute()
    {
        return $this->tanggal_kegiatan
            ? $this->tanggal_kegiatan->copy()->timezone('Asia/Jayapura')->translatedFormat('d M Y')
            : '-';
    }

    public function getWhatsappPreviewAttribute()
    {
        $lines = [
            'Yth. Bapak/Ibu,',
            'Dengan hormat, berikut disampaikan informasi agenda pimpinan.',
            '',
            'Judul: ' . $this->judul_agenda,
            'Tanggal: ' . $this->tanggal_formatted,
            'Waktu: ' . $this->waktu_formatted . ' WIT',
            'Tempat: ' . $this->tempat,
        ];

        if ($this->yang_menghadiri) {
            $lines[] = 'Yang Menghadiri: ' . $this->yang_menghadiri;
        }

        if ($this->seragam_pakaian) {
            $lines[] = 'Pakaian: ' . $this->seragam_pakaian;
        }

        if ($this->nomor_naskah_dinas) {
            $lines[] = 'Nomor Naskah Dinas: ' . $this->nomor_naskah_dinas;
        }

        if ($this->lampiran_link) {
            $lines[] = 'Lampiran: ' . $this->lampiran_link;
        }

        $lines[] = '';
        $lines[] = 'Hormat kami,';
        $lines[] = 'PAPEDA';

        return implode("\n", $lines);
    }
}
