<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $fillable = ['user_id','leave_type_id','year','opening_balance','entitlement','carry_forward','adjustment_plus','adjustment_minus','used_days','reserved_days','remaining_balance','meta_json'];
    protected $casts = ['year' => 'integer','opening_balance' => 'integer','entitlement' => 'integer','carry_forward' => 'integer','adjustment_plus' => 'integer','adjustment_minus' => 'integer','used_days' => 'integer','reserved_days' => 'integer','remaining_balance' => 'integer','meta_json' => 'array'];
    public function user() { return $this->belongsTo(User::class); }
    public function leaveType() { return $this->belongsTo(LeaveType::class); }
}
