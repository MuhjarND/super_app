<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ActivityAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'module',
        'event',
        'auditable_type',
        'auditable_id',
        'subject_type',
        'subject_id',
        'subject_title',
        'actor_id',
        'actor_name',
        'target_user_id',
        'target_name',
        'old_values_json',
        'new_values_json',
        'metadata_json',
        'note',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_values_json' => 'array',
        'new_values_json' => 'array',
        'metadata_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
