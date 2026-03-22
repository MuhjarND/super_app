<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'jabatan_id',
        'jabatan_keterangan',
        'unit_id',
        'bidang_id',
        'hirarki',
        'nip',
        'no_hp',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function bidang()
    {
        return $this->belongsTo(Bidang::class);
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }
        return !!$role->intersect($this->roles)->count();
    }

    public function hasAnyRole($roles)
    {
        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($this->hasRole($role)) {
                    return true;
                }
            }
            return false;
        }
        return $this->hasRole($roles);
    }

    public function isSuperAdmin()
    {
        return $this->hasAnyRole(['super_admin', 'admin']);
    }

    public function isMeetingAdmin()
    {
        return $this->hasRole('admin') || $this->hasRole('super_admin');
    }

    public function isMeetingOperator()
    {
        return $this->hasRole('operator');
    }

    public function isMeetingNotulis()
    {
        return $this->hasAnyRole(['notulis', 'operator']);
    }

    public function isMeetingParticipant()
    {
        return $this->hasAnyRole(['peserta', 'protokoler']);
    }

    public function isMeetingApproval()
    {
        return $this->hasRole('approval');
    }

    public function isMeetingProtokoler()
    {
        return $this->hasRole('protokoler');
    }

    public function hasMeetingRole()
    {
        return $this->isMeetingAdmin()
            || $this->isMeetingOperator()
            || $this->isMeetingNotulis()
            || $this->isMeetingParticipant()
            || $this->isMeetingApproval()
            || $this->isMeetingProtokoler();
    }

    public function canAccessMeetingModule()
    {
        return $this->hasMeetingRole();
    }

    public function canManageRapat()
    {
        return $this->isMeetingAdmin() || $this->isMeetingOperator();
    }

    public function canAccessMeetingMasterData()
    {
        return $this->isMeetingAdmin();
    }

    public function canAccessMeetingMinutes()
    {
        return $this->isMeetingAdmin() || $this->isMeetingOperator() || $this->isMeetingNotulis();
    }

    public function getMonitorableMeetingUnitCodesAttribute()
    {
        if ($this->hasJabatanKode('SEK')) {
            return ['KESEKRETARIATAN', 'KEPEGAWAIAN', 'UMUM', 'PERSURATAN'];
        }

        if ($this->hasJabatanKode('PAN')) {
            return ['KEPANITERAAN'];
        }

        return [];
    }

    public function canMonitorNotulensiFollowUps()
    {
        return !empty($this->monitorable_meeting_unit_codes);
    }

    public function canMonitorFollowUpForUser($targetUser)
    {
        if (!$this->canMonitorNotulensiFollowUps() || !$targetUser) {
            return false;
        }

        return in_array(optional($targetUser->unit)->kode, $this->monitorable_meeting_unit_codes, true);
    }

    public function canAccessMeetingApproval()
    {
        return $this->isMeetingAdmin() || $this->isMeetingApproval();
    }

    public function canAccessAgendaPimpinan()
    {
        return $this->isMeetingAdmin() || $this->isMeetingProtokoler();
    }

    public function canManageVoting()
    {
        return $this->isMeetingAdmin();
    }

    public function canViewRapat($rapat)
    {
        if ($this->canManageRapat()) {
            return true;
        }

        if ((int) $rapat->created_by === (int) $this->id) {
            return true;
        }

        if ((int) $rapat->approver_1_id === (int) $this->id || (int) $rapat->approver_2_id === (int) $this->id) {
            return true;
        }

        return $rapat->pesertas()->where('users.id', $this->id)->exists();
    }

    public function canAccessPersuratanMenu()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasAnyRole([
            'operator_surat_masuk',
            'admin_surat',
            'sekretaris',
            'panitera',
            'ketua',
            'wakil_ketua',
            'kasubag',
            'kabag',
            'panmud',
        ]);
    }

    public function hasJabatanKode($codes)
    {
        $codes = is_array($codes) ? $codes : [$codes];
        return $this->jabatan && in_array($this->jabatan->kode, $codes);
    }

    public function isAdminSurat()
    {
        return $this->hasRole('admin_surat') || $this->hasJabatanKode('ADMIN_SURAT');
    }

    public function isPimpinan()
    {
        return $this->hasJabatanKode(['KPTA', 'WKPTA', 'SEK', 'PAN']);
    }

    public function isKasubagTurt()
    {
        return $this->hasJabatanKode('KASUBAG_TURT');
    }

    public function isKabagAtauKasubag()
    {
        if (!$this->jabatan) {
            return false;
        }

        return (bool) preg_match('/^(KABAG|KASUBAG)_/', $this->jabatan->kode);
    }

    public function requiresPetunjukDisposisi()
    {
        return $this->isPimpinan();
    }

    public function canCreateSuratMasuk()
    {
        return $this->isSuperAdmin() || $this->isAdminSurat();
    }

    public function canManageInitialSuratMasuk()
    {
        return $this->isSuperAdmin() || $this->hasJabatanKode('KASUBAG_TURT');
    }

    public function canManageSuratKeluar()
    {
        return $this->isSuperAdmin() || $this->hasJabatanKode(['KASUBAG_TURT', 'KASUBAG_KEPEG', 'KABAG_KEPEG']);
    }

    public function hasPendingDisposisiForSurat($suratMasuk)
    {
        return $suratMasuk->disposisis()
            ->where('kepada_user_id', $this->id)
            ->where('status', 'pending')
            ->exists();
    }

    public function canViewSuratMasuk($suratMasuk)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->canManageInitialSuratMasuk()) {
            return true;
        }

        if ($this->isAdminSurat()) {
            return (int) $suratMasuk->created_by === (int) $this->id;
        }

        if ((int) $suratMasuk->created_by === (int) $this->id) {
            return true;
        }

        return $suratMasuk->disposisis()
            ->where(function ($query) {
                $query->where('dari_user_id', $this->id)
                    ->orWhere('kepada_user_id', $this->id);
            })
            ->exists();
    }

    public function canForwardSuratMasuk($suratMasuk)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->jabatan || empty($this->jabatan->getTargetDisposisi())) {
            return false;
        }

        if ($suratMasuk->status === 'baru') {
            return $this->canManageInitialSuratMasuk();
        }

        if ($suratMasuk->status !== 'didisposisi') {
            return false;
        }

        return $this->hasPendingDisposisiForSurat($suratMasuk);
    }

    public function canActOnSuratMasuk($suratMasuk)
    {
        return $this->canForwardSuratMasuk($suratMasuk);
    }

    public function canEditSuratMasuk($suratMasuk)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $suratMasuk->status === 'baru' && $this->canManageInitialSuratMasuk();
    }

    public function canDeleteSuratMasuk($suratMasuk)
    {
        return $this->canEditSuratMasuk($suratMasuk);
    }

    public function canViewSuratKeluar($suratKeluar)
    {
        if ($this->canManageSuratKeluar()) {
            return true;
        }

        return $suratKeluar->penerimaInternal()
            ->where('users.id', $this->id)
            ->exists();
    }

    public function canFollowUpDisposisi($disposisi)
    {
        return $this->isSuperAdmin() || (int) $disposisi->kepada_user_id === (int) $this->id;
    }

    public function canOpenTindakLanjutSuratMasuk($suratMasuk)
    {
        return $this->isKabagAtauKasubag() && $this->hasPendingDisposisiForSurat($suratMasuk);
    }

    public function suratMasukCreated()
    {
        return $this->hasMany(SuratMasuk::class, 'created_by');
    }

    public function suratKeluarCreated()
    {
        return $this->hasMany(SuratKeluar::class, 'created_by');
    }

    public function disposisiDiterima()
    {
        return $this->hasMany(Disposisi::class, 'kepada_user_id');
    }

    public function disposisiDikirim()
    {
        return $this->hasMany(Disposisi::class, 'dari_user_id');
    }

    public function tindakLanjutNotulensiItems()
    {
        return $this->hasMany(RapatNotulensiTindakLanjut::class, 'user_id');
    }
}
