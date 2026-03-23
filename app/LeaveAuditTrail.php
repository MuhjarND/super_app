<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeaveAuditTrail extends Model
{
    public $timestamps = false;
    protected $fillable = ['leave_request_id','actor_id','event','old_values_json','new_values_json','note','ip_address','user_agent','created_at'];
    protected $casts = ['old_values_json' => 'array','new_values_json' => 'array','created_at' => 'datetime'];
    public function leaveRequest() { return $this->belongsTo(LeaveRequest::class); }
    public function actor() { return $this->belongsTo(User::class, 'actor_id'); }
}
