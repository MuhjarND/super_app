<?php

namespace Tests\Feature;

use App\AgendaPimpinan;
use App\Services\IntegratedCalendarService;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicCalendarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->boolean('status_aktif_pegawai')->default(true);
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
        });
        Schema::create('agenda_pimpinans', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_kegiatan');
            $table->time('waktu');
            $table->string('judul_agenda');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        require_once database_path('migrations/2026_11_24_000001_add_public_calendar_title_to_agenda_pimpinans.php');
        (new \AddPublicCalendarTitleToAgendaPimpinans())->up();

        require_once database_path('migrations/2026_11_24_000002_create_calendar_publications_table.php');
        (new \CreateCalendarPublicationsTable())->up();
    }

    public function test_feed_only_returns_explicitly_public_titles_and_schedule(): void
    {
        $public = AgendaPimpinan::create([
            'judul_agenda' => 'Judul Internal',
            'calendar_public_title' => 'Kegiatan Terbuka',
            'tanggal_kegiatan' => '2026-11-24',
            'waktu' => '09:15:00',
            'catatan' => 'Catatan Internal',
        ]);
        AgendaPimpinan::create(['judul_agenda' => 'Kegiatan Internal', 'tanggal_kegiatan' => '2026-11-25', 'waktu' => '10:00:00']);
        AgendaPimpinan::create([
            'judul_agenda' => 'Bulan Berikutnya', 'calendar_public_title' => 'Jadwal Desember',
            'tanggal_kegiatan' => '2026-12-01', 'waktu' => '12:00:00',
        ]);

        $this->getJson('/api/kalender-publik?month=2026-11')->assertOk()->assertExactJson([
            'events' => [['title' => 'Kegiatan Terbuka', 'date' => '2026-11-24', 'time' => '09:15']],
        ]);
        $public->update(['calendar_public_title' => null]);
        $this->getJson('/api/kalender-publik?month=2026-11')->assertOk()->assertExactJson(['events' => []]);
        $this->getJson('/api/kalender-publik?month=2026-13')->assertStatus(422);
    }

    public function test_calendar_can_publish_multiple_modules_and_tracks_schedule_changes(): void
    {
        DB::table('users')->insert([['id' => 1], ['id' => 2]]);
        DB::table('roles')->insert(['id' => 1, 'name' => 'super_admin']);
        DB::table('role_user')->insert(['user_id' => 1, 'role_id' => 1]);
        $admin = User::findOrFail(1);
        $other = User::findOrFail(2)->setRelation('activeJabatanDelegations', collect());
        $date = '2026-11-24';
        $schedule = [];
        foreach (['rapat', 'virtual-meeting', 'cuti', 'zi', 'surat-tugas', 'surat-keluar-calendar'] as $source) {
            $schedule[$source . '-1'] = [
                'id' => $source . '-1', 'title' => 'Judul internal',
                'start' => $date . 'T09:00:00+09:00', 'end' => $date . 'T10:00:00+09:00', 'allDay' => false,
                'extendedProps' => ['participants' => 'Peserta rahasia'],
            ];
        }

        $calendar = \Mockery::mock(IntegratedCalendarService::class);
        $calendar->shouldReceive('build')->withArgs(function ($user, $filters, $keys) {
            return $user->id === 1 && $filters === ['start' => '2026-11-01', 'end' => '2026-11-30'] && $keys;
        })->andReturnUsing(function ($user, $filters, $keys) use (&$schedule) {
            return ['events' => array_values(array_intersect_key($schedule, array_flip($keys)))];
        });
        $this->app->instance(IntegratedCalendarService::class, $calendar);

        $endpoint = '/kalender-terpadu/publikasi';
        $feed = '/api/kalender-publik?month=2026-11';
        $payload = ['event_key' => 'rapat-1', 'start' => $schedule['rapat-1']['start'], 'public_title' => 'Rapat Publik'];
        $this->getJson($feed)->assertOk()->assertExactJson(['events' => []]);
        $this->putJson($endpoint, $payload)->assertStatus(401);
        $this->actingAs($other)->putJson($endpoint, $payload)->assertStatus(403);

        $this->actingAs($admin)->putJson($endpoint, array_merge($payload, ['event_key' => 'rapat-99']))->assertStatus(404);
        foreach ($schedule as $key => $event) {
            $this->putJson($endpoint, ['event_key' => $key, 'start' => $event['start'], 'public_title' => 'Publik ' . $key])
                ->assertOk();
        }

        $schedule['rapat-1']['start'] = '2026-11-25T13:00:00+09:00';
        $schedule['rapat-1']['end'] = '2026-11-25T14:00:00+09:00';
        $response = $this->getJson($feed)->assertOk()->assertDontSee('Judul internal')->assertDontSee('Peserta rahasia');
        $events = $response->json('events');
        $this->assertCount(6, $events);
        $this->assertSame(['title' => 'Publik rapat-1', 'date' => '2026-11-25', 'time' => '13:00'], end($events));

        $this->putJson($endpoint, array_merge($payload, ['public_title' => null]))->assertOk();
        $this->getJson($feed)->assertOk()->assertDontSee('Publik rapat-1');
        DB::table('users')->where('id', 1)->update(['status_aktif_pegawai' => false]);
        $this->getJson($feed)->assertOk()->assertExactJson(['events' => []]);
    }
}
