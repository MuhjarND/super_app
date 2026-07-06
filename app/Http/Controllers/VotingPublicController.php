<?php

namespace App\Http\Controllers;

use App\Voting;
use App\VotingVote;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VotingPublicController extends Controller
{
    public function show($publicCode)
    {
        $voting = $this->findVoting($publicCode);
        abort_unless($voting->status === 'aktif', 404);

        $voting->load([
            'items.candidates',
            'participantPivots.user' => function ($query) {
                $query->active();
            },
        ]);
        $voting->setRelation('participantPivots', $voting->participantPivots->filter(function ($participant) {
            return $participant->user;
        })->values());

        return view('rapat.voting.public', compact('voting'));
    }

    public function store(Request $request, $publicCode)
    {
        $voting = $this->findVoting($publicCode);
        abort_unless($voting->status === 'aktif', 422, 'Voting belum aktif atau sudah selesai.');

        $rules = [
            'participant_id' => ['required', Rule::exists('users', 'id')->where('status_aktif_pegawai', true)],
            'choices' => ['required', 'array'],
        ];

        foreach ($voting->items as $item) {
            $rules['choices.' . $item->id] = ['required', 'exists:voting_candidates,id'];
        }

        $data = $request->validate($rules, [
            'participant_id.required' => 'Peserta voting wajib dipilih.',
        ]);

        $participantPivot = $voting->participantPivots()->where('user_id', $data['participant_id'])->first();
        if (!$participantPivot) {
            return response()->json(['message' => 'Peserta tidak terdaftar pada voting ini.'], 422);
        }

        if ($participantPivot->voted_at) {
            return response()->json(['message' => 'Peserta tersebut sudah melakukan voting.'], 422);
        }

        DB::transaction(function () use ($voting, $data, $participantPivot) {
            foreach ($voting->items as $item) {
                $candidateId = (int) $data['choices'][$item->id];
                $candidate = $item->candidates()->where('id', $candidateId)->first();

                if (!$candidate) {
                    abort(422, 'Pilihan kandidat tidak valid.');
                }

                VotingVote::create([
                    'voting_id' => $voting->id,
                    'voting_item_id' => $item->id,
                    'voting_candidate_id' => $candidate->id,
                    'user_id' => $participantPivot->user_id,
                    'voted_at' => Carbon::now('Asia/Jayapura'),
                ]);
            }

            $participantPivot->update([
                'voted_at' => Carbon::now('Asia/Jayapura'),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Voting berhasil direkam.',
            'redirect_url' => route('rapat.voting.public.results', $voting->public_code),
        ]);
    }

    public function results($publicCode)
    {
        $voting = $this->findVoting($publicCode);
        $voting->load(['items.candidates.votes', 'participantPivots.user']);

        return view('rapat.voting.public-results', compact('voting'));
    }

    public function stats($publicCode)
    {
        $voting = $this->findVoting($publicCode);
        $voting->load(['items.candidates.votes', 'participantPivots.user']);

        $items = $voting->items->map(function ($item) {
            $totalVotes = $item->votes()->count();

            return [
                'id' => $item->id,
                'judul' => $item->judul,
                'total_votes' => $totalVotes,
                'candidates' => $item->candidates->map(function ($candidate) use ($totalVotes) {
                    $count = $candidate->votes()->count();
                    return [
                        'id' => $candidate->id,
                        'nama' => $candidate->nama_snapshot,
                        'image_url' => $candidate->image_url,
                        'count' => $count,
                        'percentage' => $totalVotes > 0 ? round(($count / $totalVotes) * 100, 2) : 0,
                    ];
                })->sortByDesc('count')->values(),
            ];
        })->values();

        $pending = $voting->participantPivots
            ->filter(function ($participant) {
                return !$participant->voted_at;
            })
            ->map(function ($participant) {
                return [
                    'name' => $participant->user->name,
                ];
            })
            ->values();

        return response()->json([
            'title' => $voting->judul,
            'total_participants' => $voting->participantPivots->count(),
            'total_voted' => $voting->participantPivots->whereNotNull('voted_at')->count(),
            'pending_count' => $pending->count(),
            'pending' => $pending,
            'items' => $items,
            'all_voted' => $pending->count() === 0 && $voting->participantPivots->count() > 0,
        ]);
    }

    protected function findVoting($publicCode)
    {
        return Voting::with(['items.candidates', 'participantPivots.user'])->where('public_code', $publicCode)->firstOrFail();
    }
}
