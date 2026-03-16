<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Disposisi extends Model
{
    protected $fillable = [
        'surat_masuk_id',
        'dari_user_id',
        'kepada_user_id',
        'dari_jabatan_id',
        'kepada_jabatan_id',
        'petunjuk',
        'catatan',
        'catatan_tindak_lanjut',
        'tipe',
        'status'
    ];

    public const PETUNJUK_OPTIONS = [
        'Sesuai dengan ketentuan yang berlaku',
        'Tidak sesuai dengan ketentuan yang berlaku',
        'Sesuaikan dengan ketentuan yang berlaku',
        'Jawab sesuai dengan ketentuan yang berlaku',
        'Perbaiki',
        'Teliti dan pendapat',
        'Sesuai catatan',
        'Untuk perhatian',
        'Untuk diketahui',
        'Edarkan',
        'Disiapkan',
        'Ingatkan',
        'Dijadwalkan',
        'Bicarakan bersama dan laporkan hasilnya',
        'Simpan',
        'Harap dihadiri/diwakili',
    ];

    public function suratMasuk()
    {
        return $this->belongsTo(SuratMasuk::class, 'surat_masuk_id');
    }

    public function dariUser()
    {
        return $this->belongsTo(User::class, 'dari_user_id');
    }

    public function kepadaUser()
    {
        return $this->belongsTo(User::class, 'kepada_user_id');
    }

    public function dariJabatan()
    {
        return $this->belongsTo(Jabatan::class, 'dari_jabatan_id');
    }

    public function kepadaJabatan()
    {
        return $this->belongsTo(Jabatan::class, 'kepada_jabatan_id');
    }

    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case 'pending':
                return '<span class="badge badge-warning">Pending</span>';
            case 'dibaca':
                return '<span class="badge badge-info">Dibaca</span>';
            case 'ditindaklanjuti':
                return '<span class="badge badge-danger">Ditindaklanjuti</span>';
            default:
                return '<span class="badge badge-secondary">' . $this->status . '</span>';
        }
    }

    public function getTipeBadgeAttribute()
    {
        if ($this->tipe == 'disposisi' && $this->dariJabatan && $this->dariJabatan->kode == 'KASUBAG_TURT') {
            return '<span class="badge badge-danger">Diteruskan</span>';
        }

        if ($this->tipe == 'naikan') {
            return '<span class="badge badge-primary">Dinaikkan</span>';
        }
        return '<span class="badge badge-info">Disposisi</span>';
    }

    public static function getPetunjukOptions()
    {
        return self::PETUNJUK_OPTIONS;
    }
}
