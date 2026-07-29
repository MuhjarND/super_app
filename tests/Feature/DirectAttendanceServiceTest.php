<?php

namespace Tests\Feature;

use App\Rapat;
use App\RapatAttendance;
use App\PdfVerification;
use App\Services\DirectAttendanceService;
use App\SuratKeluar;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DirectAttendanceServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('kategori_rapats', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->text('keterangan')->nullable();
            $table->boolean('butuh_pakaian')->default(false);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('surat_keluars', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat');
            $table->string('perihal')->nullable();
            $table->date('tanggal_surat')->nullable();
            $table->unsignedBigInteger('klasifikasi_kode_id')->nullable();
            $table->string('nomenklatur_jabatan')->nullable();
            $table->string('opsi_penerima')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('surat_keluar_penerima', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('surat_keluar_id');
            $table->unsignedBigInteger('user_id');
        });

        Schema::create('rapats', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_undangan');
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->unsignedBigInteger('kategori_rapat_id');
            $table->unsignedBigInteger('kategori_surat_kode_id')->nullable();
            $table->string('nomenklatur_jabatan')->nullable();
            $table->date('tanggal');
            $table->time('waktu_mulai');
            $table->string('tempat');
            $table->boolean('bersama_satker')->default(false);
            $table->string('status')->default('draft');
            $table->string('token_qr')->nullable()->unique();
            $table->string('public_code')->nullable()->unique();
            $table->boolean('is_attendance_only')->default(false);
            $table->unsignedBigInteger('attendance_surat_keluar_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });

        Schema::create('rapat_peserta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rapat_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('urutan')->default(999);
            $table->timestamps();
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
        });

        Schema::create('pdf_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('module');
            $table->string('document_type');
            $table->string('document_id')->nullable();
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('file_hash')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('signers')->nullable();
            $table->text('metadata')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('pdf_verifications');
        Schema::dropIfExists('rapat_attendances');
        Schema::dropIfExists('rapat_peserta');
        Schema::dropIfExists('rapats');
        Schema::dropIfExists('surat_keluar_penerima');
        Schema::dropIfExists('surat_keluars');
        Schema::dropIfExists('kategori_rapats');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function testItCreatesAttendanceOnlyRecordAndCopiesInternalRecipients()
    {
        $creator = $this->createUser('Operator', 'operator@example.test');
        $participant = $this->createUser('Peserta', 'peserta@example.test');
        $suratKeluar = SuratKeluar::create([
            'nomor_surat' => '900/KPTA.W31-A/OT1/VII/2026',
            'perihal' => 'Kegiatan Pembinaan',
            'tanggal_surat' => '2026-07-29',
            'opsi_penerima' => 'internal',
            'status' => 'lengkap',
            'created_by' => $creator->id,
        ]);
        $suratKeluar->penerimaInternal()->attach($participant->id);

        $rapat = app(DirectAttendanceService::class)->createFromSuratKeluar(
            $suratKeluar,
            $creator,
            [
                'judul' => 'Absensi Pembinaan Internal',
                'tanggal' => '2026-07-30',
                'waktu_mulai' => '09:00',
                'tempat' => 'Aula PTA Papua Barat',
            ]
        );

        $this->assertTrue($rapat->is_attendance_only);
        $this->assertSame($suratKeluar->id, $rapat->attendance_surat_keluar_id);
        $this->assertSame('Absensi Pembinaan Internal', $rapat->judul);
        $this->assertSame('disetujui', $rapat->status);
        $this->assertSame(
            [(int) $participant->id],
            $rapat->pesertas()->pluck('users.id')->map(function ($id) {
                return (int) $id;
            })->all()
        );

        $manager = \Mockery::mock(User::class)->makePartial();
        $manager->shouldReceive('canManageRapat')->andReturn(true);
        $this->assertSame(0, Rapat::visibleTo($manager)->count());
        $this->assertSame(1, Rapat::attendanceVisibleTo($manager)->count());
    }

    public function testOneOutgoingLetterCanCreateMultiplePublicAttendances()
    {
        $creator = $this->createUser('Operator', 'operator@example.test');
        $suratKeluar = SuratKeluar::create([
            'nomor_surat' => '901/KPTA.W31-A/OT1/VII/2026',
            'perihal' => 'Kegiatan Evaluasi',
            'tanggal_surat' => '2026-07-29',
            'opsi_penerima' => 'external',
            'status' => 'lengkap',
            'created_by' => $creator->id,
        ]);
        $payload = [
            'judul' => 'Absensi Evaluasi',
            'tanggal' => '2026-07-30',
            'waktu_mulai' => '10:00',
            'tempat' => 'Ruang Rapat',
        ];

        $service = app(DirectAttendanceService::class);
        $first = $service->createFromSuratKeluar($suratKeluar, $creator, $payload);
        $payload['judul'] = 'Absensi Evaluasi Kedua';
        $payload['tanggal'] = '2026-07-31';
        $second = $service->createFromSuratKeluar($suratKeluar, $creator, $payload);

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame($suratKeluar->id, $first->attendance_surat_keluar_id);
        $this->assertSame($suratKeluar->id, $second->attendance_surat_keluar_id);
        $this->assertSame(2, Rapat::where('attendance_surat_keluar_id', $suratKeluar->id)->count());
    }

    public function testItDeletesDirectAttendanceAndItsSignatureFiles()
    {
        Storage::fake('public');

        $creator = $this->createUser('Operator', 'operator-delete@example.test');
        $participant = $this->createUser('Peserta', 'participant-delete@example.test');
        $suratKeluar = SuratKeluar::create([
            'nomor_surat' => '902/KPTA.W31-A/OT1/VII/2026',
            'perihal' => 'Kegiatan yang Dihapus',
            'tanggal_surat' => '2026-07-29',
            'opsi_penerima' => 'internal',
            'status' => 'lengkap',
            'created_by' => $creator->id,
        ]);
        $suratKeluar->penerimaInternal()->attach($participant->id);

        $service = app(DirectAttendanceService::class);
        $rapat = $service->createFromSuratKeluar($suratKeluar, $creator, [
            'judul' => 'Absensi yang Dihapus',
            'tanggal' => '2026-07-30',
            'waktu_mulai' => '11:00',
            'tempat' => 'Ruang Rapat',
        ]);

        $signaturePath = 'rapat/attendance-signatures/delete-test.png';
        Storage::disk('public')->put($signaturePath, 'signature');
        RapatAttendance::create([
            'rapat_id' => $rapat->id,
            'user_id' => $participant->id,
            'attendance_type' => 'internal',
            'participant_name_snapshot' => $participant->name,
            'source' => 'public',
            'signature_path' => $signaturePath,
            'signature_mime' => 'image/png',
            'signature_size' => 9,
            'attended_at' => now(),
        ]);
        $verificationPath = 'pdf-verifications/2026/07/delete-test.pdf';
        Storage::disk('public')->put($verificationPath, 'pdf');
        PdfVerification::create([
            'token' => 'delete-direct-attendance-token',
            'module' => 'rapat',
            'document_type' => 'laporan_absensi',
            'document_id' => (string) $rapat->id,
            'title' => 'Laporan Absensi',
            'file_path' => $verificationPath,
        ]);

        $service->deleteDirectAttendance($rapat);

        $this->assertDatabaseMissing('rapats', ['id' => $rapat->id]);
        $this->assertDatabaseMissing('rapat_attendances', ['rapat_id' => $rapat->id]);
        $this->assertDatabaseMissing('rapat_peserta', ['rapat_id' => $rapat->id]);
        $this->assertDatabaseMissing('pdf_verifications', [
            'module' => 'rapat',
            'document_type' => 'laporan_absensi',
            'document_id' => (string) $rapat->id,
        ]);
        $this->assertDatabaseHas('surat_keluars', ['id' => $suratKeluar->id]);
        Storage::disk('public')->assertMissing($signaturePath);
        Storage::disk('public')->assertMissing($verificationPath);

        $replacement = $service->createFromSuratKeluar($suratKeluar, $creator, [
            'judul' => 'Absensi Pengganti',
            'tanggal' => '2026-07-31',
            'waktu_mulai' => '09:00',
            'tempat' => 'Aula',
        ]);
        $this->assertSame($suratKeluar->id, $replacement->attendance_surat_keluar_id);
    }

    protected function createUser($name, $email)
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('password'),
        ]);
    }
}
