<?php

namespace Tests\Feature;

use App\Role;
use App\SuratKeluar;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuratKeluarUploadLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->string('name'); });
        Schema::create('surat_keluars', function (Blueprint $table) {
            $table->id(); $table->string('nomor_surat'); $table->string('file_path')->nullable();
            $table->string('status'); $table->unsignedBigInteger('created_by'); $table->timestamps();
        });
        Schema::create('surat_keluar_approvals', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('surat_keluar_id'); $table->string('template_slug');
        });
        Schema::create('surat_keluar_penerima', function (Blueprint $table) {
            $table->unsignedBigInteger('surat_keluar_id'); $table->unsignedBigInteger('user_id');
        });
        $user = new User(['name'=>'Admin']); $user->id = 1;
        $role = new Role(['name'=>'admin','display_name'=>'Admin']);
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('jabatan', null);
        $user->setRelation('activeJabatanDelegations', collect());
        $this->actingAs($user);
    }

    public function test_three_mb_letter_is_accepted()
    {
        $letter = $this->letter();
        $this->postJson(route('surat-keluar.upload', $letter), [
            'file'=>UploadedFile::fake()->image('surat.png')->size(3072),
        ])->assertOk()->assertJson(['success'=>true]);
        Storage::disk('public')->assertExists($letter->fresh()->file_path);
    }

    public function test_larger_letter_is_rejected_before_the_previous_file_is_replaced()
    {
        $letter = $this->letter();
        $this->postJson(route('surat-keluar.upload', $letter), [
            'file'=>UploadedFile::fake()->image('terlalu-besar.png')->size(3073),
        ])->assertStatus(422)->assertJsonValidationErrors('file')
            ->assertJson(['errors'=>['file'=>['Ukuran berkas surat keluar maksimal 3 MB.']]]);
        $this->assertSame('surat-keluar/previous.pdf', $letter->fresh()->file_path);
        Storage::disk('public')->assertExists('surat-keluar/previous.pdf');
    }

    protected function letter()
    {
        Storage::disk('public')->put('surat-keluar/previous.pdf', '%PDF Previous letter');
        return SuratKeluar::create(['nomor_surat'=>'QA/2026', 'file_path'=>'surat-keluar/previous.pdf', 'status'=>'lengkap', 'created_by'=>1]);
    }
}
