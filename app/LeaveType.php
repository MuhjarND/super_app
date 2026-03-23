<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = ['code','name','description','requires_balance','requires_document','requires_verification','requires_ppk_approval','max_days','max_months','service_years_required','status'];

    protected $casts = [
        'requires_balance' => 'boolean',
        'requires_document' => 'boolean',
        'requires_verification' => 'boolean',
        'requires_ppk_approval' => 'boolean',
        'max_days' => 'integer',
        'max_months' => 'integer',
        'service_years_required' => 'integer',
    ];

    public const CODE_TAHUNAN = 'CT';
    public const CODE_BESAR = 'CB';
    public const CODE_SAKIT = 'CS';
    public const CODE_MELAHIRKAN = 'CM';
    public const CODE_ALASAN_PENTING = 'CAP';
    public const CODE_BERSAMA = 'CBS';
    public const CODE_LTN = 'CLTN';

    public function policies() { return $this->hasMany(LeavePolicy::class); }
    public function requests() { return $this->hasMany(LeaveRequest::class); }
    public function holidays() { return $this->hasMany(LeaveHoliday::class); }

    public function getStatusLabelAttribute()
    {
        return $this->status === 'active' ? 'Aktif' : 'Nonaktif';
    }
}
