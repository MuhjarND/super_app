<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeaveHoliday extends Model
{
    protected $fillable = ['holiday_date','name','category','impacts_balance','leave_type_id','deduction_days','is_national_holiday','is_collective_leave','is_active'];
    protected $casts = ['holiday_date' => 'date','impacts_balance' => 'boolean','deduction_days' => 'integer','is_national_holiday' => 'boolean','is_collective_leave' => 'boolean','is_active' => 'boolean'];
    public function leaveType() { return $this->belongsTo(LeaveType::class); }

    public function getCategoryLabelAttribute()
    {
        $map = [
            'libur_nasional' => 'Libur Nasional',
            'cuti_bersama' => 'Cuti Bersama',
            'internal' => 'Internal',
        ];

        return $map[$this->category] ?? ucfirst(str_replace('_', ' ', (string) $this->category));
    }
}
