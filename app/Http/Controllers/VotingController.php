<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVotingRequest;
use App\User;
use App\Voting;
use App\VotingCandidate;
use App\VotingItem;
use App\VotingParticipant;
use App\VotingVote;
use App\Services\WhatsAppNotificationService;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VotingController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppNotificationService $whatsAppService)
    {
        $this->middleware('auth');
        $this->whatsAppService = $whatsAppService;
    }

    public function index()
    {
        abort_unless(auth()->user()->canManageVoting(), 403);

        $votings = Voting::with(['creator', 'participantPivots', 'votes'])
            ->orderByDesc('created_at')
            ->get();

        return view('rapat.voting.index', compact('votings'));
    }

    public function create()
    {
        abort_unless(auth()->user()->canManageVoting(), 403);

        $users = User::with('jabatan')->ordered()->get();

        return view('rapat.voting.form', [
            'voting' => new Voting(['status' => 'draft']),
            'users' => $users,
            'formAction' => route('rapat.voting.store'),
            'formMethod' => 'POST',
            'pageTitle' => 'Buat Voting',
        ]);
    }

    public function store(StoreVotingRequest $request)
    {
        abort_unless(auth()->user()->canManageVoting(), 403);

        $data = $request->validated();

        $voting = DB::transaction(function () use ($data) {
            $voting = Voting::create([
                'judul' => $data['judul'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'status' => $data['status'],
                'select_all_participants' => (bool) ($data['select_all_participants'] ?? false),
                'public_code' => strtoupper(Str::random(10)),
                'token_qr' => (string) Str::uuid(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->syncItems($voting, $data['items']);
            $this->syncParticipants($voting, $data);

            return $voting;
        });

        if ($voting->status === 'aktif') {
            $this->whatsAppService->notifyVotingParticipants($voting->fresh(['participantPivots.user', 'items']));
        }

        return redirect()->route('rapat.voting.show', $voting)->with('success', 'Voting berhasil dibuat.');
    }

    public function show(Voting $voting)
    {
        abort_unless(auth()->user()->canManageVoting(), 403);

        $voting->load([
            'creator',
            'items.candidates',
            'participantPivots.user.jabatan',
            'votes',
        ]);

        return view('rapat.voting.show', compact('voting'));
    }

    public function edit(Voting $voting)
    {
        abort_unless(auth()->user()->canManageVoting(), 403);

        $voting->load(['items.candidates', 'participants']);
        $users = User::with('jabatan')->ordered()->get();

        return view('rapat.voting.form', [
            'voting' => $voting,
            'users' => $users,
            'formAction' => route('rapat.voting.update', $voting),
            'formMethod' => 'PUT',
            'pageTitle' => 'Edit Voting',
        ]);
    }

    public function update(StoreVotingRequest $request, Voting $voting)
    {
        abort_unless(auth()->user()->canManageVoting(), 403);

        $data = $request->validated();

        DB::transaction(function () use ($voting, $data) {
            $voting->update([
                'judul' => $data['judul'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'status' => $data['status'],
                'select_all_participants' => (bool) ($data['select_all_participants'] ?? false),
                'updated_by' => auth()->id(),
            ]);

            $this->replaceItems($voting, $data['items']);
            $this->syncParticipants($voting, $data);
        });

        $voting->refresh();
        if ($voting->status === 'aktif' && !$voting->participant_notified_at) {
            $this->whatsAppService->notifyVotingParticipants($voting->fresh(['participantPivots.user', 'items']));
        }

        return redirect()->route('rapat.voting.show', $voting)->with('success', 'Voting berhasil diperbarui.');
    }

    public function destroy(Voting $voting)
    {
        abort_unless(auth()->user()->canManageVoting(), 403);

        $voting->delete();

        return redirect()->route('rapat.voting.index')->with('success', 'Voting berhasil dihapus.');
    }

    public function stats(Voting $voting)
    {
        abort_unless(auth()->user()->canManageVoting(), 403);

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
                    'id' => $participant->user_id,
                    'name' => $participant->user->name,
                ];
            })
            ->values();

        return response()->json([
            'total_participants' => $voting->participantPivots->count(),
            'total_voted' => $voting->participantPivots->whereNotNull('voted_at')->count(),
            'pending_count' => $pending->count(),
            'pending' => $pending,
            'items' => $items,
            'all_voted' => $pending->count() === 0 && $voting->participantPivots->count() > 0,
        ]);
    }

    public function resultsPdf(Voting $voting)
    {
        abort_unless(auth()->user()->canManageVoting(), 403);

        $voting->load(['items.candidates.votes', 'participantPivots.user']);

        $pdf = PDF::loadView('rapat.voting.pdf.results', compact('voting'))
            ->setPaper('a4', 'portrait');

        return $pdf->stream('hasil-voting-' . $voting->id . '.pdf');
    }

    public function sendWhatsapp(Voting $voting)
    {
        abort_unless(auth()->user()->canManageVoting(), 403);

        $result = $this->whatsAppService->notifyVotingParticipants($voting->fresh(['participantPivots.user', 'items']));
        $processed = $result || !$this->whatsAppService->isConfigured();

        return redirect()->route('rapat.voting.show', $voting)->with(
            $processed ? 'success' : 'error',
            $processed
                ? 'Notifikasi voting diproses untuk peserta yang terdaftar.'
                : 'Notifikasi voting tidak dikirim. Kemungkinan sudah pernah dikirim atau peserta tidak memiliki nomor WhatsApp.'
        );
    }

    protected function syncItems(Voting $voting, array $items)
    {
        foreach (array_values($items) as $index => $itemData) {
            $item = VotingItem::create([
                'voting_id' => $voting->id,
                'judul' => $itemData['judul'],
                'deskripsi' => $itemData['deskripsi'] ?? null,
                'urutan' => $index + 1,
            ]);

            $this->syncCandidates($item, $itemData['candidate_ids']);
        }
    }

    protected function replaceItems(Voting $voting, array $items)
    {
        $voting->items()->each(function ($item) {
            $item->candidates()->delete();
        });
        $voting->items()->delete();
        $this->syncItems($voting, $items);
    }

    protected function syncCandidates(VotingItem $item, array $candidateIds)
    {
        $users = User::with('jabatan')
            ->whereIn('id', array_values($candidateIds))
            ->ordered()
            ->get();

        foreach ($users as $index => $user) {
            VotingCandidate::create([
                'voting_item_id' => $item->id,
                'user_id' => $user->id,
                'nama_snapshot' => $user->name,
                'jabatan_snapshot' => $user->jabatan_keterangan ?: optional($user->jabatan)->nama,
                'urutan' => $index + 1,
            ]);
        }
    }

    protected function syncParticipants(Voting $voting, array $data)
    {
        $participantIds = (bool) ($data['select_all_participants'] ?? false)
            ? User::ordered()->pluck('id')->all()
            : (array) ($data['participant_ids'] ?? []);

        $orderedUsers = User::whereIn('id', $participantIds)
            ->ordered()
            ->get();

        $existingVotedAt = $voting->participantPivots()->pluck('voted_at', 'user_id');
        $syncData = [];

        foreach ($orderedUsers as $index => $user) {
            $syncData[$user->id] = [
                'urutan' => $index + 1,
                'voted_at' => $existingVotedAt[$user->id] ?? null,
            ];
        }

        $removedParticipantIds = $voting->participantPivots()
            ->whereNotIn('user_id', array_keys($syncData))
            ->pluck('user_id')
            ->all();

        if (!empty($removedParticipantIds)) {
            $voting->votes()->whereIn('user_id', $removedParticipantIds)->delete();
        }

        $voting->participants()->sync($syncData);
    }
}
