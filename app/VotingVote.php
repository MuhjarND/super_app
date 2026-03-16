<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VotingVote extends Model
{
    protected $fillable = [
        'voting_id',
        'voting_item_id',
        'voting_candidate_id',
        'user_id',
        'voted_at',
    ];

    protected $casts = [
        'voted_at' => 'datetime',
    ];

    public function voting()
    {
        return $this->belongsTo(Voting::class);
    }

    public function item()
    {
        return $this->belongsTo(VotingItem::class, 'voting_item_id');
    }

    public function candidate()
    {
        return $this->belongsTo(VotingCandidate::class, 'voting_candidate_id');
    }

    public function voter()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
