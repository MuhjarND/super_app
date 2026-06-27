<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMagicLoginToken extends Model
{
    protected $table = 'whatsapp_magic_login_tokens';

    protected $fillable = [
        'user_id',
        'token_hash',
        'destination_url',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
