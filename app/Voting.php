<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Voting extends Model
{
    protected $fillable = [
        'judul',
        'deskripsi',
        'status',
        'select_all_participants',
        'public_code',
        'token_qr',
        'created_by',
        'updated_by',
        'participant_notified_at',
    ];

    protected $casts = [
        'select_all_participants' => 'boolean',
        'participant_notified_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items()
    {
        return $this->hasMany(VotingItem::class)->orderBy('urutan');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'voting_participants')
            ->withPivot('urutan', 'voted_at')
            ->orderBy('pivot_urutan');
    }

    public function participantPivots()
    {
        return $this->hasMany(VotingParticipant::class)->orderBy('urutan');
    }

    public function votes()
    {
        return $this->hasMany(VotingVote::class);
    }

    public function getStatusBadgeAttribute()
    {
        $map = [
            'draft' => ['secondary', 'Draft'],
            'aktif' => ['success', 'Aktif'],
            'selesai' => ['dark', 'Selesai'],
        ];
        $status = $map[$this->status] ?? ['secondary', ucfirst((string) $this->status)];

        return '<span class="badge badge-' . $status[0] . '">' . $status[1] . '</span>';
    }
}
