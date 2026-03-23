<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeaveDelegation extends Model
{
    protected $fillable = ['delegator_id','delegate_id','scope','start_date','end_date','is_active','note'];
    protected $casts = ['start_date' => 'date','end_date' => 'date','is_active' => 'boolean'];
    public function delegator() { return $this->belongsTo(User::class, 'delegator_id'); }
    public function delegate() { return $this->belongsTo(User::class, 'delegate_id'); }

    public function getScopeLabelAttribute()
    {
        $map = [
            'leave_approval' => 'Approval Cuti',
            'document_verification' => 'Verifikasi Dokumen',
            'ppk_approval' => 'Persetujuan PPK',
        ];

        return $map[$this->scope] ?? ucfirst(str_replace('_', ' ', (string) $this->scope));
    }
}
