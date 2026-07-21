<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Rapat extends Model
{
    protected $fillable = [
        'nomor_undangan',
        'judul',
        'deskripsi',
        'kategori_rapat_id',
        'kategori_surat_kode_id',
        'nomenklatur_jabatan',
        'tanggal',
        'waktu_mulai',
        'tempat',
        'approver_1_id',
        'approver_2_id',
        'approval1_jabatan_manual',
        'detail_tambahan',
        'tujuan_surat',
        'bersama_satker',
        'jenis_pakaian',
        'is_virtual',
        'meeting_id',
        'meeting_passcode',
        'lampiran_tambahan_path',
        'lampiran_tambahan_nama',
        'lampiran_tambahan_mime',
        'lampiran_tambahan_size',
        'status',
        'token_qr',
        'public_code',
        'participant_notified_at',
        'last_attendance_reminder_at',
        'is_recurring',
        'recurring_pattern',
        'recurring_until',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'recurring_until' => 'date',
        'is_virtual' => 'boolean',
        'bersama_satker' => 'boolean',
        'is_recurring' => 'boolean',
        'lampiran_tambahan_size' => 'integer',
        'participant_notified_at' => 'datetime',
        'last_attendance_reminder_at' => 'datetime',
    ];

    public function scopeVisibleTo($query, $user)
    {
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->canManageRapat()) {
            return $query;
        }

        if ($user->hasRole('satker')) {
            return $query->where('bersama_satker', true)
                ->whereIn('status', ['disetujui', 'selesai'])
                ->whereHas('pesertas', function ($pesertaQuery) use ($user) {
                    $pesertaQuery->where('users.id', $user->id);
                });
        }

        return $query->where(function ($builder) use ($user) {
            $builder->where('created_by', $user->id)
                ->orWhere('approver_1_id', $user->id)
                ->orWhere('approver_2_id', $user->id)
                ->orWhereHas('pesertas', function ($pesertaQuery) use ($user) {
                    $pesertaQuery->where('users.id', $user->id);
                });
        });
    }

    public function kategoriRapat()
    {
        return $this->belongsTo(KategoriRapat::class, 'kategori_rapat_id');
    }

    public function kategoriSuratKode()
    {
        return $this->belongsTo(KlasifikasiKode::class, 'kategori_surat_kode_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver1()
    {
        return $this->belongsTo(User::class, 'approver_1_id');
    }

    public function approver2()
    {
        return $this->belongsTo(User::class, 'approver_2_id');
    }

    public function pesertas()
    {
        return $this->belongsToMany(User::class, 'rapat_peserta')
            ->withPivot('urutan')
            ->orderBy('pivot_urutan');
    }

    public function approvals()
    {
        return $this->hasMany(RapatApproval::class)->orderBy('step_order');
    }

    public function approvalHistories()
    {
        return $this->hasMany(RapatApprovalHistory::class)->orderByDesc('acted_at');
    }

    public function currentApproval()
    {
        return $this->hasOne(RapatApproval::class)->where('status', 'pending')->orderBy('step_order');
    }

    public function attendances()
    {
        return $this->hasMany(RapatAttendance::class)->orderByDesc('attended_at');
    }

    public function internalAttendances()
    {
        return $this->hasMany(RapatAttendance::class)->where('attendance_type', 'internal')->orderByDesc('attended_at');
    }

    public function guestAttendances()
    {
        return $this->hasMany(RapatAttendance::class)->where('attendance_type', 'guest')->orderByDesc('attended_at');
    }

    public function notulensi()
    {
        return $this->hasOne(RapatNotulensi::class);
    }

    public function laporans()
    {
        return $this->hasMany(RapatLaporan::class);
    }

    public function suratKeluar()
    {
        return $this->hasOne(SuratKeluar::class, 'rapat_id');
    }

    public function getWaktuMulaiFormattedAttribute()
    {
        if (!$this->waktu_mulai) {
            return '-';
        }

        return Carbon::createFromFormat('H:i:s', $this->waktu_mulai, 'Asia/Jayapura')
            ->format('H:i');
    }

    public function getTanggalWitFormattedAttribute()
    {
        return $this->tanggal
            ? $this->tanggal->copy()->timezone('Asia/Jayapura')->translatedFormat('d M Y')
            : '-';
    }

    public function getStatusBadgeAttribute()
    {
        $statusKey = $this->display_status_key;
        $map = [
            'draft' => ['secondary', 'Draft'],
            'terjadwal' => ['info', 'Terjadwal'],
            'pending_approval' => ['warning', 'Pending Approval'],
            'disetujui' => ['success', 'Disetujui'],
            'ditolak' => ['danger', 'Ditolak'],
            'dibatalkan' => ['danger', 'Dibatalkan'],
            'selesai' => ['primary', 'Selesai'],
        ];

        $status = $map[$statusKey] ?? ['secondary', ucfirst((string) $statusKey)];

        return '<span class="badge badge-' . $status[0] . '">' . $status[1] . '</span>';
    }

    public function getDisplayStatusKeyAttribute()
    {
        $approvals = $this->relationLoaded('approvals') ? $this->approvals : $this->approvals()->get();

        if ($approvals->count() > 0) {
            if ($approvals->contains('status', 'rejected')) {
                return 'ditolak';
            }

            if ($approvals->every(function ($approval) {
                return $approval->status === 'approved';
            })) {
                return 'disetujui';
            }

            return 'pending_approval';
        }

        if (($this->approver_1_id || $this->approver_2_id) && in_array($this->status, ['disetujui', 'selesai'], true)) {
            return 'pending_approval';
        }

        return $this->status;
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'draft' => 'Draft',
            'terjadwal' => 'Terjadwal',
            'pending_approval' => 'Pending Approval',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
            'dibatalkan' => 'Dibatalkan',
            'selesai' => 'Selesai',
        ];

        return $labels[$this->display_status_key] ?? ucfirst((string) $this->display_status_key);
    }

    public function getKategoriSuratLabelAttribute()
    {
        if ($this->kategoriSuratKode) {
            return $this->kategoriSuratKode->nama;
        }

        return optional($this->kategoriRapat)->nama ?: '-';
    }

    public function getKategoriSuratKodeLabelAttribute()
    {
        if ($this->kategoriSuratKode) {
            return $this->kategoriSuratKode->kode;
        }

        return optional($this->kategoriRapat)->kode ?: '-';
    }
}
