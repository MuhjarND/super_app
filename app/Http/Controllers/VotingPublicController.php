<?php

namespace App\Http\Controllers;

use App\Voting;
use App\Services\VotingBallotService;
use App\Services\VotingStatsService;
use Illuminate\Http\Request;
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

        $data = $request->validate($rules, [
            'participant_id.required' => 'Peserta voting wajib dipilih.',
        ]);

        app(VotingBallotService::class)->submit($voting, $data['participant_id'], $data['choices']);

        return response()->json([
            'success' => true,
            'message' => 'Voting berhasil direkam.',
            'redirect_url' => route('rapat.voting.public.results', $voting->public_code),
        ]);
    }

    public function results($publicCode)
    {
        $voting = $this->findVoting($publicCode);
        abort_if($voting->status === 'draft', 404);
        $stats = app(VotingStatsService::class)->build($voting);
        return view('rapat.voting.public-results', compact('voting', 'stats'));
    }

    public function stats($publicCode)
    {
        $voting = $this->findVoting($publicCode);
        abort_if($voting->status === 'draft', 404);
        return response()->json(app(VotingStatsService::class)->build($voting))
            ->header('Cache-Control', 'no-store, private');
    }

    protected function findVoting($publicCode)
    {
        return Voting::where('public_code', $publicCode)->firstOrFail();
    }
}
