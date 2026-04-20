<?php

namespace App\Services;

use App\ActivityAudit;
use App\User;

class ActivityAuditService
{
    public function log($module, $event, $auditable = null, array $payload = [], User $actor = null)
    {
        $actor = $actor ?: auth()->user();

        return ActivityAudit::create([
            'module' => $module,
            'event' => $event,
            'auditable_type' => $auditable ? get_class($auditable) : data_get($payload, 'auditable_type'),
            'auditable_id' => $auditable ? data_get($auditable, 'id') : data_get($payload, 'auditable_id'),
            'subject_type' => data_get($payload, 'subject_type'),
            'subject_id' => data_get($payload, 'subject_id'),
            'subject_title' => data_get($payload, 'subject_title'),
            'actor_id' => $actor ? $actor->id : data_get($payload, 'actor_id'),
            'actor_name' => $actor ? $actor->name : data_get($payload, 'actor_name'),
            'target_user_id' => data_get($payload, 'target_user_id'),
            'target_name' => data_get($payload, 'target_name'),
            'old_values_json' => data_get($payload, 'old_values_json'),
            'new_values_json' => data_get($payload, 'new_values_json'),
            'metadata_json' => data_get($payload, 'metadata_json'),
            'note' => data_get($payload, 'note'),
            'ip_address' => request() ? request()->ip() : null,
            'user_agent' => request() ? request()->userAgent() : null,
            'created_at' => now('Asia/Jayapura'),
        ]);
    }
}
