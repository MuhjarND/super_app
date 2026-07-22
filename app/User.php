<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    protected $effectiveJabatansCache = null;
    protected $effectiveAssignmentUserIdsCache = null;
    protected $suratMasukAssignmentUserIdsCache = null;
    protected $delegatedStructuralRoleNamesCache = null;
    protected $hasAssignedMeetingFollowUpsCache = null;

    use Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'profile_photo_path',
        'profile_signature_path',
        'profile_signature_mime',
        'profile_signature_size',
        'profile_signature_method',
        'two_factor_secret',
        'two_factor_enabled',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
        'jabatan_id',
        'jabatan_keterangan',
        'unit_id',
        'hirarki',
        'nip',
        'no_hp',
        'status_asn',
        'tmt_pns',
        'golongan_ruang',
        'satuan_kerja',
        'atasan_langsung_id',
        'pejabat_berwenang_id',
        'jumlah_anak',
        'status_aktif_pegawai',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'tmt_pns' => 'date',
        'jumlah_anak' => 'integer',
        'status_aktif_pegawai' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'two_factor_confirmed_at' => 'datetime',
    ];

    public function hasProfileSignature()
    {
        return !empty($this->profile_signature_path);
    }

    public function getProfileSignatureUrlAttribute()
    {
        return $this->profile_signature_path ? asset('storage/' . $this->profile_signature_path) : null;
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function jabatanDelegations()
    {
        return $this->hasMany(UserJabatanDelegation::class);
    }

    public function activeJabatanDelegations()
    {
        return $this->jabatanDelegations()->where('is_active', true)->with('jabatan');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function atasanLangsung()
    {
        return $this->belongsTo(self::class, 'atasan_langsung_id');
    }

    public function pejabatBerwenang()
    {
        return $this->belongsTo(self::class, 'pejabat_berwenang_id');
    }

    public function getDisplayJabatanAttribute()
    {
        return $this->jabatan_keterangan ?: optional($this->jabatan)->nama ?: '-';
    }

    public function getMasaKerjaAttribute()
    {
        if (!$this->tmt_pns) {
            return null;
        }

        $startDate = $this->tmt_pns->copy()->startOfDay();
        $currentDate = now()->startOfDay();

        if ($startDate->gt($currentDate)) {
            return '0 hari';
        }

        $period = $startDate->diff($currentDate);
        $parts = [];

        if ($period->y > 0) {
            $parts[] = $period->y . ' tahun';
        }

        if ($period->m > 0) {
            $parts[] = $period->m . ' bulan';
        }

        if (empty($parts)) {
            $parts[] = '0 bulan';
        }

        return implode(' ', $parts);
    }

    public function hasTwoFactorEnabled()
    {
        return (bool) $this->two_factor_enabled && !empty($this->two_factor_secret) && !is_null($this->two_factor_confirmed_at);
    }

    public function scopeOrdered($query)
    {
        return $query
            ->orderByRaw('CASE WHEN hirarki IS NULL THEN 1 ELSE 0 END')
            ->orderBy('hirarki')
            ->orderBy('name');
    }

    public function scopeActive($query)
    {
        return $query->where('status_aktif_pegawai', true);
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->contains('name', $role) || $this->hasDelegatedRole($role);
        }

        return collect($role)->contains(function ($candidate) {
            return $this->hasRole(is_string($candidate) ? $candidate : optional($candidate)->name);
        });
    }

    protected function hasDelegatedRole($role)
    {
        return in_array($role, $this->delegatedRoleNames(), true);
    }

    protected function delegatedRoleNames()
    {
        $delegations = $this->relationLoaded('activeJabatanDelegations')
            ? $this->activeJabatanDelegations
            : $this->activeJabatanDelegations()->get();

        return $delegations
            ->map(function ($delegation) {
                return optional($delegation->jabatan)->kode;
            })
            ->filter()
            ->flatMap(function ($kode) {
                if ($kode === 'KPTA') {
                    return ['ketua', 'approval', 'peserta', 'atasan_langsung'];
                }
                if ($kode === 'WKPTA') {
                    return ['wakil_ketua', 'approval', 'peserta', 'atasan_langsung'];
                }
                if ($kode === 'SEK') {
                    return ['sekretaris', 'approval', 'peserta', 'atasan_langsung'];
                }
                if ($kode === 'PAN') {
                    return ['panitera', 'approval', 'peserta', 'atasan_langsung'];
                }
                if (strpos($kode, 'KABAG_') === 0) {
                    return ['kabag', 'peserta', 'atasan_langsung'];
                }
                if (strpos($kode, 'KASUBAG_') === 0) {
                    return ['kasubag', 'peserta', 'atasan_langsung'];
                }
                if (strpos($kode, 'PANMUD_') === 0) {
                    return ['panmud', 'peserta', 'atasan_langsung'];
                }

                return [];
            })
            ->unique()
            ->values()
            ->all();
    }

    public function activeDelegationLabels()
    {
        $delegations = $this->relationLoaded('activeJabatanDelegations')
            ? $this->activeJabatanDelegations
            : $this->activeJabatanDelegations()->get();

        return $delegations
            ->map(function ($delegation) {
                $jabatanName = optional($delegation->jabatan)->nama;

                return $jabatanName ? trim($delegation->type_label . ' ' . $jabatanName) : null;
            })
            ->filter()
            ->values();
    }

    public function scopeWithRoleOrDelegatedJabatan($query, $roles)
    {
        $roles = collect(is_array($roles) ? $roles : [$roles])->filter()->values()->all();
        $jabatanCodes = static::jabatanCodesForRoleNames($roles);

        return $query->where(function ($roleQuery) use ($roles, $jabatanCodes) {
            $roleQuery->whereHas('roles', function ($query) use ($roles) {
                $query->whereIn('name', $roles);
            });

            if (!empty($jabatanCodes)) {
                $roleQuery
                    ->orWhereHas('jabatan', function ($query) use ($jabatanCodes) {
                        $query->whereIn('kode', $jabatanCodes);
                    })
                    ->orWhereHas('activeJabatanDelegations.jabatan', function ($query) use ($jabatanCodes) {
                        $query->whereIn('kode', $jabatanCodes);
                    });
            }
        });
    }

    public static function jabatanCodesForRoleNames($roles)
    {
        $map = [
            'ketua' => ['KPTA'],
            'wakil_ketua' => ['WKPTA'],
            'sekretaris' => ['SEK'],
            'panitera' => ['PAN'],
            'approval' => ['KPTA', 'WKPTA', 'SEK', 'PAN'],
            'atasan_langsung' => ['KPTA', 'WKPTA', 'SEK', 'PAN', 'KABAG_KEPEG', 'KABAG_UMUM', 'KASUBAG_KEPEG', 'KASUBAG_RENPRO', 'KASUBAG_LAPKEU', 'KASUBAG_TURT', 'PANMUD_BANDING', 'PANMUD_HUKUM'],
            'kabag' => ['KABAG_KEPEG', 'KABAG_UMUM'],
            'kasubag' => ['KASUBAG_KEPEG', 'KASUBAG_RENPRO', 'KASUBAG_LAPKEU', 'KASUBAG_TURT'],
            'panmud' => ['PANMUD_BANDING', 'PANMUD_HUKUM'],
        ];

        return collect(is_array($roles) ? $roles : [$roles])
            ->flatMap(function ($role) use ($map) {
                return $map[$role] ?? [];
            })
            ->unique()
            ->values()
            ->all();
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

    public function isSatker()
    {
        return $this->hasRole('satker');
    }

    public function hasMeetingRole()
    {
        return $this->isMeetingAdmin()
            || $this->isMeetingOperator()
            || $this->isMeetingNotulis()
            || $this->isMeetingParticipant()
            || $this->isMeetingApproval()
            || $this->isMeetingProtokoler()
            || $this->isSatker();
    }

    public function canAccessMeetingModule()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasMeetingRole();
    }

    public function canManageRapat()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->isMeetingAdmin() || $this->isMeetingOperator();
    }

    public function canAccessMeetingMasterData()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->isMeetingAdmin();
    }

    public function canAccessMeetingMinutes()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->canManageMeetingMinutes() || $this->isSatker();
    }

    public function canManageMeetingMinutes()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->isMeetingAdmin() || $this->isMeetingOperator() || $this->isMeetingNotulis();
    }

    public function canAccessMeetingReports()
    {
        return $this->canAccessMeetingModule() && !$this->isSatker();
    }

    public function getMonitorableMeetingUnitCodesAttribute()
    {
        if ($this->hasJabatanKode('SEK')) {
            return ['KESEKRETARIATAN'];
        }

        if ($this->hasJabatanKode('PAN')) {
            return ['KEPANITERAAN'];
        }

        return [];
    }

    public function canMonitorNotulensiFollowUps()
    {
        return $this->canMonitorAllMeetingFollowUps()
            || !empty($this->monitorable_meeting_unit_codes);
    }

    public function canMonitorAllMeetingFollowUps()
    {
        return $this->isSuperAdmin() || $this->isPimpinan();
    }

    public function canMonitorFollowUpForUser($targetUser)
    {
        if (!$this->canMonitorNotulensiFollowUps() || !$targetUser) {
            return false;
        }

        if ($this->canMonitorAllMeetingFollowUps()) {
            return true;
        }

        return in_array(optional($targetUser->unit)->kode, $this->monitorable_meeting_unit_codes, true);
    }

    public function hasAssignedMeetingFollowUps()
    {
        if ($this->relationLoaded('tindakLanjutNotulensiItems')) {
            return $this->tindakLanjutNotulensiItems->isNotEmpty();
        }

        if ($this->hasAssignedMeetingFollowUpsCache === null) {
            $this->hasAssignedMeetingFollowUpsCache = $this->tindakLanjutNotulensiItems()->exists();
        }

        return $this->hasAssignedMeetingFollowUpsCache;
    }

    public function canAccessMeetingFollowUps()
    {
        if ($this->isSatker()) {
            return false;
        }

        return $this->canAccessMeetingModule()
            || $this->canMonitorNotulensiFollowUps()
            || $this->hasAssignedMeetingFollowUps();
    }

    public function canManageMeetingFollowUp($followUp)
    {
        return $this->canManageMeetingMinutes()
            || ($followUp && (int) $followUp->user_id === (int) $this->id);
    }

    public function canViewMeetingFollowUp($followUp)
    {
        if ($this->canManageMeetingFollowUp($followUp)) {
            return true;
        }

        if (!$followUp || !$this->canMonitorNotulensiFollowUps()) {
            return false;
        }

        if ($this->canMonitorAllMeetingFollowUps()) {
            return true;
        }

        return $this->canMonitorFollowUpForUser($followUp->user);
    }

    public function canAccessMeetingApproval()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->isMeetingAdmin() || $this->isMeetingApproval();
    }

    public function canAccessAgendaPimpinan()
    {
        return $this->canManageAgendaPimpinanParticipants()
            || $this->hasTaggedAgendaPimpinan();
    }

    public function agendaPimpinans()
    {
        return $this->belongsToMany(AgendaPimpinan::class, 'agenda_pimpinan_user')
            ->withPivot('urutan');
    }

    public function canManageAgendaPimpinanDetails()
    {
        return $this->isSuperAdmin() || $this->isMeetingAdmin();
    }

    public function canManageAgendaPimpinanParticipants()
    {
        return $this->canManageAgendaPimpinanDetails() || $this->isMeetingProtokoler();
    }

    public function hasTaggedAgendaPimpinan()
    {
        if ($this->relationLoaded('agendaPimpinans')) {
            return $this->agendaPimpinans->isNotEmpty();
        }

        return $this->exists && $this->agendaPimpinans()->exists();
    }

    public function virtualMeetings()
    {
        return $this->belongsToMany(VirtualMeeting::class, 'virtual_meeting_user')
            ->withPivot('urutan')
            ->withTimestamps();
    }

    public function canManageVirtualMeetings()
    {
        return $this->isSuperAdmin()
            || $this->isMeetingAdmin()
            || $this->isMeetingOperator()
            || $this->isMeetingProtokoler();
    }

    public function canAccessVirtualMeetings()
    {
        return $this->canManageVirtualMeetings() || $this->hasTaggedVirtualMeeting();
    }

    public function hasTaggedVirtualMeeting()
    {
        if ($this->relationLoaded('virtualMeetings')) {
            return $this->virtualMeetings->isNotEmpty();
        }

        return $this->exists && $this->virtualMeetings()->exists();
    }

    public function canManageVoting()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->isMeetingAdmin();
    }

    public function canAccessVoting()
    {
        return !$this->isSatker();
    }

    public function canViewRapat($rapat)
    {
        if ($this->canManageRapat()) {
            return true;
        }

        if ($this->isSatker()) {
            return (bool) $rapat->bersama_satker
                && in_array($rapat->status, ['disetujui', 'selesai'], true)
                && $rapat->pesertas()->where('users.id', $this->id)->exists();
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

        return $this->canAccessSuratMasukMenu()
            || $this->canAccessSuratKeluarMenu()
            || $this->canAccessSuratTemplateMenu();
    }

    public function canAccessSuratMasukMenu()
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
            'peserta',
            'pegawai',
        ]);
    }

    public function canAccessSuratKeluarMenu()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasAnyRole([
            'admin_surat',
            'sekretaris',
            'panitera',
            'ketua',
            'wakil_ketua',
            'kasubag',
            'kabag',
            'panmud',
            'peserta',
            'pegawai',
        ]);
    }

    public function canAccessSuratTemplateMenu()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->isKabagAtauKasubag();
    }

    public function canManageSuratTemplates()
    {
        return $this->isSuperAdmin();
    }

    public function canSubmitSuratTemplateProposal()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->isKabagAtauKasubag();
    }

    public function canAccessArsipMenu()
    {
        return $this->isSuperAdmin();
    }

    public function canAccessArchiveMenu()
    {
        return $this->canAccessArsipMenu();
    }

    public function canAccessIntegratedCalendar()
    {
        return $this->canAccessMeetingModule()
            || $this->canAccessAgendaPimpinan()
            || $this->canAccessVirtualMeetings()
            || $this->canAccessLeaveModule()
            || $this->canAccessProgressZiModule()
            || $this->canAccessInventoryModule();
    }

    public function canAccessUnifiedActionCenter()
    {
        return $this->canAccessPersuratanMenu()
            || $this->canAccessMeetingModule()
            || $this->canAccessMeetingFollowUps()
            || $this->canAccessLeaveModule()
            || $this->canAccessProgressZiModule()
            || $this->canAccessInventoryModule()
            || $this->canAccessApprovalCenter();
    }

    public function canAccessLeadershipDashboard()
    {
        return $this->isSuperAdmin() || $this->isPimpinan();
    }

    public function canAccessProgressZiModule()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasAnyRole([
            'admin',
            'approval',
            'sekretaris',
            'panitera',
            'kabag',
            'kasubag',
            'pegawai',
            'peserta',
            'admin_surat',
            'operator_surat_masuk',
            'operator',
            'protokoler',
            'panmud',
        ]);
    }

    public function canManageProgressZiMasterData()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasAnyRole([
            'admin',
            'approval',
            'sekretaris',
            'panitera',
            'kabag',
            'kasubag',
        ]);
    }

    public function canVerifyProgressZi()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasAnyRole([
            'admin',
            'approval',
            'sekretaris',
            'panitera',
            'kabag',
            'kasubag',
        ]);
    }

    public function canManageProgressZiActivity($activity = null)
    {
        if ($this->canManageProgressZiMasterData()) {
            return true;
        }

        if (!$activity) {
            return false;
        }

        if ((int) data_get($activity, 'pic_user_id') === (int) $this->id) {
            return true;
        }

        $areaPics = data_get($activity, 'area.pics');
        if ($areaPics && $areaPics->contains('id', $this->id)) {
            return true;
        }

        $area = data_get($activity, 'area');
        if ($area && method_exists($area, 'pics') && $area->pics()->where('users.id', $this->id)->exists()) {
            return true;
        }

        if ((int) data_get($activity, 'area.pic_user_id') === (int) $this->id) {
            return true;
        }

        return false;
    }

    public function canManageProgressZiArea($area = null)
    {
        if ($this->canManageProgressZiMasterData()) {
            return true;
        }

        if (!$area) {
            return false;
        }

        $pics = data_get($area, 'pics');
        if ($pics && $pics->contains('id', $this->id)) {
            return true;
        }

        if (method_exists($area, 'pics') && $area->pics()->where('users.id', $this->id)->exists()) {
            return true;
        }

        return (int) data_get($area, 'pic_user_id') === (int) $this->id;
    }

    public function canAccessInventoryModule()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasAnyRole([
            'operator_siperlatin',
            'admin',
            'sekretaris',
            'panitera',
            'kabag',
            'kasubag',
            'panmud',
            'pegawai',
            'peserta',
        ]);
    }

    public function canManageInventoryMasterData()
    {
        return $this->canManageInventoryModule();
    }

    public function canManageInventoryTransactions()
    {
        return $this->canManageInventoryModule();
    }

    public function canManageInventoryModule()
    {
        return $this->isSuperAdmin()
            || $this->hasRole('operator_siperlatin')
            || $this->hasJabatanKode(['KASUBAG_TURT', 'KASUBAG_LAPKEU']);
    }

    public function canScheduleInventoryMaintenance()
    {
        return $this->isSuperAdmin() || $this->hasRole('operator_siperlatin');
    }

    public function canAccessSupplyModule()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasAnyRole([
            'operator_persediaan',
            'admin',
            'sekretaris',
            'panitera',
            'kabag',
            'kasubag',
            'panmud',
            'pegawai',
            'peserta',
        ]);
    }

    public function canAccessLibraryModule()
    {
        if ($this->isSuperAdmin() || $this->hasRole('operator_perpustakaan')) {
            return true;
        }

        return $this->hasAnyRole([
            'sekretaris', 'panitera', 'kabag', 'kasubag', 'panmud',
            'pegawai', 'peserta', 'operator', 'notulis', 'protokoler',
        ]);
    }

    public function canManageLibraryModule()
    {
        return $this->isSuperAdmin() || $this->hasRole('operator_perpustakaan');
    }

    public function canManageSupplyModule()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasRole('operator_persediaan');
    }

    public function canAccessLeaveModule()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasAnyRole([
            'pegawai',
            'satker',
            'atasan_langsung',
            'admin_kepegawaian',
            'verifikator_dokumen',
            'ppk',
        ]);
    }

    public function canApproveLeave()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasAnyRole([
            'atasan_langsung',
            'verifikator_dokumen',
            'ppk',
            'admin_kepegawaian',
        ]);
    }

    public function hasLeaveApprovalAssignment($statuses = null)
    {
        if (!$this->exists || !\Illuminate\Support\Facades\Schema::hasTable('leave_approvals')) {
            return false;
        }

        $query = LeaveApproval::where('approver_id', $this->id);

        if ($statuses) {
            $query->whereIn('status', (array) $statuses);
        }

        return $query->exists();
    }

    public function canAccessLeaveApproval()
    {
        if ($this->isSatker()) {
            return false;
        }

        return $this->canApproveLeave() || $this->hasLeaveApprovalAssignment();
    }

    public function canAccessLeaveBalanceReport()
    {
        return $this->isSuperAdmin()
            || $this->canManageLeaveMasterData()
            || $this->canApproveLeave();
    }

    public function canAccessLeaveReports()
    {
        return $this->canAccessLeaveModule() && !$this->isSatker();
    }

    public function canApproveSuratKeluarTemplate()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasRole('approval');
    }

    public function canAccessApprovalCenter()
    {
        return $this->canAccessMeetingApproval()
            || $this->canAccessLeaveApproval()
            || $this->canApproveSuratKeluarTemplate()
            || $this->hasPendingSuratTugasParaf();
    }

    public function hasPendingSuratTugasParaf()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('surat_keluar_approvals')
            || !\Illuminate\Support\Facades\Schema::hasColumn('surat_keluar_approvals', 'paraf_user_id')) {
            return false;
        }

        return SuratKeluarApproval::where('status', 'pending')
            ->where('paraf_status', 'pending')
            ->whereIn('paraf_user_id', $this->effectiveAssignmentUserIds())
            ->exists();
    }

    public function canManageLeaveMasterData()
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->hasAnyRole([
            'admin_kepegawaian',
            'ppk',
        ]);
    }

    public function hasJabatanKode($codes)
    {
        $codes = is_array($codes) ? $codes : [$codes];
        return $this->effectiveJabatans()->contains(function ($jabatan) use ($codes) {
            return $jabatan && in_array($jabatan->kode, $codes, true);
        });
    }

    public function effectiveJabatans()
    {
        if ($this->effectiveJabatansCache !== null) {
            return collect($this->effectiveJabatansCache);
        }

        $jabatans = collect();

        if ($this->jabatan) {
            $jabatans->push($this->jabatan);
        }

        $delegations = $this->relationLoaded('activeJabatanDelegations')
            ? $this->activeJabatanDelegations
            : $this->activeJabatanDelegations()->get();

        foreach ($delegations as $delegation) {
            if ($delegation->jabatan) {
                $jabatans->push($delegation->jabatan);
            }
        }

        $this->effectiveJabatansCache = $jabatans->unique('id')->values()->all();

        return collect($this->effectiveJabatansCache);
    }

    public function effectiveJabatanIds()
    {
        return $this->effectiveJabatans()->pluck('id')->filter()->values()->all();
    }

    public function effectiveAssignmentUserIds()
    {
        if ($this->effectiveAssignmentUserIdsCache !== null) {
            return $this->effectiveAssignmentUserIdsCache;
        }

        $ids = collect([$this->id])->filter();
        $jabatanIds = $this->effectiveJabatanIds();
        $delegatedStructuralRoles = $this->delegatedStructuralRoleNames();

        if (!empty($jabatanIds)) {
            $ids = $ids->merge(
                static::active()
                    ->whereIn('jabatan_id', $jabatanIds)
                    ->pluck('id')
            );
        }

        if (!empty($delegatedStructuralRoles)) {
            $ids = $ids->merge(
                static::active()
                    ->whereHas('roles', function ($query) use ($delegatedStructuralRoles) {
                        $query->whereIn('name', $delegatedStructuralRoles);
                    })
                    ->pluck('id')
            );
        }

        $this->effectiveAssignmentUserIdsCache = $ids
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values()
            ->all();

        return $this->effectiveAssignmentUserIdsCache;
    }

    public function activeDelegationForJabatan($jabatanId)
    {
        if (!$jabatanId) {
            return null;
        }

        $delegations = $this->relationLoaded('activeJabatanDelegations')
            ? $this->activeJabatanDelegations
            : $this->activeJabatanDelegations()->get();

        return $delegations->first(function ($delegation) use ($jabatanId) {
            return (int) $delegation->jabatan_id === (int) $jabatanId;
        });
    }

    public function hasActiveJabatanDelegation()
    {
        $delegations = $this->relationLoaded('activeJabatanDelegations')
            ? $this->activeJabatanDelegations
            : $this->activeJabatanDelegations()->get();

        return $delegations->isNotEmpty();
    }

    /**
     * User IDs whose incoming letters may be handled by this user.
     * Unlike general approval delegation, incoming-letter authority is tied
     * strictly to the delegated position and never expanded by a shared role.
     */
    public function suratMasukAssignmentUserIds()
    {
        if ($this->suratMasukAssignmentUserIdsCache !== null) {
            return $this->suratMasukAssignmentUserIdsCache;
        }

        $ids = collect([$this->id])->filter();
        $delegations = $this->relationLoaded('activeJabatanDelegations')
            ? $this->activeJabatanDelegations
            : $this->activeJabatanDelegations()->get();
        $delegatedJabatanIds = $delegations->pluck('jabatan_id')->filter()->unique()->values()->all();

        if (!empty($delegatedJabatanIds)) {
            $ids = $ids->merge(
                static::active()
                    ->whereIn('jabatan_id', $delegatedJabatanIds)
                    ->pluck('id')
            );
        }

        $this->suratMasukAssignmentUserIdsCache = $ids
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values()
            ->all();

        return $this->suratMasukAssignmentUserIdsCache;
    }

    protected function delegatedStructuralRoleNames()
    {
        if ($this->delegatedStructuralRoleNamesCache !== null) {
            return $this->delegatedStructuralRoleNamesCache;
        }

        $delegations = $this->relationLoaded('activeJabatanDelegations')
            ? $this->activeJabatanDelegations
            : $this->activeJabatanDelegations()->get();

        $this->delegatedStructuralRoleNamesCache = $delegations
            ->map(function ($delegation) {
                return optional($delegation->jabatan)->kode;
            })
            ->filter()
            ->flatMap(function ($kode) {
                if ($kode === 'KPTA') {
                    return ['ketua'];
                }
                if ($kode === 'WKPTA') {
                    return ['wakil_ketua'];
                }
                if ($kode === 'SEK') {
                    return ['sekretaris'];
                }
                if ($kode === 'PAN') {
                    return ['panitera'];
                }
                if (strpos($kode, 'KABAG_') === 0) {
                    return ['kabag'];
                }
                if (strpos($kode, 'KASUBAG_') === 0) {
                    return ['kasubag'];
                }
                if (strpos($kode, 'PANMUD_') === 0) {
                    return ['panmud'];
                }

                return [];
            })
            ->unique()
            ->values()
            ->all();

        return $this->delegatedStructuralRoleNamesCache;
    }

    public function canActAsAssignedUser($userId)
    {
        return in_array((int) $userId, $this->effectiveAssignmentUserIds(), true);
    }

    public function targetDisposisiJabatanIds()
    {
        return $this->effectiveJabatans()
            ->flatMap(function ($jabatan) {
                return $jabatan ? $jabatan->getTargetDisposisi() : [];
            })
            ->unique()
            ->values()
            ->all();
    }

    public function sourceJabatanIdForTarget($targetJabatanId)
    {
        foreach ($this->effectiveJabatans() as $jabatan) {
            if ($jabatan && in_array((int) $targetJabatanId, array_map('intval', $jabatan->getTargetDisposisi()), true)) {
                return $jabatan->id;
            }
        }

        return $this->jabatan_id;
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
        return $this->effectiveJabatans()->contains(function ($jabatan) {
            return $jabatan && (bool) preg_match('/^(KABAG|KASUBAG)_/', $jabatan->kode);
        });
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

    public function canCreateSuratKeluar()
    {
        if ($this->canManageSuratKeluar()) {
            return true;
        }

        return $this->effectiveJabatans()->contains(function ($jabatan) {
            return $jabatan && (bool) preg_match('/^(KASUBAG|PANMUD)_/', (string) $jabatan->kode);
        });
    }

    public function canModifySuratKeluar($suratKeluar)
    {
        return $this->canManageSuratKeluar()
            || ($suratKeluar && (int) $suratKeluar->created_by === (int) $this->id);
    }

    public function canNaikanSuratMasuk()
    {
        return $this->hasJabatanKode(['PAN', 'SEK', 'KASUBAG_TURT']);
    }

    public function hasPendingDisposisiForSurat($suratMasuk)
    {
        return (bool) $this->activePendingDisposisiForSurat($suratMasuk);
    }

    public function activePendingDisposisiForSurat($suratMasuk)
    {
        if (!$suratMasuk || $suratMasuk->status === 'selesai') {
            return null;
        }

        if ($suratMasuk->relationLoaded('disposisis')) {
            $assignmentUserIds = $this->suratMasukAssignmentUserIds();
            $jabatanIds = array_map('intval', $this->effectiveJabatanIds());

            $pending = $suratMasuk->disposisis
                ->filter(function ($disposisi) use ($assignmentUserIds, $jabatanIds) {
                    return $disposisi->status === 'pending'
                        && (
                            in_array((int) $disposisi->kepada_user_id, $assignmentUserIds, true)
                            || ($disposisi->kepada_jabatan_id && in_array((int) $disposisi->kepada_jabatan_id, $jabatanIds, true))
                        );
                })
                ->sortByDesc('created_at')
                ->first();

            if (!$pending) {
                return null;
            }

            $hasForwardedAfterPending = $suratMasuk->disposisis
                ->contains(function ($disposisi) use ($pending) {
                    return (int) $disposisi->dari_user_id === (int) $this->id
                        && $disposisi->created_at
                        && $pending->created_at
                        && $disposisi->created_at->gt($pending->created_at);
                });

            return $hasForwardedAfterPending ? null : $pending;
        }

        $pending = $suratMasuk->disposisis()
            ->addressedToUser($this)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$pending) {
            return null;
        }

        $hasForwardedAfterPending = $suratMasuk->disposisis()
            ->where('dari_user_id', $this->id)
            ->where('created_at', '>', $pending->created_at)
            ->exists();

        return $hasForwardedAfterPending ? null : $pending;
    }

    public function canViewSuratMasuk($suratMasuk)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->canManageInitialSuratMasuk()) {
            return true;
        }

        if ((int) $suratMasuk->created_by === (int) $this->id) {
            return true;
        }

        if ($suratMasuk->isAgendaRelatedTo($this)) {
            return true;
        }

        return $suratMasuk->disposisis()
            ->involvingUser($this)
            ->exists();
    }

    public function canForwardSuratMasuk($suratMasuk)
    {
        if ($suratMasuk->status === 'selesai') {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        if (empty($this->targetDisposisiJabatanIds())) {
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
        if ($this->isSuperAdmin() || $this->isKasubagTurt()) {
            return true;
        }

        if ((int) $suratKeluar->created_by === (int) $this->id) {
            return true;
        }

        return $suratKeluar->penerimaInternal()
            ->whereIn('users.id', $this->effectiveAssignmentUserIds())
            ->exists();
    }

    public function canFollowUpDisposisi($disposisi)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (in_array((int) $disposisi->kepada_user_id, $this->suratMasukAssignmentUserIds(), true)) {
            return true;
        }

        return $disposisi->kepada_jabatan_id
            && in_array((int) $disposisi->kepada_jabatan_id, array_map('intval', $this->effectiveJabatanIds()), true);
    }

    public function canOpenTindakLanjutSuratMasuk($suratMasuk)
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

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

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'user_id');
    }

    public function leaveApprovals()
    {
        return $this->hasMany(LeaveApproval::class, 'approver_id');
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class, 'user_id');
    }

    public function supplyRequests()
    {
        return $this->hasMany(SupplyRequest::class, 'user_id');
    }

    public function supplyPickups()
    {
        return $this->hasMany(SupplyPickup::class, 'user_id');
    }
}
