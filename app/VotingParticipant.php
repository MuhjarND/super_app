<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VotingParticipant extends Model
{
    protected $fillable = [
        'voting_id',
        'user_id',
        'urutan',
        'voted_at',
    ];

    protected $casts = [
        'voted_at' => 'datetime',
    ];

    public function voting()
    {
        return $this->belongsTo(Voting::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
