<?php

namespace Tests\Feature;

use App\Http\Controllers\MobileModuleMenuController;
use App\Role;
use App\Services\MobileNotificationBadgeService;
use App\Services\ModuleSettingService;
use App\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Tests\TestCase;

class ModuleManagementMenuTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('atasan_langsung_id')->nullable();
        });
    }

    public function test_desktop_management_links_belong_to_their_module_and_are_not_duplicated()
    {
        $html = $this->renderSidebar($this->user('super_admin'));
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $mapping = [
            'admin.kategori-surats.index' => 'persuratan',
            'admin.kategori-rapats.index' => 'rapat',
            'admin.dasar-hukums.index' => 'rapat',
            'cuti.master.types.index' => 'cuti',
            'cuti.master.policies.index' => 'cuti',
            'cuti.master.delegations.index' => 'cuti',
            'progress-zi.periods.index' => 'progress-zi',
            'progress-zi.areas.index' => 'progress-zi',
            'admin.users.index' => 'master-data',
            'admin.jabatans.index' => 'master-data',
            'admin.units.index' => 'master-data',
        ];
        foreach ($mapping as $route => $section) {
            $selector = '//aside//li[@data-section="' . $section . '"]//a[@href="' . route($route) . '"]';
            $this->assertSame(1, $xpath->query($selector)->length, $route);
            $this->assertSame(1, $xpath->query('//aside//a[@href="' . route($route) . '"]')->length, $route);
        }
        $this->assertSame(3, $xpath->query('//aside//li[@data-section="master-data"]//a')->length);
        $this->assertStringContainsString('Administrasi Pengguna', $html);
    }

    public function test_mobile_management_links_match_desktop_grouping()
    {
        $user = $this->user('super_admin');
        $mapping = [
            'persuratan' => ['admin.kategori-surats.index'],
            'rapat' => ['admin.kategori-rapats.index', 'admin.dasar-hukums.index'],
            'cuti' => ['cuti.master.types.index', 'cuti.master.policies.index', 'cuti.master.delegations.index'],
            'zi' => ['progress-zi.periods.index', 'progress-zi.areas.index'],
        ];
        foreach ($mapping as $module => $routes) {
            $urls = array_column($this->mobileMenu($user, $module)['items'], 'url');
            foreach ($routes as $route) {
                $this->assertContains(route($route), $urls);
            }
        }
        $admin = $this->mobileMenu($user, 'master-data');
        $this->assertSame('Administrasi Pengguna', $admin['title']);
        $this->assertSame(['User', 'Jabatan', 'Unit Kerja'], array_column($admin['items'], 'label'));
    }

    public function test_general_employee_does_not_gain_access_to_superadmin_management_links()
    {
        $user = $this->user('pegawai');
        foreach ([
            'persuratan' => ['admin.kategori-surats.index'],
            'rapat' => ['admin.kategori-rapats.index', 'admin.dasar-hukums.index'],
        ] as $module => $routes) {
            $urls = array_column($this->mobileMenu($user, $module)['items'], 'url');
            $html = $this->renderSidebar($user, $module);
            foreach ($routes as $route) {
                $this->assertNotContains(route($route), $urls);
                $this->assertStringNotContainsString('href="' . route($route) . '"', $html);
            }
        }
        $this->assertSame([], $this->mobileMenu($user, 'master-data')['items']);
    }

    public function test_management_page_marks_its_link_active_in_the_correct_dropdown()
    {
        $request = \Illuminate\Http\Request::create(route('cuti.master.types.index'));
        $route = Route::getRoutes()->getByName('cuti.master.types.index');
        $route->bind($request);
        $request->setRouteResolver(function () use ($route) { return $route; });
        $this->app->instance('request', $request);
        $html = $this->renderSidebar($this->user('super_admin'));
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $this->assertSame(1, $xpath->query(
            '//aside//li[@data-section="cuti"]//a[@href="' . route('cuti.master.types.index') . '"][contains(@class, "active")]'
        )->length);
    }

    public function test_leave_manager_sees_management_in_leave_menu_but_employee_does_not()
    {
        foreach (['admin_kepegawaian', 'ppk', 'pegawai'] as $role) {
            $user = $this->user($role);
            $urls = array_column($this->mobileMenu($user, 'cuti')['items'], 'url');
            $html = $this->renderSidebar($user, 'cuti');
            foreach (['types', 'policies', 'delegations'] as $resource) {
                $url = route('cuti.master.' . $resource . '.index');
                if ($role === 'pegawai') {
                    $this->assertNotContains($url, $urls);
                    $this->assertStringNotContainsString('href="' . $url . '"', $html);
                } else {
                    $this->assertContains($url, $urls);
                    $this->assertStringContainsString('href="' . $url . '"', $html);
                }
            }
        }
    }

    public function test_zi_manager_sees_zi_management_but_employee_does_not()
    {
        foreach (['sekretaris', 'pegawai'] as $role) {
            $user = $this->user($role);
            $urls = array_column($this->mobileMenu($user, 'zi')['items'], 'url');
            $html = $this->renderSidebar($user, 'progress_zi');
            foreach (['periods', 'areas'] as $resource) {
                $url = route('progress-zi.' . $resource . '.index');
                if ($role === 'pegawai') {
                    $this->assertNotContains($url, $urls);
                    $this->assertStringNotContainsString('href="' . $url . '"', $html);
                } else {
                    $this->assertContains($url, $urls);
                    $this->assertStringContainsString('href="' . $url . '"', $html);
                }
            }
        }
    }

    protected function mobileMenu(User $user, $module)
    {
        $controller = new MobileModuleMenuController(
            app(ModuleSettingService::class),
            $this->createMock(MobileNotificationBadgeService::class)
        );
        $method = new ReflectionMethod($controller, 'buildMenu');
        $method->setAccessible(true);

        return $method->invoke($controller, $user, $module);
    }

    protected function renderSidebar(User $user, $onlyModule = null)
    {
        // Render navigation independently of dashboard statistics and database data.
        $this->app['events']->forget('composing: layouts.app');
        $this->actingAs($user);
        $controls = collect(config('modules.catalog'))->map(function ($definition, $key) use ($onlyModule) {
            return ['visible_desktop' => !$onlyModule || $key === $onlyModule, 'visible_mobile' => false];
        })->all();

        return view('layouts.app', [
            'moduleControls' => $controls,
            'mobileNotificationBadges' => [],
            'sidebarSuratMasukOpenCount' => 0,
            'sidebarSuratKeluarDraftCount' => 0,
        ])->render();
    }

    protected function user($roleName)
    {
        $role = new Role();
        $role->name = $roleName;
        $user = new User();
        $user->id = 100;
        $user->name = 'Test User';
        $user->setRelation('roles', collect([$role]));
        $user->setRelation('jabatan', null);
        $user->setRelation('activeJabatanDelegations', collect());
        $user->setRelation('tindakLanjutNotulensiItems', collect());

        return $user;
    }
}
