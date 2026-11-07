<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VotingItem extends Model
{
    protected $fillable = [
        'voting_id',
        'judul',
        'deskripsi',
        'urutan',
        'uses_candidates',
    ];

    protected $casts = ['uses_candidates' => 'boolean'];

    public function voting()
    {
        return $this->belongsTo(Voting::class);
    }

    public function candidates()
    {
        return $this->hasMany(VotingCandidate::class)->orderBy('urutan');
    }

    public function votes()
    {
        return $this->hasMany(VotingVote::class);
    }
}
