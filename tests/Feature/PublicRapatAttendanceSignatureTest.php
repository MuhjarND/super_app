<?php

namespace Tests\Feature;

use App\Rapat;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicRapatAttendanceSignatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        Schema::create('klasifikasi_kodes', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->nullable();
            $table->string('nama')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('jabatan_keterangan')->nullable();
            $table->unsignedInteger('hirarki')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('rapats', function (Blueprint $table) {
            $table->id();
            $table->string('public_code')->unique();
            $table->boolean('is_attendance_only')->default(false);
            $table->unsignedBigInteger('attendance_surat_keluar_id')->nullable();
            $table->timestamps();
        });

        Schema::create('rapat_peserta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapat_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('urutan')->default(0);
        });

        Schema::create('rapat_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapat_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('attendance_type')->default('internal');
            $table->string('participant_name_snapshot');
            $table->string('participant_jabatan_snapshot')->nullable();
            $table->string('guest_instansi')->nullable();
            $table->string('source')->default('public');
            $table->string('signature_path')->nullable();
            $table->string('signature_mime')->nullable();
            $table->unsignedBigInteger('signature_size')->nullable();
            $table->timestamp('attended_at');
            $table->string('created_ip', 45)->nullable();
            $table->timestamps();
            $table->unique(['rapat_id', 'user_id']);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('rapat_attendances');
        Schema::dropIfExists('rapat_peserta');
        Schema::dropIfExists('rapats');
        Schema::dropIfExists('users');
        Schema::dropIfExists('klasifikasi_kodes');

        parent::tearDown();
    }

    public function testPublicAttendanceRejectsParticipantWithoutSignature()
    {
        [$rapat, $participant] = $this->createMeetingParticipant();

        $response = $this->postJson(route('rapat.absensi.public.store', $rapat->public_code), [
            'user_id' => $participant->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('signature_data');
        $this->assertDatabaseCount('rapat_attendances', 0);
    }

    public function testPublicAttendanceStoresParticipantSignature()
    {
        [$rapat, $participant] = $this->createMeetingParticipant();

        $response = $this->postJson(route('rapat.absensi.public.store', $rapat->public_code), [
            'user_id' => $participant->id,
            'signature_data' => $this->signatureDataUri(),
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $attendance = $rapat->attendances()->first();
        $this->assertNotNull($attendance);
        $this->assertNotEmpty($attendance->signature_path);
        $this->assertSame('image/png', $attendance->signature_mime);
        Storage::disk('public')->assertExists($attendance->signature_path);
    }

    public function testPublicGuestAttendanceAlsoRequiresSignature()
    {
        $rapat = Rapat::create(['public_code' => 'public-guest-signature']);

        $response = $this->postJson(route('rapat.absensi.public.guest', $rapat->public_code), [
            'guest_name' => 'Peserta External',
            'guest_instansi' => 'Instansi Pengujian',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('signature_data');
    }

    public function testPublicAttendanceParticipantsAreOrderedByEmployeeHierarchy()
    {
        $rapat = Rapat::create(['public_code' => 'public-hierarchy-order']);
        $hakim = User::create([
            'name' => 'Hakim Tinggi',
            'email' => 'hakim@example.test',
            'password' => bcrypt('password'),
            'hirarki' => 3,
        ]);
        $ketua = User::create([
            'name' => 'Ketua',
            'email' => 'ketua@example.test',
            'password' => bcrypt('password'),
            'hirarki' => 1,
        ]);
        $tanpaHierarki = User::create([
            'name' => 'Pegawai Tanpa Hierarki',
            'email' => 'pegawai@example.test',
            'password' => bcrypt('password'),
            'hirarki' => null,
        ]);

        $rapat->pesertas()->attach($hakim->id, ['urutan' => 1]);
        $rapat->pesertas()->attach($tanpaHierarki->id, ['urutan' => 2]);
        $rapat->pesertas()->attach($ketua->id, ['urutan' => 3]);

        $view = app(\App\Http\Controllers\RapatAbsensiController::class)
            ->publicShow($rapat->public_code);
        $names = $view->getData()['rapat']->pesertas->pluck('name')->all();

        $this->assertSame([
            'Ketua',
            'Hakim Tinggi',
            'Pegawai Tanpa Hierarki',
        ], $names);
    }

    protected function createMeetingParticipant()
    {
        $rapat = Rapat::create(['public_code' => 'public-internal-signature']);
        $participant = User::create([
            'name' => 'Peserta Rapat',
            'email' => 'peserta@example.test',
            'password' => bcrypt('password'),
            'jabatan_keterangan' => 'Staf',
        ]);
        $rapat->pesertas()->attach($participant->id, ['urutan' => 1]);

        return [$rapat, $participant];
    }

    protected function signatureDataUri()
    {
        return 'data:image/png;base64,' . base64_encode(str_repeat('papeda-signature-binary', 20));
    }
}
