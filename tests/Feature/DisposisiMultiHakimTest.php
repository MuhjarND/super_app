<?php

namespace Tests\Feature;

use App\Disposisi;
use App\Http\Controllers\DisposisiController;
use App\Jabatan;
use App\Services\ActivityAuditService;
use App\Services\WhatsAppNotificationService;
use App\SuratMasuk;
use App\Unit;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class DisposisiMultiHakimTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->timestamps();
        });
        Schema::create('jabatans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode')->unique();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('level')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->unsignedBigInteger('jabatan_id')->nullable();
            $table->string('jabatan_keterangan')->nullable();
            $table->integer('hirarki')->nullable();
            $table->boolean('status_aktif_pegawai')->default(true);
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();
        });
        Schema::create('user_jabatan_delegations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('jabatan_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('surat_masuks', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat');
            $table->text('perihal');
            $table->string('status');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
        Schema::create('disposisis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('surat_masuk_id');
            $table->unsignedBigInteger('dari_user_id');
            $table->unsignedBigInteger('kepada_user_id');
            $table->unsignedBigInteger('dari_jabatan_id')->nullable();
            $table->unsignedBigInteger('kepada_jabatan_id')->nullable();
            $table->string('petunjuk')->nullable();
            $table->text('catatan')->nullable();
            $table->text('catatan_tindak_lanjut')->nullable();
            $table->string('tautan_tindak_lanjut')->nullable();
            $table->string('tipe');
            $table->string('status');
            $table->string('priority_level')->default('normal');
            $table->dateTime('target_tindak_lanjut_at')->nullable();
            $table->dateTime('read_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('notification_sent_at')->nullable();
            $table->dateTime('reminder_whatsapp_sent_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Auth::logout();
        Schema::dropIfExists('disposisis');
        Schema::dropIfExists('surat_masuks');
        Schema::dropIfExists('user_jabatan_delegations');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('jabatans');
        Schema::dropIfExists('units');
        Mockery::close();

        parent::tearDown();
    }

    public function test_wakil_ketua_can_dispose_to_multiple_high_judges_with_individual_notifications(): void
    {
        $pimpinan = Unit::create(['kode' => 'PIMPINAN', 'nama' => 'Pimpinan']);
        $hakimUnit = Unit::create(['kode' => 'HAKIM_TINGGI', 'nama' => 'Hakim Tinggi']);
        $ketuaJabatan = Jabatan::create(['kode' => 'KPTA', 'nama' => 'Ketua PTA', 'level' => 1, 'unit_id' => $pimpinan->id]);
        $wakilJabatan = Jabatan::create(['kode' => 'WKPTA', 'nama' => 'Wakil Ketua PTA', 'level' => 1, 'unit_id' => $pimpinan->id]);
        Jabatan::create(['kode' => 'SEK', 'nama' => 'Sekretaris', 'level' => 2, 'unit_id' => $pimpinan->id]);
        Jabatan::create(['kode' => 'PAN', 'nama' => 'Panitera', 'level' => 2, 'unit_id' => $pimpinan->id]);

        $ketua = User::create([
            'name' => 'Ketua',
            'unit_id' => $pimpinan->id,
            'jabatan_id' => $ketuaJabatan->id,
            'status_aktif_pegawai' => true,
        ]);
        $wakil = User::create([
            'name' => 'Wakil Ketua',
            'unit_id' => $pimpinan->id,
            'jabatan_id' => $wakilJabatan->id,
            'status_aktif_pegawai' => true,
        ]);
        $hakimSatu = User::create([
            'name' => 'Hakim Tinggi Satu',
            'unit_id' => $hakimUnit->id,
            'jabatan_keterangan' => 'Hakim Tinggi',
            'status_aktif_pegawai' => true,
        ]);
        $hakimDua = User::create([
            'name' => 'Hakim Tinggi Dua',
            'unit_id' => $hakimUnit->id,
            'jabatan_keterangan' => 'Hakim Tinggi',
            'status_aktif_pegawai' => true,
        ]);

        $surat = SuratMasuk::create([
            'nomor_surat' => '100/UND/VII/2026',
            'perihal' => 'Undangan pembinaan',
            'status' => 'didisposisi',
            'created_by' => $wakil->id,
        ]);
        $incoming = Disposisi::create([
            'surat_masuk_id' => $surat->id,
            'dari_user_id' => $ketua->id,
            'kepada_user_id' => $wakil->id,
            'dari_jabatan_id' => $ketuaJabatan->id,
            'kepada_jabatan_id' => $wakilJabatan->id,
            'petunjuk' => 'Untuk diketahui',
            'tipe' => 'disposisi',
            'status' => 'pending',
            'priority_level' => 'normal',
        ]);

        $wakil->setRelation('roles', collect());
        $wakil->setRelation('jabatan', $wakilJabatan);
        $wakil->setRelation('activeJabatanDelegations', collect());
        Auth::login($wakil);

        $whatsApp = Mockery::mock(WhatsAppNotificationService::class);
        $whatsApp->shouldReceive('notifyDisposisi')
            ->twice()
            ->andReturn(true);
        $audit = Mockery::mock(ActivityAuditService::class);
        $audit->shouldReceive('log')
            ->twice()
            ->andReturnNull();

        $request = Request::create('/disposisi', 'POST', [
            'surat_masuk_id' => $surat->id,
            'kepada_user_ids' => [$hakimSatu->id, $hakimDua->id],
            'tipe' => 'disposisi',
            'petunjuk' => 'Harap dihadiri/diwakili',
            'catatan' => 'Mohon menghadiri kegiatan.',
            'priority_level' => 'normal',
        ]);

        $response = (new DisposisiController($whatsApp, $audit))->store($request);

        $this->assertTrue($response->getData(true)['success']);
        $this->assertSame('ditindaklanjuti', $incoming->fresh()->status);
        $this->assertDatabaseHas('disposisis', [
            'surat_masuk_id' => $surat->id,
            'dari_user_id' => $wakil->id,
            'kepada_user_id' => $hakimSatu->id,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('disposisis', [
            'surat_masuk_id' => $surat->id,
            'dari_user_id' => $wakil->id,
            'kepada_user_id' => $hakimDua->id,
            'status' => 'pending',
        ]);
        $this->assertSame(
            2,
            Disposisi::where('surat_masuk_id', $surat->id)
                ->where('dari_user_id', $wakil->id)
                ->count()
        );
    }
}
