<?php

namespace Tests\Feature;

use App\KlasifikasiKode;
use App\Rapat;
use App\Role;
use App\Services\DocumentQrCodeService;
use App\Services\PdfVerificationService;
use App\Services\RapatDocumentService;
use App\SuratKeluar;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class RapatSatkerSuratKeluarSyncTest extends TestCase
{
    protected $tables = [
        'surat_keluar_penerima', 'surat_keluars', 'rapat_satker', 'rapat_peserta', 'rapats',
        'kategori_surats', 'klasifikasi_kodes', 'role_user', 'roles', 'users',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->boolean('status_aktif_pegawai')->default(true);
            $table->integer('hirarki')->nullable();
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('user_id');
        });
        Schema::create('klasifikasi_kodes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kode');
            $table->string('nama');
            $table->string('tipe');
            $table->unsignedInteger('parent_id')->nullable();
            $table->timestamps();
        });
        Schema::create('kategori_surats', function (Blueprint $table) {
            $table->increments('id');
            $table->string('kode');
            $table->string('nama');
            $table->timestamps();
        });
        Schema::create('rapats', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nomor_undangan')->default('');
            $table->string('judul');
            $table->unsignedInteger('kategori_surat_kode_id');
            $table->string('nomenklatur_jabatan');
            $table->boolean('bersama_satker')->default(false);
            $table->boolean('is_external')->default(false);
            $table->text('tujuan_surat')->nullable();
            $table->text('tujuan_external')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('rapat_peserta', function (Blueprint $table) {
            $table->unsignedInteger('rapat_id');
            $table->unsignedInteger('user_id');
            $table->integer('urutan')->default(1);
        });
        Schema::create('rapat_satker', function (Blueprint $table) {
            $table->unsignedInteger('rapat_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();
        });
        Schema::create('surat_keluars', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('legacy_source_id')->nullable();
            $table->string('nomor_surat');
            $table->integer('nomor_urut');
            $table->integer('tahun_surat');
            $table->unsignedInteger('klasifikasi_kode_id')->nullable();
            $table->unsignedInteger('kategori_surat_id')->nullable();
            $table->unsignedInteger('kode_fungsi_id')->nullable();
            $table->unsignedInteger('kode_kegiatan_id')->nullable();
            $table->unsignedInteger('kode_transaksi_id')->nullable();
            $table->string('nomenklatur_jabatan');
            $table->string('opsi_penerima');
            $table->text('penerima_external')->nullable();
            $table->string('perihal');
            $table->date('tanggal_surat');
            $table->boolean('has_lampiran')->default(true);
            $table->string('status')->default('draft');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('rapat_id')->nullable();
            $table->unsignedInteger('satker_id')->nullable();
            $table->boolean('is_satker_collective')->default(false);
            $table->timestamps();
        });
        Schema::create('surat_keluar_penerima', function (Blueprint $table) {
            $table->unsignedInteger('surat_keluar_id');
            $table->unsignedInteger('user_id');
        });
    }

    protected function tearDown(): void
    {
        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }
        Mockery::close();
        parent::tearDown();
    }

    public function test_all_satkers_create_one_collective_external_letter(): void
    {
        [$rapat, $satkers] = $this->meetingWithSatkers(4);
        $rapat->satkers()->sync($satkers->pluck('id'));
        $this->sync($rapat);

        $this->assertSame(1, SuratKeluar::where('rapat_id', $rapat->id)->count());
        $letter = SuratKeluar::where('rapat_id', $rapat->id)->first();
        $this->assertSame('external', $letter->opsi_penerima);
        $this->assertTrue($letter->is_satker_collective);
        $this->assertNull($letter->satker_id);
        $this->assertSame('Seluruh Satker Sewilayah Hukum PTA Papua Barat', $letter->penerima_external);
    }

    public function test_three_selected_satkers_create_three_external_letters_with_distinct_numbers(): void
    {
        [$rapat, $satkers] = $this->meetingWithSatkers(4);
        $selected = $satkers->take(3);
        $rapat->satkers()->sync($selected->pluck('id'));
        $this->sync($rapat);

        $letters = SuratKeluar::where('rapat_id', $rapat->id)->orderBy('nomor_urut')->get();
        $this->assertCount(3, $letters);
        $this->assertCount(3, $letters->pluck('nomor_surat')->unique());
        $this->assertSame($selected->pluck('name')->sort()->values()->all(), $letters->pluck('penerima_external')->sort()->values()->all());
        $this->assertTrue($letters->every(function ($letter) { return $letter->opsi_penerima === 'external'; }));
        $this->assertFalse($letters->contains('is_satker_collective', true));
    }

    protected function meetingWithSatkers($count)
    {
        $role = Role::create(['name' => 'satker', 'display_name' => 'Satuan Kerja']);
        $satkers = collect();
        foreach (range(1, $count) as $index) {
            $user = User::create(['name' => 'Pengadilan Agama ' . $index, 'status_aktif_pegawai' => true]);
            $user->roles()->attach($role->id);
            $satkers->push($user);
        }
        $classification = KlasifikasiKode::create(['kode' => 'OT', 'nama' => 'Organisasi', 'tipe' => 'klasifikasi']);
        $rapat = Rapat::create([
            'judul' => 'Rapat Bersama Satker',
            'kategori_surat_kode_id' => $classification->id,
            'nomenklatur_jabatan' => 'ketua',
            'bersama_satker' => true,
        ]);

        return [$rapat, $satkers];
    }

    protected function sync(Rapat $rapat)
    {
        $service = new RapatDocumentService(
            Mockery::mock(DocumentQrCodeService::class),
            Mockery::mock(PdfVerificationService::class)
        );
        return $service->syncSuratKeluar($rapat->fresh(), false);
    }
}
