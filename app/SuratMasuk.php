<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SuratMasuk extends Model
{
    protected $fillable = [
        'nomor_surat',
        'opsi_pengirim',
        'klasifikasi_kode_id',
        'kategori_surat_id',
        'pengirim',
        'perihal',
        'tanggal_surat',
        'sifat',
        'file_path',
        'status',
        'created_by'
    ];

    protected $dates = ['tanggal_surat'];

    public function scopeVisibleTo($query, $user)
    {
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin() || $user->canManageInitialSuratMasuk()) {
            return $query;
        }

        return $query->where(function ($builder) use ($user) {
            $builder->where('created_by', $user->id);

            if (!$user->isAdminSurat()) {
                $builder->orWhereHas('disposisis', function ($disposisiQuery) use ($user) {
                    $disposisiQuery->where('dari_user_id', $user->id)
                        ->orWhere('kepada_user_id', $user->id);
                });
            }
        });
    }

    public function klasifikasiKode()
    {
        return $this->belongsTo(KlasifikasiKode::class, 'klasifikasi_kode_id');
    }

    public function kategoriSurat()
    {
        return $this->belongsTo(KategoriSurat::class, 'kategori_surat_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function disposisis()
    {
        return $this->hasMany(Disposisi::class, 'surat_masuk_id');
    }

    public function latestDisposisi()
    {
        return $this->hasOne(Disposisi::class, 'surat_masuk_id')->latest();
    }

    public function getNomorSuratLengkapAttribute()
    {
        $klasifikasi = $this->klasifikasiKode ? $this->klasifikasiKode->kode . ' / ' : '';
        return $klasifikasi . $this->nomor_surat;
    }

    public function getPengirimLengkapAttribute()
    {
        $tipe = $this->opsi_pengirim == 'mahkamah_agung' ? 'Mahkamah Agung' : 'Non Mahkamah Agung';
        return $tipe . ' - ' . $this->pengirim;
    }

    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case 'baru':
                return '<span class="badge badge-danger">Baru</span>';
            case 'didisposisi':
                return '<span class="badge badge-warning">Didisposisi</span>';
            case 'selesai':
                return '<span class="badge badge-success">Selesai</span>';
            default:
                return '<span class="badge badge-secondary">' . $this->status . '</span>';
        }
    }
}
