<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WhatsAppNotificationLog extends Model
{
    protected $table = 'whatsapp_notification_logs';

    protected $fillable = [
        'module',
        'event',
        'notifiable_type',
        'notifiable_id',
        'target_user_id',
        'target_name',
        'phone_number',
        'message',
        'status',
        'response_body',
        'created_by',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
