<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Disposisi extends Model
{
    protected $fillable = [
        'legacy_source_id',
        'surat_masuk_id',
        'dari_user_id',
        'kepada_user_id',
        'dari_jabatan_id',
        'kepada_jabatan_id',
        'petunjuk',
        'catatan',
        'catatan_tindak_lanjut',
        'tautan_tindak_lanjut',
        'tipe',
        'status',
        'priority_level',
        'target_tindak_lanjut_at',
        'read_at',
        'completed_at',
        'notification_sent_at',
        'reminder_whatsapp_sent_at',
    ];

    protected $casts = [
        'target_tindak_lanjut_at' => 'datetime',
        'read_at' => 'datetime',
        'completed_at' => 'datetime',
        'notification_sent_at' => 'datetime',
        'reminder_whatsapp_sent_at' => 'datetime',
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

    public function scopeAddressedToUser($query, User $user)
    {
        $userIds = $user->suratMasukAssignmentUserIds();
        $jabatanIds = $user->effectiveJabatanIds();

        return $query->where(function ($targetQuery) use ($userIds, $jabatanIds) {
            if (!empty($userIds)) {
                $targetQuery->whereIn('kepada_user_id', $userIds);
            }

            if (!empty($jabatanIds)) {
                $method = !empty($userIds) ? 'orWhereIn' : 'whereIn';
                $targetQuery->{$method}('kepada_jabatan_id', $jabatanIds);
            }
        });
    }

    public function scopeInvolvingUser($query, User $user)
    {
        $userIds = $user->suratMasukAssignmentUserIds();
        $jabatanIds = $user->effectiveJabatanIds();

        return $query->where(function ($targetQuery) use ($user, $userIds, $jabatanIds) {
            $targetQuery->where('dari_user_id', $user->id);

            if (!empty($userIds)) {
                $targetQuery->orWhereIn('kepada_user_id', $userIds);
            }

            if (!empty($jabatanIds)) {
                $targetQuery->orWhereIn('kepada_jabatan_id', $jabatanIds);
            }
        });
    }

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

    public function dokumentasis()
    {
        return $this->hasMany(DisposisiDokumentasi::class)->latest();
    }

    public function isAddressedTo(User $user)
    {
        if (in_array((int) $this->kepada_user_id, $user->suratMasukAssignmentUserIds(), true)) {
            return true;
        }

        return $this->kepada_jabatan_id
            && in_array((int) $this->kepada_jabatan_id, array_map('intval', $user->effectiveJabatanIds()), true);
    }

    public function assignmentContextFor(User $user)
    {
        if (!$this->isAddressedTo($user)) {
            return null;
        }

        $targetJabatanId = $this->kepada_jabatan_id
            ?: optional($this->kepadaUser)->jabatan_id;
        $delegation = $user->activeDelegationForJabatan($targetJabatanId);

        if (!$delegation) {
            return [
                'mode' => 'direct',
                'badge' => 'Untuk Saya',
                'description' => 'Surat ditujukan langsung kepada ' . $user->name . '.',
                'action_label' => 'Aksi dilakukan sebagai ' . $user->display_jabatan . '.',
                'type' => null,
                'jabatan' => optional($this->kepadaJabatan)->nama ?: $user->display_jabatan,
                'original_user_name' => $user->name,
            ];
        }

        $jabatan = $delegation->jabatan ?: $this->kepadaJabatan;
        $originalUser = null;

        if ($this->kepadaUser
            && (int) $this->kepadaUser->id !== (int) $user->id
            && (int) $this->kepadaUser->jabatan_id === (int) $targetJabatanId) {
            $originalUser = $this->kepadaUser;
        }

        if (!$originalUser && $this->kepadaJabatan) {
            $users = $this->kepadaJabatan->relationLoaded('users')
                ? $this->kepadaJabatan->users
                : $this->kepadaJabatan->users()->active()->get();
            $originalUser = $users->first(function ($candidate) use ($user, $targetJabatanId) {
                return (int) $candidate->id !== (int) $user->id
                    && (int) $candidate->jabatan_id === (int) $targetJabatanId
                    && (bool) $candidate->status_aktif_pegawai;
            });
        }

        $type = strtoupper((string) $delegation->delegation_type);
        $jabatanName = optional($jabatan)->nama ?: 'jabatan yang didelegasikan';
        $originalName = optional($originalUser)->name ?: 'Pejabat definitif ' . $jabatanName;

        return [
            'mode' => 'delegated',
            'badge' => 'Sebagai ' . $type,
            'description' => null,
            'action_label' => 'Aksi akan dicatat atas nama Anda sebagai ' . $type . ' ' . $jabatanName . '.',
            'type' => $type,
            'jabatan' => $jabatanName,
            'original_user_name' => $originalName,
        ];
    }

    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case 'pending':
                return '<span class="badge badge-warning">Pending</span>';
            case 'dibaca':
                return '<span class="badge badge-info">Dibaca</span>';
            case 'diproses':
                return '<span class="badge badge-primary">Diproses</span>';
            case 'ditindaklanjuti':
                return '<span class="badge badge-success">Ditindaklanjuti</span>';
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

    public function getPriorityBadgeAttribute()
    {
        switch ($this->priority_level) {
            case 'high':
                return '<span class="badge badge-danger">Prioritas Tinggi</span>';
            case 'low':
                return '<span class="badge badge-secondary">Prioritas Rendah</span>';
            default:
                return '<span class="badge badge-primary">Prioritas Normal</span>';
        }
    }

    public function getTargetLabelAttribute()
    {
        if (!$this->target_tindak_lanjut_at) {
            return '-';
        }

        if ($this->target_tindak_lanjut_at->isToday()) {
            return 'Hari ini';
        }

        return $this->target_tindak_lanjut_at->translatedFormat('d M Y H:i');
    }

    public function getIsOverdueAttribute()
    {
        if (!$this->target_tindak_lanjut_at || $this->status === 'ditindaklanjuti') {
            return false;
        }

        return $this->target_tindak_lanjut_at->lt(now('Asia/Jayapura'));
    }
}
