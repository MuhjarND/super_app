<?php

namespace Tests\Feature;

use App\Role;
use App\User;
use App\UserJabatanDelegation;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserJabatanDelegationManagementTest extends TestCase
{
    protected $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            foreach (['jabatan_id', 'unit_id', 'hirarki', 'atasan_langsung_id', 'pejabat_berwenang_id', 'masa_kerja_tahun', 'masa_kerja_bulan'] as $column) {
                $table->integer($column)->nullable();
            }
            foreach (['jabatan_keterangan', 'nip', 'no_hp', 'golongan_ruang', 'satuan_kerja'] as $column) {
                $table->string($column)->nullable();
            }
            $table->date('masa_kerja_acuan')->nullable();
            $table->date('tmt_pns')->nullable();
            $table->boolean('status_aktif_pegawai')->default(true);
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('display_name'); $table->timestamps();
        });
        Schema::create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id'); $table->unsignedBigInteger('user_id');
        });
        Schema::create('jabatans', function (Blueprint $table) {
            $table->id(); $table->string('kode'); $table->string('nama');
        });
        require_once database_path('migrations/2026_07_05_000001_create_user_jabatan_delegations_table.php');
        (new \CreateUserJabatanDelegationsTable)->up();
        DB::table('jabatans')->insert([
            ['id' => 1, 'kode' => 'KASUBAG', 'nama' => 'Kasubag'],
            ['id' => 2, 'kode' => 'SEK', 'nama' => 'Sekretaris'],
        ]);
        $adminRole = Role::create(['name' => 'super_admin', 'display_name' => 'Super Admin']);
        $employeeRole = Role::create(['name' => 'pegawai', 'display_name' => 'Pegawai']);
        $admin = User::create(['name' => 'Admin Uji', 'email' => 'admin@example.test']);
        $admin->roles()->attach($adminRole);
        $this->actingAs($admin);
        $this->employee = User::create([
            'name' => 'Pegawai Uji', 'email' => 'pegawai@example.test', 'jabatan_id' => 1,
            'status_aktif_pegawai' => true, 'password' => 'unchanged-password-hash',
        ]);
        $this->employee->roles()->attach($employeeRole);
        UserJabatanDelegation::create([
            'user_id' => $this->employee->id, 'jabatan_id' => 2,
            'delegation_type' => 'plh', 'is_active' => true,
        ]);
    }

    public function test_choosing_none_clears_the_old_plh_even_when_its_position_is_still_submitted()
    {
        $this->save(['delegasi_tipe' => '', 'delegasi_jabatan_id' => 2])
            ->assertRedirect(route('admin.users.index'))->assertSessionHasNoErrors();
        $this->assertSame(0, $this->employee->activeJabatanDelegations()->count());
        $employee = $this->employee->fresh();
        $this->assertTrue($employee->status_aktif_pegawai);
        $this->assertSame(1, (int) $employee->jabatan_id);
        $this->assertSame(['pegawai'], $employee->roles->pluck('name')->all());
        $this->assertSame('unchanged-password-hash', $employee->password);
    }

    public function test_choosing_none_also_clears_plt_when_disabled_position_is_not_submitted()
    {
        UserJabatanDelegation::query()->update(['delegation_type' => 'plt']);
        $this->save(['delegasi_tipe' => ''])->assertSessionHasNoErrors();
        $this->assertSame(0, $this->employee->activeJabatanDelegations()->count());
    }

    /** @dataProvider delegationTypes */
    public function test_a_complete_delegation_can_still_be_saved($type)
    {
        $this->save(['delegasi_tipe' => $type, 'delegasi_jabatan_id' => 2])->assertSessionHasNoErrors();
        $this->assertSame(1, $this->employee->activeJabatanDelegations()->count());
        $delegation = $this->employee->activeJabatanDelegations()->firstOrFail();
        $this->assertSame($type, $delegation->delegation_type);
        $this->assertSame(2, (int) $delegation->jabatan_id);
    }

    public function delegationTypes()
    {
        return [['plh'], ['plt']];
    }

    public function test_missing_position_has_a_readable_message_and_keeps_the_previous_delegation()
    {
        $this->save(['delegasi_tipe' => 'plt', 'delegasi_jabatan_id' => '', 'name' => 'Must not be saved'])
            ->assertSessionHasErrors(['delegasi_jabatan_id' => 'Pilih jabatan delegasi untuk PLH atau PLT.']);
        $this->assertSame('plh', $this->employee->activeJabatanDelegations()->firstOrFail()->delegation_type);
        $this->assertSame('Pegawai Uji', $this->employee->fresh()->name);
    }

    public function test_invalid_delegation_type_is_rejected_without_removing_existing_authority()
    {
        $this->save(['delegasi_tipe' => 'invalid', 'delegasi_jabatan_id' => 2])
            ->assertSessionHasErrors(['delegasi_tipe' => 'Jenis delegasi harus PLH atau PLT.']);
        $this->assertSame('plh', $this->employee->activeJabatanDelegations()->firstOrFail()->delegation_type);
    }

    public function test_nonexistent_position_is_rejected_with_a_readable_message()
    {
        $this->save(['delegasi_tipe' => 'plh', 'delegasi_jabatan_id' => 999])
            ->assertSessionHasErrors(['delegasi_jabatan_id' => 'Jabatan delegasi yang dipilih tidak tersedia.']);
        $this->assertSame(2, (int) $this->employee->activeJabatanDelegations()->firstOrFail()->jabatan_id);
    }

    public function test_regular_employee_cannot_remove_a_delegation()
    {
        $this->actingAs($this->employee);
        $this->save(['delegasi_tipe' => ''])->assertForbidden();
        $this->assertSame(1, $this->employee->activeJabatanDelegations()->count());
    }

    protected function save(array $changes)
    {
        return $this->from(route('admin.users.index'))->put(route('admin.users.update', $this->employee), array_merge([
            'name' => $this->employee->name, 'email' => $this->employee->email,
            'role_ids' => $this->employee->roles->pluck('id')->all(), 'jabatan_id' => 1,
        ], $changes));
    }
}
