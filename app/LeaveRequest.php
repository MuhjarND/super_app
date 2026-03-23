<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = ['request_number','letter_number','user_id','leave_type_id','delegate_approval_id','status_asn_snapshot','unit_snapshot','jabatan_snapshot','approver_chain_snapshot','start_date','end_date','requested_days','approved_days','workday_count','purpose','leave_address','contact_phone','needs_document_verification','needs_ppk_approval','is_deferred','deferred_reason','revision_number','revision_note','status','submitted_at','verified_at','approved_at','rejected_at','cancelled_at','completed_at','locked_at','created_by','updated_by'];
    protected $casts = ['approver_chain_snapshot' => 'array','start_date' => 'date','end_date' => 'date','requested_days' => 'integer','approved_days' => 'integer','workday_count' => 'integer','needs_document_verification' => 'boolean','needs_ppk_approval' => 'boolean','is_deferred' => 'boolean','revision_number' => 'integer','submitted_at' => 'datetime','verified_at' => 'datetime','approved_at' => 'datetime','rejected_at' => 'datetime','cancelled_at' => 'datetime','completed_at' => 'datetime','locked_at' => 'datetime'];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    public function user() { return $this->belongsTo(User::class); }
    public function leaveType() { return $this->belongsTo(LeaveType::class); }
    public function documents() { return $this->hasMany(LeaveRequestDocument::class); }
    public function approvals() { return $this->hasMany(LeaveApproval::class)->orderBy('step_no'); }
    public function audits() { return $this->hasMany(LeaveAuditTrail::class)->orderByDesc('id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function updater() { return $this->belongsTo(User::class, 'updated_by'); }
    public function delegateApproval() { return $this->belongsTo(User::class, 'delegate_approval_id'); }
    public function suratKeluar() { return $this->hasOne(SuratKeluar::class, 'nomor_surat', 'letter_number'); }
    public function isLocked() { return !is_null($this->locked_at); }
    public function getStatusLabelAttribute() { $map = [self::STATUS_DRAFT=>'Draft',self::STATUS_SUBMITTED=>'Diajukan',self::STATUS_UNDER_REVIEW=>'Ditinjau',self::STATUS_VERIFIED=>'Terverifikasi',self::STATUS_APPROVED=>'Disetujui',self::STATUS_REJECTED=>'Ditolak',self::STATUS_CANCELLED=>'Dibatalkan',self::STATUS_COMPLETED=>'Selesai']; return $map[$this->status] ?? ucfirst((string) $this->status); }
    public function getStatusBadgeAttribute() { $map = [self::STATUS_DRAFT=>['secondary','Draft'],self::STATUS_SUBMITTED=>['info','Diajukan'],self::STATUS_UNDER_REVIEW=>['warning','Ditinjau'],self::STATUS_VERIFIED=>['primary','Terverifikasi'],self::STATUS_APPROVED=>['success','Disetujui'],self::STATUS_REJECTED=>['danger','Ditolak'],self::STATUS_CANCELLED=>['dark','Dibatalkan'],self::STATUS_COMPLETED=>['success','Selesai']]; $status = $map[$this->status] ?? ['secondary', ucfirst((string) $this->status)]; return '<span class="badge badge-' . $status[0] . ' app-status-badge">' . $status[1] . '</span>'; }
    public function getDisplayNumberAttribute() { return $this->letter_number ?: ($this->request_number ?: 'Draft belum bernomor'); }

    public function getPeriodLabelAttribute()
    {
        return optional($this->start_date)->translatedFormat('d F Y') . ' s.d. ' . optional($this->end_date)->translatedFormat('d F Y');
    }
}
