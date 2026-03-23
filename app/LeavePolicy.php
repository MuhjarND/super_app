<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LeavePolicy extends Model
{
    protected $fillable = ['leave_type_id','key','value_json','is_active','effective_start','effective_end'];
    protected $casts = ['value_json' => 'array','is_active' => 'boolean','effective_start' => 'date','effective_end' => 'date'];
    public function leaveType() { return $this->belongsTo(LeaveType::class); }

    public function getValueTextAttribute()
    {
        $value = $this->value_json;

        if (is_array($value) && array_key_exists('value', $value)) {
            $value = $value['value'];
        }

        if (is_bool($value)) {
            return $value ? 'Ya' : 'Tidak';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }
}
