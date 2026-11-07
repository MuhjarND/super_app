<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVotingRequest;
use App\User;
use App\Voting;
use App\VotingCandidate;
use App\VotingItem;
use App\Services\VotingStatsService;
use App\VotingParticipant;
use App\VotingVote;
use App\Services\WhatsAppNotificationService;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

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
        $user = auth()->user();
        abort_unless($user->canAccessVoting(), 403);

        $votings = Voting::with(['creator', 'participantPivots', 'votes'])
            ->orderByDesc('created_at')
            ->get();
        $canManage = $user->canManageVoting();

        return view('rapat.voting.index', compact('votings', 'canManage'));
    }

    public function create()
    {
        abort_unless(auth()->user()->canManageVoting(), 403);

        $users = User::with('jabatan')->active()->ordered()->get();

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
        $user = auth()->user();
        abort_unless($user->canAccessVoting(), 403);

        $voting->load([
            'creator',
            'items.candidates',
            'participantPivots.user.jabatan',
        ]);
        $voting->loadCount('votes');

        $canManage = $user->canManageVoting();

        $stats = app(VotingStatsService::class)->build($voting);
        return view('rapat.voting.show', compact('voting', 'canManage', 'stats'));
    }

    public function edit(Voting $voting)
    {
        abort_unless(auth()->user()->canManageVoting(), 403);
        if ($voting->votes()->exists()) {
            return redirect()->route('rapat.voting.show', $voting)->with('error', 'Pilihan dan peserta dikunci karena suara sudah masuk. Status voting masih dapat diubah.');
        }

        $voting->load(['items.candidates', 'participants']);
        $users = User::with('jabatan')->active()->ordered()->get();

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
            $voting = Voting::whereKey($voting->id)->lockForUpdate()->firstOrFail();
            if ($voting->votes()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['items' => 'Voting sudah menerima suara. Pilihan dan peserta tidak dapat diubah.']);
            }
            $oldImagePaths = $this->candidateImagePaths($voting);

            $voting->update([
                'judul' => $data['judul'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'status' => $data['status'],
                'select_all_participants' => (bool) ($data['select_all_participants'] ?? false),
                'updated_by' => auth()->id(),
            ]);

            $this->replaceItems($voting, $data['items']);
            $this->syncParticipants($voting, $data);

            $voting->load('items.candidates');
            $this->deleteUnusedCandidateImages($oldImagePaths, $this->candidateImagePaths($voting));
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

        $imagePaths = $this->candidateImagePaths($voting);
        $voting->delete();
        $this->deleteUnusedCandidateImages($imagePaths, []);

        return redirect()->route('rapat.voting.index')->with('success', 'Voting berhasil dihapus.');
    }

    public function stats(Voting $voting)
    {
        abort_unless(auth()->user()->canAccessVoting(), 403);
        return response()->json(app(VotingStatsService::class)->build($voting))
            ->header('Cache-Control', 'no-store, private');
    }

    public function updateStatus(Request $request, Voting $voting)
    {
        abort_unless(auth()->user()->canManageVoting(), 403);
        $data = $request->validate(['status' => 'required|in:draft,aktif,selesai']);
        DB::transaction(function () use ($voting, $data) {
            $locked = Voting::whereKey($voting->id)->lockForUpdate()->firstOrFail();
            if ($data['status'] === 'draft' && $locked->votes()->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['status' => 'Voting yang sudah menerima suara tidak dapat dikembalikan ke draft.']);
            }
            $locked->update(['status' => $data['status'], 'updated_by' => auth()->id()]);
        });
        $voting->refresh();
        if ($voting->status === 'aktif' && !$voting->participant_notified_at) {
            $this->whatsAppService->notifyVotingParticipants($voting->fresh(['participantPivots.user', 'items']));
        }
        return back()->with('success', 'Status voting berhasil diperbarui.');
    }

    public function resultsPdf(Voting $voting)
    {
        abort_unless(auth()->user()->canAccessVoting(), 403);

        $voting->load(['items.candidates.votes', 'participantPivots.user']);
        $verifier = app(\App\Services\PdfVerificationService::class);
        $verification = $verifier->begin('rapat', 'hasil_voting', $voting->id, 'Hasil Voting - ' . ($voting->judul ?: $voting->id), [], [
            'voting_id' => $voting->id,
        ]);
        $pdfVerification = $verifier->viewData($verification);

        $pdf = PDF::loadView('rapat.voting.pdf.results', compact('voting', 'pdfVerification'))
            ->setPaper('a4', 'portrait');

        return $verifier->response($pdf->output(), $verification, 'hasil-voting-' . $voting->id . '.pdf');
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

    protected function syncItems(Voting $voting, array $items, array $retainedChoices = [])
    {
        foreach (array_values($items) as $index => $itemData) {
            $item = VotingItem::create([
                'voting_id' => $voting->id,
                'judul' => $itemData['judul'],
                'deskripsi' => $itemData['deskripsi'] ?? null,
                'urutan' => $index + 1,
                'uses_candidates' => (bool) ($itemData['uses_candidates'] ?? true),
            ]);

            if (!$item->uses_candidates) {
                $order = 0;
                foreach ($itemData['options'] as $key => $label) {
                    $file = $itemData['option_images'][$key] ?? null;
                    $existingId = $itemData['existing_option_image_ids'][$key] ?? null;
                    $existing = $existingId ? ($retainedChoices[$existingId] ?? null) : null;
                    $image = $file ? [
                        'image_path' => $this->storeCandidateImage($file),
                        'image_name' => $file->getClientOriginalName(),
                        'image_mime' => $file->getMimeType(),
                        'image_size' => $file->getSize(),
                    ] : ($existing ? $existing->only(['image_path', 'image_name', 'image_mime', 'image_size']) : []);
                    $item->candidates()->create(array_merge([
                        'user_id' => null, 'nama_snapshot' => trim($label), 'urutan' => ++$order,
                    ], $image));
                }
                continue;
            }
            $this->syncCandidates(
                $item,
                $itemData['candidate_ids'],
                $itemData['candidate_images'] ?? [],
                $itemData['existing_candidate_images'] ?? []
            );
        }
    }

    protected function replaceItems(Voting $voting, array $items)
    {
        $voting->loadMissing('items.candidates');
        $retainedChoices = $voting->items->flatMap(function ($item) {
            return $item->candidates;
        })->keyBy('id')->all();
        $voting->items()->each(function ($item) {
            $item->candidates()->delete();
        });
        $voting->items()->delete();
        $this->syncItems($voting, $items, $retainedChoices);
    }

    protected function syncCandidates(VotingItem $item, array $candidateIds, array $candidateImages = [], array $existingCandidateImages = [])
    {
        $users = User::with('jabatan')
            ->whereIn('id', array_values($candidateIds))
            ->active()
            ->ordered()
            ->get();

        foreach ($users as $index => $user) {
            VotingCandidate::create([
                'voting_item_id' => $item->id,
                'user_id' => $user->id,
                'nama_snapshot' => $user->name,
                'jabatan_snapshot' => $user->jabatan_keterangan ?: optional($user->jabatan)->nama,
                'image_path' => $this->candidateImagePathFor($user->id, $candidateImages, $existingCandidateImages),
                'image_name' => $this->candidateImageNameFor($user->id, $candidateImages),
                'image_mime' => $this->candidateImageMimeFor($user->id, $candidateImages),
                'image_size' => $this->candidateImageSizeFor($user->id, $candidateImages),
                'urutan' => $index + 1,
            ]);
        }
    }

    protected function candidateImagePathFor($userId, array $candidateImages, array $existingCandidateImages = [])
    {
        $file = $this->candidateImageFile($userId, $candidateImages);
        if ($file) {
            return $this->storeCandidateImage($file);
        }

        $existingPath = $existingCandidateImages[$userId] ?? $existingCandidateImages[(string) $userId] ?? null;

        return $existingPath && Storage::disk('public')->exists($existingPath) ? $existingPath : null;
    }

    protected function candidateImageNameFor($userId, array $candidateImages)
    {
        $file = $this->candidateImageFile($userId, $candidateImages);

        return $file ? $file->getClientOriginalName() : null;
    }

    protected function candidateImageMimeFor($userId, array $candidateImages)
    {
        $file = $this->candidateImageFile($userId, $candidateImages);

        return $file ? $file->getClientMimeType() : null;
    }

    protected function candidateImageSizeFor($userId, array $candidateImages)
    {
        $file = $this->candidateImageFile($userId, $candidateImages);

        return $file ? $file->getSize() : null;
    }

    protected function candidateImageFile($userId, array $candidateImages)
    {
        return $candidateImages[$userId] ?? $candidateImages[(string) $userId] ?? null;
    }

    protected function storeCandidateImage($file)
    {
        $extension = strtolower($file->extension() ?: 'jpg');
        $path = 'voting/candidates/' . date('Y/m') . '/' . (string) Str::uuid() . '.' . $extension;

        if (!Storage::disk('public')->put($path, file_get_contents($file->getRealPath()))) {
            throw new \RuntimeException('Gambar voting tidak dapat disimpan. Silakan coba kembali.');
        }

        return $path;
    }

    protected function candidateImagePaths(Voting $voting)
    {
        $voting->loadMissing('items.candidates');

        return $voting->items
            ->flatMap(function ($item) {
                return $item->candidates->pluck('image_path');
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function deleteUnusedCandidateImages(array $oldPaths, array $keptPaths)
    {
        $kept = array_flip(array_filter($keptPaths));

        foreach (array_unique(array_filter($oldPaths)) as $path) {
            if (!isset($kept[$path])) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    protected function syncParticipants(Voting $voting, array $data)
    {
        $participantIds = (bool) ($data['select_all_participants'] ?? false)
            ? User::active()->ordered()->pluck('id')->all()
            : (array) ($data['participant_ids'] ?? []);

        $orderedUsers = User::whereIn('id', $participantIds)
            ->active()
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
