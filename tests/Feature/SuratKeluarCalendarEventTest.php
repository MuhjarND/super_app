<?php

namespace Tests\Feature;

use App\Role;
use App\SuratKeluar;
use App\SuratKeluarCalendarEvent;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SuratKeluarCalendarEventTest extends TestCase
{
    protected $letter;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->string('name'); $table->timestamps(); });
        Schema::create('surat_keluars', function (Blueprint $table) {
            $table->id(); $table->string('nomor_surat'); $table->string('perihal');
            $table->unsignedBigInteger('created_by'); $table->timestamps();
        });
        require_once database_path('migrations/2026_07_21_000003_create_surat_keluar_calendar_events_table.php');
        (new \CreateSuratKeluarCalendarEventsTable)->up();
        $user = User::create(['name' => 'Pembuat surat']);
        $user->setRelation('roles', collect([new Role(['name' => 'pegawai'])]));
        $user->setRelation('jabatan', null);
        $user->setRelation('activeJabatanDelegations', collect());
        $this->actingAs($user);
        $this->letter = SuratKeluar::create(['nomor_surat' => 'QA/2026', 'perihal' => 'ST Pelatihan AI Gelombang 3', 'created_by' => $user->id]);
    }

    public function test_start_time_without_end_time_can_be_saved_for_a_multi_day_letter()
    {
        $this->save(['start_time' => '10:00', 'end_time' => ''])->assertSessionHasNoErrors();
        $event = $this->letter->calendarEvent()->firstOrFail();
        $this->assertSame('10:00', $event->start_time);
        $this->assertNull($event->end_time);
        $this->assertSame('2026-11-13', $event->end_date->format('Y-m-d'));
        $this->assertSame(1, (int) $event->created_by);
    }

    public function test_both_times_can_be_empty_for_an_all_day_event()
    {
        $this->save(['start_time' => '', 'end_time' => ''])->assertSessionHasNoErrors();
        $event = $this->letter->calendarEvent()->firstOrFail();
        $this->assertNull($event->start_time);
        $this->assertNull($event->end_time);
    }

    public function test_an_existing_end_time_can_be_cleared_without_creating_a_duplicate_event()
    {
        $this->save(['start_time' => '10:00', 'end_time' => '16:00'])->assertSessionHasNoErrors();
        $id = SuratKeluarCalendarEvent::firstOrFail()->id;
        $this->save(['start_time' => '10:00', 'end_time' => ''])->assertSessionHasNoErrors();
        $this->assertSame(1, SuratKeluarCalendarEvent::count());
        $this->assertSame($id, SuratKeluarCalendarEvent::first()->id);
        $this->assertNull(SuratKeluarCalendarEvent::first()->end_time);
    }

    public function test_end_time_without_start_time_has_a_readable_indonesian_message()
    {
        $this->save(['end_time' => '16:00'])->assertSessionHasErrors([
            'start_time' => 'Waktu mulai wajib diisi jika waktu selesai diisi.',
        ]);
        $this->assertSame(0, SuratKeluarCalendarEvent::count());
    }

    public function test_invalid_time_format_has_a_readable_message()
    {
        $this->save(['start_time' => '25:00'])->assertSessionHasErrors([
            'start_time' => 'Waktu mulai harus menggunakan format jam:menit (contoh 10:00).',
        ]);
        $this->assertSame(0, SuratKeluarCalendarEvent::count());
    }

    public function test_end_before_start_on_the_same_day_is_rejected_without_overwriting_the_event()
    {
        $this->save(['end_date' => '2026-11-09', 'start_time' => '10:00', 'end_time' => '11:00'])->assertSessionHasNoErrors();
        $this->save(['end_date' => '2026-11-09', 'start_time' => '10:00', 'end_time' => '09:00'])
            ->assertSessionHasErrors(['end_time' => 'Waktu selesai harus setelah waktu mulai.']);
        $this->assertSame('11:00', SuratKeluarCalendarEvent::first()->end_time);
    }

    public function test_an_earlier_clock_time_on_a_later_day_is_valid()
    {
        $this->save(['start_time' => '10:00', 'end_time' => '09:00'])->assertSessionHasNoErrors();
        $this->assertSame('09:00', SuratKeluarCalendarEvent::firstOrFail()->end_time);
    }

    public function test_an_end_date_before_the_start_date_is_rejected_with_a_readable_message()
    {
        $this->save(['end_date' => '2026-11-08'])->assertSessionHasErrors([
            'end_date' => 'Tanggal selesai harus sama dengan atau setelah tanggal mulai.',
        ]);
        $this->assertSame(0, SuratKeluarCalendarEvent::count());
    }

    protected function save(array $data)
    {
        return $this->from(route('surat-keluar.index'))->put(route('surat-keluar.calendar.upsert', $this->letter), array_merge([
            'calendar_surat_id' => $this->letter->id, 'type' => 'surat_tugas',
            'start_date' => '2026-11-09', 'end_date' => '2026-11-13',
        ], $data));
    }
}
