<?php

namespace App\Services;

use App\Voting;
use Illuminate\Support\Facades\DB;

class VotingStatsService
{
    public function build(Voting $voting)
    {
        return DB::transaction(function () use ($voting) {
            return $this->snapshot($voting);
        });
    }

    protected function snapshot(Voting $voting)
    {
        // Aggregate on the database; do not load each ballot into memory or
        // run a count query for every candidate on each live refresh.
        $voting->load(['items' => function ($query) {
            $query->withCount('votes')->with(['candidates' => function ($candidateQuery) {
                $candidateQuery->withCount('votes');
            }]);
        }, 'participantPivots.user']);

        $participants = $voting->participantPivots;
        $pending = $participants->whereNull('voted_at')->map(function ($participant) {
            return ['id' => $participant->user_id, 'name' => optional($participant->user)->name ?: 'Peserta tidak tersedia'];
        })->values();

        return [
            'title' => $voting->judul,
            'status' => $voting->status,
            'updated_at' => now('Asia/Jayapura')->toIso8601String(),
            'total_participants' => $participants->count(),
            'total_voted' => $participants->whereNotNull('voted_at')->count(),
            'pending_count' => $pending->count(),
            'pending' => $pending,
            'items' => $voting->items->map(function ($item) {
                return [
                    'id' => $item->id, 'judul' => $item->judul,
                    'uses_candidates' => (bool) $item->uses_candidates,
                    'total_votes' => $item->votes_count,
                    'candidates' => $item->candidates->map(function ($choice) use ($item) {
                        return [
                            'id' => $choice->id, 'nama' => $choice->nama_snapshot,
                            'image_url' => $choice->image_url, 'count' => $choice->votes_count,
                            'percentage' => $item->votes_count ? round($choice->votes_count / $item->votes_count * 100, 2) : 0,
                        ];
                    })->sortByDesc('count')->values(),
                ];
            })->values(),
            'all_voted' => $pending->isEmpty() && $participants->isNotEmpty(),
        ];
    }
}
