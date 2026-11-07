<?php

namespace App\Services;

use App\Voting;
use App\VotingVote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VotingBallotService
{
    public function submit(Voting $voting, $participantId, array $choices)
    {
        return DB::transaction(function () use ($voting, $participantId, $choices) {
            $locked = Voting::whereKey($voting->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'aktif') {
                throw ValidationException::withMessages(['voting' => 'Voting belum aktif atau sudah selesai.']);
            }
            $participant = $locked->participantPivots()->where('user_id', $participantId)
                ->whereHas('user', function ($query) { $query->active(); })
                ->lockForUpdate()->first();
            if (!$participant) {
                throw ValidationException::withMessages(['participant_id' => 'Peserta tidak terdaftar atau sudah tidak aktif.']);
            }
            if ($participant->voted_at || $locked->votes()->where('user_id', $participantId)->exists()) {
                throw ValidationException::withMessages(['participant_id' => 'Peserta tersebut sudah melakukan voting.']);
            }

            $items = $locked->items()->with('candidates')->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['choices' => 'Voting belum memiliki item yang dapat dipilih.']);
            }
            $errors = [];
            foreach ($items as $item) {
                $value = $choices[$item->id] ?? null;
                if (!is_scalar($value) || !ctype_digit((string) $value)
                    || !$item->candidates->contains('id', (int) $value)) {
                    $errors['choices.' . $item->id] = 'Pilih satu ' . ($item->uses_candidates ? 'kandidat' : 'jawaban') . ' yang valid untuk ' . $item->judul . '.';
                }
            }
            if ($errors) throw ValidationException::withMessages($errors);

            $now = now('Asia/Jayapura');
            foreach ($items as $item) {
                VotingVote::create([
                    'voting_id' => $locked->id, 'voting_item_id' => $item->id,
                    'voting_candidate_id' => (int) $choices[$item->id],
                    'user_id' => $participant->user_id, 'voted_at' => $now,
                ]);
            }
            $participant->update(['voted_at' => $now]);
        });
    }
}
