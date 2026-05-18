<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeaveApproval extends Model
{
    protected $fillable = ['leave_request_id','step_no','role_name','approver_id','delegated_to_id','status','action','acted_at','signature_path','signature_mime','signature_size','note','meta_json'];
    protected $casts = ['step_no' => 'integer','acted_at' => 'datetime','meta_json' => 'array'];
    public function leaveRequest() { return $this->belongsTo(LeaveRequest::class); }
    public function approver() { return $this->belongsTo(User::class, 'approver_id'); }
    public function delegatedTo() { return $this->belongsTo(User::class, 'delegated_to_id'); }

    public function getRoleLabelAttribute()
    {
        $map = [
            'atasan_langsung' => 'Atasan Langsung',
            'atasan_lanjutan' => 'Atasan dari Atasan Langsung',
            'verifikator_dokumen' => 'Verifikator Dokumen',
            'ppk' => 'PPK / Pejabat Berwenang',
        ];

        return $map[$this->role_name] ?? ucfirst(str_replace('_', ' ', (string) $this->role_name));
    }

    public function getStatusLabelAttribute()
    {
        $map = [
            'waiting' => 'Menunggu',
            'pending' => 'Pending',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
        ];

        return $map[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getStatusBadgeAttribute()
    {
        $map = [
            'waiting' => ['secondary', 'Menunggu'],
            'pending' => ['warning', 'Pending'],
            'approved' => ['success', 'Disetujui'],
            'rejected' => ['danger', 'Ditolak'],
        ];

        $status = $map[$this->status] ?? ['secondary', ucfirst((string) $this->status)];

        return '<span class="badge badge-' . $status[0] . ' app-status-badge">' . $status[1] . '</span>';
    }
}
