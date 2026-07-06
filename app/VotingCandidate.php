<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VotingCandidate extends Model
{
    protected $fillable = [
        'voting_item_id',
        'user_id',
        'nama_snapshot',
        'jabatan_snapshot',
        'image_path',
        'image_name',
        'image_mime',
        'image_size',
        'urutan',
    ];

    public function getImageUrlAttribute()
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    public function item()
    {
        return $this->belongsTo(VotingItem::class, 'voting_item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function votes()
    {
        return $this->hasMany(VotingVote::class);
    }
}
