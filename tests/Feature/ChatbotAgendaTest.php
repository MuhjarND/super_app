<?php

namespace Tests\Feature;

use App\Role;
use App\Services\IntegratedCalendarService;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatbotAgendaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['chatbot.internal_api_key' => 'test-key']);
        Carbon::setTestNow(Carbon::parse('2026-10-15 16:30:00', 'UTC'));

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->boolean('status_aktif_pegawai')->default(true);
            $table->boolean('is_active')->default(true);
        });
        DB::table('users')->insert([
            ['id' => 76, 'email' => 'first@example.test', 'status_aktif_pegawai' => true, 'is_active' => true],
            ['id' => 77, 'email' => 'other@example.test', 'status_aktif_pegawai' => false, 'is_active' => true],
        ]);
        User::retrieved(function ($user) {
            $user->setRelation('roles', collect([new Role(['name' => 'super_admin'])]));
            $user->setRelation('activeJabatanDelegations', collect());
            $user->setRelation('jabatan', null);
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_endpoint_rejects_missing_wrong_and_unconfigured_keys()
    {
        $this->postJson('/api/chatbot/agenda', ['app_user_id' => '76'])->assertStatus(401);
        $this->postJson('/api/chatbot/agenda', ['app_user_id' => '76'], ['X-INTERNAL-API-KEY' => 'wrong'])->assertStatus(401);
        config(['chatbot.internal_api_key' => '']);
        $this->postJson('/api/chatbot/agenda', ['app_user_id' => '76'], ['X-INTERNAL-API-KEY' => 'test-key'])->assertStatus(401);
    }

    public function test_endpoint_forces_personal_scope_and_wit_week_even_for_admin()
    {
        $calendar = \Mockery::mock(IntegratedCalendarService::class);
        $calendar->shouldReceive('build')->once()
            ->withArgs(function ($user, $filters) {
                return $user->id === 76 && $filters === [
                    'scope' => 'mine', 'start' => '2026-10-16', 'end' => '2026-10-22',
                ];
            })->andReturn(['events' => [$this->event()]]);
        $this->app->instance(IntegratedCalendarService::class, $calendar);

        $this->postJson('/api/chatbot/agenda', [
            'app_user_id' => '76', 'scope' => 'all', 'start' => '2000-01-01', 'user_id' => 77,
        ], ['X-INTERNAL-API-KEY' => 'test-key'])
            ->assertOk()
            ->assertJsonPath('start_date', '2026-10-16')
            ->assertJsonPath('end_date', '2026-10-22')
            ->assertJsonPath('events.0.title', 'Rapat pribadi')
            ->assertJsonMissing(['description' => 'private-notes']);
    }

    public function test_endpoint_accepts_email_mapping_and_empty_agenda()
    {
        $calendar = \Mockery::mock(IntegratedCalendarService::class);
        $calendar->shouldReceive('build')->once()->withArgs(function ($user) {
            return $user->id === 76;
        })->andReturn(['events' => []]);
        $this->app->instance(IntegratedCalendarService::class, $calendar);

        $this->postJson('/api/chatbot/agenda', ['app_user_id' => 'first@example.test'], ['X-INTERNAL-API-KEY' => 'test-key'])
            ->assertOk()->assertJsonPath('events', []);
    }

    public function test_endpoint_rejects_inactive_unknown_and_invalid_accounts()
    {
        $calendar = \Mockery::mock(IntegratedCalendarService::class);
        $calendar->shouldNotReceive('build');
        $this->app->instance(IntegratedCalendarService::class, $calendar);
        foreach (['77', '999', '76invalid'] as $id) {
            $this->postJson('/api/chatbot/agenda', ['app_user_id' => $id], ['X-INTERNAL-API-KEY' => 'test-key'])
                ->assertStatus(403);
        }
        $this->postJson('/api/chatbot/agenda', [], ['X-INTERNAL-API-KEY' => 'test-key'])->assertStatus(422);
    }

    protected function event(): array
    {
        return [
            'title' => 'Rapat pribadi', 'start' => '2026-10-16T09:00:00+09:00',
            'end' => '2026-10-16T10:00:00+09:00', 'allDay' => false,
            'extendedProps' => [
                'module_label' => 'Rapat', 'status_label' => 'Berjalan',
                'location' => 'Ruang rapat', 'time_label' => '09:00 WIT', 'description' => 'private-notes',
            ],
        ];
    }
}
