<?php

namespace Tests\Feature;

use App\Role;
use App\User;
use App\Voting;
use App\VotingVote;
use App\Services\VotingBallotService;
use App\Services\VotingStatsService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\Support\BuildsVotingSchema;
use Tests\TestCase;

class VotingModesTest extends TestCase
{
    use BuildsVotingSchema;

    protected $manager;
    protected $participant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createVotingSchema();
        $this->manager = $this->user('Admin', 'admin');
        $this->participant = $this->user('Peserta', 'pegawai');
        $this->mock(WhatsAppNotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyVotingParticipants')->andReturn(true);
        });
    }

    public function test_answer_mode_does_not_require_candidates_and_ignores_hidden_candidate_fields()
    {
        $this->withoutExceptionHandling();
        $payload = $this->payload();
        $payload['items'][0]['candidate_ids'] = ['not-a-user'];
        $this->actingAs($this->manager)->post(route('rapat.voting.store'), $payload)->assertStatus(302)->assertSessionHasNoErrors();
        $voting = Voting::with('items.candidates')->firstOrFail();
        $this->assertFalse($voting->items[0]->uses_candidates);
        $this->assertSame(['Setuju', 'Tidak setuju'], $voting->items[0]->candidates->pluck('nama_snapshot')->all());
        $this->assertNull($voting->items[0]->candidates[0]->user_id);
    }

    public function test_candidate_mode_requires_at_least_two_active_users()
    {
        $payload = $this->payload();
        $payload['items'][0] = ['judul' => 'Pemilihan ketua', 'uses_candidates' => '1', 'candidate_ids' => [$this->manager->id]];
        $this->actingAs($this->manager)->post(route('rapat.voting.store'), $payload)->assertSessionHasErrors('items.0.candidate_ids');
        $this->assertSame(0, Voting::count());
    }

    public function test_answer_images_are_stored_and_exposed_in_public_and_live_results()
    {
        Storage::fake('public');
        $payload = $this->payload();
        // Sparse keys model deleting another answer before uploading.
        $payload['items'][0]['options'] = [0=>'Rancangan A', 2=>'Rancangan B'];
        $payload['items'][0]['option_images'] = [2=>UploadedFile::fake()->image('rancangan-b.png')];
        $this->actingAs($this->manager)->post(route('rapat.voting.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $voting = Voting::firstOrFail();
        $choices = $voting->items()->first()->candidates;
        $this->assertNull($choices[0]->image_path);
        $this->assertSame('Rancangan B', $choices[1]->nama_snapshot);
        $this->assertSame('rancangan-b.png', $choices[1]->image_name);
        Storage::disk('public')->assertExists($choices[1]->image_path);
        $voting->update(['status'=>'aktif']);
        $this->get(route('rapat.voting.public.show', $voting->public_code))->assertOk()->assertSee($choices[1]->image_url);
        $stats = $this->getJson(route('rapat.voting.public.stats', $voting->public_code))->assertOk();
        $this->assertSame($choices[1]->image_url, $stats->json('items.0.candidates.1.image_url'));
        $voting->load('items.candidates.votes', 'participantPivots');
        $this->assertStringContainsString('>Gambar</th>', view('rapat.voting.pdf.results', compact('voting'))->render());
    }

    public function test_answer_image_rejects_non_images_and_files_larger_than_three_mb()
    {
        Storage::fake('public');
        foreach ([UploadedFile::fake()->create('document.pdf',20,'application/pdf'), UploadedFile::fake()->image('large.jpg')->size(3073)] as $file) {
            $payload = $this->payload(); $payload['items'][0]['option_images'] = [0=>$file];
            $this->actingAs($this->manager)->post(route('rapat.voting.store'), $payload)->assertSessionHasErrors('items.0.option_images.0');
        }
        $this->assertSame(0, Voting::count());
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }

    public function test_answer_images_can_be_kept_replaced_and_removed_when_editing()
    {
        Storage::fake('public');
        $payload = $this->payload(); $payload['items'][0]['option_images'] = [0=>UploadedFile::fake()->image('first.png')];
        $this->actingAs($this->manager)->post(route('rapat.voting.store'), $payload)->assertSessionHasNoErrors();
        $voting = Voting::first(); $first = $voting->items()->first()->candidates()->first(); $originalPath = $first->image_path;
        $edit = $this->payload(); $edit['items'][0]['existing_option_image_ids'] = [0=>$first->id];
        $this->put(route('rapat.voting.update', $voting), $edit)->assertSessionHasNoErrors();
        $kept = $voting->items()->first()->candidates()->first();
        $this->assertSame($originalPath, $kept->image_path);
        $this->assertSame('first.png', $kept->image_name);
        Storage::disk('public')->assertExists($originalPath);
        $edit['items'][0]['existing_option_image_ids'] = [0=>$kept->id];
        $edit['items'][0]['option_images'] = [0=>UploadedFile::fake()->image('replacement.jpg')];
        $this->put(route('rapat.voting.update', $voting), $edit)->assertSessionHasNoErrors();
        $replaced = $voting->items()->first()->candidates()->first();
        $this->assertSame('replacement.jpg', $replaced->image_name);
        Storage::disk('public')->assertMissing($originalPath);
        Storage::disk('public')->assertExists($replaced->image_path);
        $this->put(route('rapat.voting.update', $voting), $this->payload())->assertSessionHasNoErrors();
        $this->assertNull($voting->items()->first()->candidates()->first()->image_path);
        Storage::disk('public')->assertMissing($replaced->image_path);
    }

    public function test_images_from_another_voting_cannot_be_reused()
    {
        $source = $this->ballot(); $target = $this->ballot();
        $payload = $this->payload();
        $payload['items'][0]['existing_option_image_ids'] = [0=>$source->items[0]->candidates[0]->id];
        $this->actingAs($this->manager)->put(route('rapat.voting.update', $target), $payload)->assertSessionHasErrors('items.0.existing_option_image_ids.0');
    }

    public function test_both_voting_modes_accept_three_mb_and_reject_any_larger_image()
    {
        Storage::fake('public');
        foreach ([false, true] as $useCandidates) {
            foreach ([3072, 3073] as $size) {
                $payload = $this->payload();
                $payload['items'][0]['uses_candidates'] = $useCandidates ? '1' : '0';
                $field = $useCandidates ? 'candidate_images' : 'option_images';
                $key = $useCandidates ? $this->manager->id : 0;
                if ($useCandidates) $payload['items'][0]['candidate_ids'] = [$this->manager->id, $this->participant->id];
                $payload['items'][0][$field] = [$key => UploadedFile::fake()->image('boundary.png')->size($size)];
                $response = $this->actingAs($this->manager)->post(route('rapat.voting.store'), $payload);
                if ($size === 3072) $response->assertSessionHasNoErrors();
                else $response->assertSessionHasErrors('items.0.' . $field . '.' . $key);
            }
        }
        $this->assertSame(2, Voting::count());
    }

    public function test_legacy_payload_defaults_to_candidates_and_allows_same_candidate_in_different_items()
    {
        $payload = $this->payload();
        $payload['items'] = array_fill(0, 2, ['judul' => 'Pemilihan', 'candidate_ids' => [$this->manager->id, $this->participant->id]]);
        $this->actingAs($this->manager)->post(route('rapat.voting.store'), $payload)->assertSessionHasNoErrors();
        $this->assertSame(2, Voting::first()->items()->where('uses_candidates', true)->count());
    }

    public function test_duplicate_case_insensitive_answers_are_rejected()
    {
        $payload = $this->payload();
        $payload['items'][0]['options'] = ['Setuju', ' setuju '];
        $this->actingAs($this->manager)->post(route('rapat.voting.store'), $payload)->assertSessionHasErrors('items.0.options');
    }

    public function test_blank_answers_are_rejected()
    {
        $payload = $this->payload(); $payload['items'][0]['options'] = ['Setuju', ' '];
        $this->actingAs($this->manager)->post(route('rapat.voting.store'), $payload)->assertSessionHasErrors('items.0.options.1');
    }

    public function test_non_manager_cannot_create_voting()
    {
        $this->actingAs($this->participant)->post(route('rapat.voting.store'), $this->payload())->assertStatus(403);
    }

    public function test_mixed_ballot_is_recorded_once_and_live_endpoints_agree()
    {
        $voting = $this->ballot();
        $choices = $this->choices($voting);
        $url = route('rapat.voting.public.store', $voting->public_code);
        $this->postJson($url, ['participant_id' => $this->participant->id, 'choices' => $choices])->assertOk()->assertJson(['success' => true]);
        $this->postJson($url, ['participant_id' => $this->participant->id, 'choices' => $choices])->assertStatus(422);
        $this->assertSame(2, VotingVote::count());
        $public = $this->getJson(route('rapat.voting.public.stats', $voting->public_code))->assertOk()->assertJson(['total_voted' => 1, 'pending_count' => 0, 'all_voted' => true]);
        $admin = $this->actingAs($this->manager)->getJson(route('rapat.voting.stats', $voting))->assertOk();
        $this->assertSame($public->json('items'), $admin->json('items'));
        $this->assertSame(100, (int) $public->json('items.0.candidates.0.percentage'));
        $this->assertStringContainsString('no-store', $public->headers->get('Cache-Control'));
    }

    public function test_incomplete_ballot_rolls_back_every_vote()
    {
        $voting = $this->ballot();
        $choices = $this->choices($voting); array_pop($choices);
        $this->postJson(route('rapat.voting.public.store', $voting->public_code), ['participant_id'=>$this->participant->id, 'choices'=>$choices])->assertStatus(422);
        $this->assertSame(0, VotingVote::count());
        $this->assertNull($voting->participantPivots()->first()->voted_at);
    }

    public function test_choices_from_another_item_are_rejected_atomically()
    {
        $voting = $this->ballot(); $choices = $this->choices($voting);
        $keys = array_keys($choices); $choices[$keys[0]] = $choices[$keys[1]];
        $this->postJson(route('rapat.voting.public.store', $voting->public_code), ['participant_id'=>$this->participant->id, 'choices'=>$choices])->assertStatus(422);
        $this->assertSame(0, VotingVote::count());
    }

    public function test_unregistered_and_inactive_participants_cannot_vote()
    {
        $voting = $this->ballot();
        $this->postJson(route('rapat.voting.public.store', $voting->public_code), ['participant_id'=>$this->manager->id, 'choices'=>$this->choices($voting)])->assertStatus(422);
        $this->participant->update(['status_aktif_pegawai'=>false]);
        $this->postJson(route('rapat.voting.public.store', $voting->public_code), ['participant_id'=>$this->participant->id, 'choices'=>$this->choices($voting)])->assertStatus(422);
    }

    public function test_closing_voting_rejects_further_ballots_but_preserves_results()
    {
        $voting = $this->ballot();
        app(VotingBallotService::class)->submit($voting, $this->participant->id, $this->choices($voting));
        $this->actingAs($this->manager)->patch(route('rapat.voting.status', $voting), ['status'=>'selesai'])->assertSessionHasNoErrors();
        $this->postJson(route('rapat.voting.public.store', $voting->public_code), ['participant_id'=>$this->participant->id, 'choices'=>$this->choices($voting)])->assertStatus(422);
        $this->getJson(route('rapat.voting.public.stats', $voting->public_code))->assertOk()->assertJson(['status'=>'selesai','total_voted'=>1]);
        $this->assertSame(2, VotingVote::count());
    }

    public function test_edits_after_first_vote_cannot_destroy_existing_results()
    {
        $voting = $this->ballot();
        app(VotingBallotService::class)->submit($voting, $this->participant->id, $this->choices($voting));
        $this->actingAs($this->manager)->put(route('rapat.voting.update', $voting), $this->payload())->assertSessionHasErrors('items');
        $this->assertSame(2, VotingVote::count());
        $this->actingAs($this->manager)->patch(route('rapat.voting.status', $voting), ['status'=>'draft'])->assertSessionHasErrors('status');
    }

    public function test_draft_results_are_not_public()
    {
        $voting = $this->ballot(); $voting->update(['status'=>'draft']);
        $this->get(route('rapat.voting.public.results', $voting->public_code))->assertStatus(404);
        $this->getJson(route('rapat.voting.public.stats', $voting->public_code))->assertStatus(404);
    }

    public function test_statistics_do_not_run_a_query_for_each_choice()
    {
        $voting = $this->ballot();
        foreach (range(1, 18) as $index) $voting->items[0]->candidates()->create(['nama_snapshot'=>'Pilihan ' . $index, 'urutan'=>$index+2]);
        DB::enableQueryLog(); DB::flushQueryLog();
        $stats = app(VotingStatsService::class)->build($voting);
        $this->assertCount(20, $stats['items'][0]['candidates']);
        $this->assertLessThanOrEqual(4, count(DB::getQueryLog()));
        DB::disableQueryLog();
    }

    public function test_existing_ballot_can_switch_mode_before_votes_exist()
    {
        $voting = $this->ballot();
        $this->actingAs($this->manager)->put(route('rapat.voting.update', $voting), $this->payload())->assertRedirect(route('rapat.voting.show', $voting))->assertSessionHasNoErrors();
        $this->assertSame(1, $voting->items()->count());
        $this->assertFalse($voting->items()->first()->uses_candidates);
        $this->assertSame(2, $voting->items()->first()->candidates()->whereNull('user_id')->count());
    }

    public function test_results_and_pdf_use_answer_labels_for_non_candidate_votes()
    {
        $voting = $this->ballot();
        $this->get(route('rapat.voting.public.results', $voting->public_code))->assertOk()->assertSee('Voting pilihan jawaban')->assertSee('Setuju');
        $voting->load('items.candidates.votes', 'participantPivots');
        $html = view('rapat.voting.pdf.results', compact('voting'))->render();
        $this->assertStringContainsString('Pilihan Jawaban', $html);
        $this->assertStringContainsString('Tidak setuju', $html);
        $this->assertStringContainsString('Kandidat', $html);
    }

    public function test_percentages_and_pending_counts_follow_each_submitted_ballot()
    {
        $voting = $this->ballot();
        $voting->participants()->attach($this->manager->id, ['urutan'=>2]);
        app(VotingBallotService::class)->submit($voting, $this->participant->id, $this->choices($voting));
        $choices = $voting->items->mapWithKeys(function ($item) { return [$item->id=>$item->candidates->last()->id]; })->all();
        app(VotingBallotService::class)->submit($voting, $this->manager->id, $choices);
        $stats = app(VotingStatsService::class)->build($voting);
        $this->assertSame(2, $stats['total_voted']);
        $this->assertSame(0, $stats['pending_count']);
        $this->assertTrue($stats['all_voted']);
        $this->assertSame(50, (int) $stats['items'][0]['candidates'][0]['percentage']);
    }

    protected function user($name, $role)
    {
        $user = User::create(['name'=>$name,'email'=>strtolower($name).'@example.test','password'=>'password']);
        $user->roles()->attach(Role::firstOrCreate(['name'=>$role], ['display_name'=>$role])->id);
        $user->setRelation('activeJabatanDelegations', collect());
        return $user;
    }

    protected function payload()
    {
        return ['judul'=>'Evaluasi kantor', 'status'=>'draft', 'participant_ids'=>[$this->participant->id], 'items'=>[
            ['judul'=>'Persetujuan program', 'uses_candidates'=>'0', 'options'=>['Setuju','Tidak setuju']],
        ]];
    }

    protected function ballot()
    {
        $voting = Voting::create(['judul'=>'Voting campuran','status'=>'aktif','public_code'=>'TEST'.uniqid(),'created_by'=>$this->manager->id]);
        $voting->participants()->attach($this->participant->id, ['urutan'=>1]);
        $item = $voting->items()->create(['judul'=>'Persetujuan','uses_candidates'=>false,'urutan'=>1]);
        foreach (['Setuju','Tidak setuju'] as $i=>$label) $item->candidates()->create(['nama_snapshot'=>$label,'urutan'=>$i+1]);
        $election = $voting->items()->create(['judul'=>'Ketua tim','uses_candidates'=>true,'urutan'=>2]);
        foreach ([$this->manager,$this->participant] as $i=>$person) $election->candidates()->create(['user_id'=>$person->id,'nama_snapshot'=>$person->name,'urutan'=>$i+1]);
        return $voting->load('items.candidates');
    }

    protected function choices(Voting $voting)
    {
        return $voting->items->mapWithKeys(function ($item) { return [$item->id => $item->candidates->first()->id]; })->all();
    }
}
