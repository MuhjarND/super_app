<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AgendaPimpinan extends Model
{
    protected $fillable = [
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

    public function recipients()
    {
        return $this->belongsToMany(User::class, 'agenda_pimpinan_user')
            ->withPivot('urutan')
            ->orderBy('pivot_urutan');
    }

    public function getWaktuFormattedAttribute()
    {
        return $this->waktu
            ? Carbon::createFromFormat('H:i:s', $this->waktu, 'Asia/Jayapura')->format('H:i')
            : '-';
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
            '*[SMART NOTIF]*',
            'Informasi Agenda Pimpinan',
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
        $lines[] = '*- SMART PTA Papua Barat*';

        return implode("\n", $lines);
    }
}
