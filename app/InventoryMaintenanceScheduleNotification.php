<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventoryMaintenanceScheduleNotification extends Model
{
    protected $fillable = [
        'inventory_maintenance_schedule_id',
        'user_id',
        'status',
        'attempts',
        'last_attempt_at',
        'sent_at',
        'last_error',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'last_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function schedule()
    {
        return $this->belongsTo(InventoryMaintenanceSchedule::class, 'inventory_maintenance_schedule_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
