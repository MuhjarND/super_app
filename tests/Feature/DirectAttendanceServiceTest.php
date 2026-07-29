<?php

namespace Tests\Feature;

use App\Rapat;
use App\Services\DirectAttendanceService;
use App\SuratKeluar;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
            $table->unsignedBigInteger('attendance_surat_keluar_id')->nullable()->unique();
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
    }

    protected function tearDown(): void
    {
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

    public function testOneOutgoingLetterCannotCreateDuplicateAttendance()
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
        $service->createFromSuratKeluar($suratKeluar, $creator, $payload);

        $this->expectException(ValidationException::class);
        $service->createFromSuratKeluar($suratKeluar, $creator, $payload);
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
