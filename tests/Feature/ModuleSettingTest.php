<?php

namespace Tests\Feature;

use App\ModuleSetting;
use App\Role;
use App\Services\ModuleSettingService;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModuleSettingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('module_settings')) {
            Schema::create('module_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('module_key')->unique();
                $table->string('status')->default('published');
                $table->string('custom_label')->nullable();
                $table->text('maintenance_message')->nullable();
                $table->boolean('show_desktop')->default(true);
                $table->boolean('show_mobile')->default(true);
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->text('settings')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        DB::table('module_settings')->insert([
            'module_key' => 'dashboard',
            'status' => 'published',
            'show_desktop' => true,
            'show_mobile' => true,
            'display_order' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_maintenance_module_is_blocked_for_regular_user()
    {
        ModuleSetting::where('module_key', 'dashboard')->update([
            'status' => ModuleSetting::STATUS_MAINTENANCE,
            'maintenance_message' => 'Dashboard sedang diperbarui.',
        ]);

        $response = $this->actingAs($this->userWithRole('pegawai'))->get('/dashboard');

        $response->assertStatus(503);
        $response->assertSee('Sedang Maintenance');
        $response->assertSee('Dashboard sedang diperbarui.');
    }

    public function test_superadmin_bypasses_module_maintenance()
    {
        ModuleSetting::where('module_key', 'dashboard')->update([
            'status' => ModuleSetting::STATUS_MAINTENANCE,
        ]);

        $state = app(ModuleSettingService::class)->state('dashboard', $this->userWithRole('super_admin'));

        $this->assertTrue($state['accessible']);
        $this->assertTrue($state['visible_desktop']);
        $this->assertTrue($state['visible_mobile']);
    }

    public function test_route_names_resolve_to_expected_modules()
    {
        $service = app(ModuleSettingService::class);

        $this->assertSame('persuratan', $service->resolveRoute('surat-masuk.index'));
        $this->assertSame('rapat', $service->resolveRoute('rapat.notulensi.follow-ups'));
        $this->assertSame('library', $service->resolveRoute('library.books.index'));
        $this->assertNull($service->resolveRoute('admin.module-settings.index'));
    }

    public function test_device_visibility_does_not_disable_direct_module_access()
    {
        ModuleSetting::where('module_key', 'dashboard')->update([
            'status' => ModuleSetting::STATUS_PUBLISHED,
            'show_desktop' => true,
            'show_mobile' => false,
        ]);

        $state = app(ModuleSettingService::class)->state('dashboard', $this->userWithRole('pegawai'));

        $this->assertTrue($state['accessible']);
        $this->assertTrue($state['visible_desktop']);
        $this->assertFalse($state['visible_mobile']);
    }

    protected function userWithRole($roleName)
    {
        $role = new Role();
        $role->forceFill(['name' => $roleName, 'display_name' => $roleName]);

        $user = new User();
        $user->id = $roleName === 'super_admin' ? 900001 : 900002;
        $user->exists = true;
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('jabatan', null);
        $user->setRelation('activeJabatanDelegations', collect());

        return $user;
    }
}
