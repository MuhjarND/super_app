<?php

namespace Tests\Feature;

use App\Http\Controllers\SupplyItemController;
use App\Http\Controllers\SupplyPickupController;
use App\Role;
use App\SupplyItem;
use App\SupplyPickup;
use App\User;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupplySearchAndReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('display_name');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        require_once database_path('migrations/2026_05_21_000001_create_supply_module_tables.php');
        require_once database_path('migrations/2026_09_10_000001_add_opname_fields_to_supply_items_table.php');
        (new \CreateSupplyModuleTables())->up();
        (new \AddOpnameFieldsToSupplyItemsTable())->up();
    }

    public function test_master_search_matches_name_and_codes_before_pagination()
    {
        $this->actingAs($this->user('operator_persediaan', 'Operator Uji'));
        for ($number = 1; $number <= 21; $number++) {
            SupplyItem::create(['name' => 'Kertas ' . $number]);
        }
        $wanted = SupplyItem::create(['name' => 'Pena', 'code' => 'ATK-42', 'account_code' => '521811']);

        foreach (['Pena', 'ATK-42', '521811'] as $search) {
            $data = app(SupplyItemController::class)->index(Request::create('/persediaan/barang', 'GET', compact('search')))->getData();
            $this->assertSame([$wanted->id], $data['items']->pluck('id')->all());
        }
    }

    public function test_report_includes_both_dates_and_all_matching_rows_without_expanding_access()
    {
        $operator = $this->user('operator_persediaan', 'Operator Uji');
        $employee = $this->user('pegawai', 'Pegawai Uji');
        $other = $this->user('pegawai', 'Pegawai Lain');
        $item = SupplyItem::create(['name' => 'Pena', 'code' => 'ATK-42']);
        $pickups = ['user_id' => $employee->id, 'supply_item_id' => $item->id, 'item_name_snapshot' => 'Pena biru'];
        for ($number = 1; $number <= 21; $number++) {
            SupplyPickup::create($pickups + ['pickup_date' => '2026-11-01']);
        }
        $last = SupplyPickup::create($pickups + ['pickup_date' => '2026-11-23']);
        SupplyPickup::create($pickups + ['pickup_date' => '2026-10-31']);
        SupplyPickup::create($pickups + ['pickup_date' => '2026-11-24']);
        $otherPickup = SupplyPickup::create(array_merge($pickups, ['user_id' => $other->id, 'pickup_date' => '2026-11-10']));
        SupplyPickup::create(['user_id' => $employee->id, 'item_name_snapshot' => 'Penggaris', 'pickup_date' => '2026-11-15']);

        $filters = ['from' => '2026-11-01', 'to' => '2026-11-23', 'search' => 'ATK-42'];
        $this->actingAs($employee);
        $view = app(SupplyPickupController::class)->index(Request::create(route('persediaan.pickups.index'), 'GET', $filters));
        $list = $view->getData()['pickups'];
        $this->assertSame(22, $list->total());
        $this->assertCount(20, $list);
        $this->assertStringContainsString('search=ATK-42', $list->url(2));

        $reportRoute = route('persediaan.pickups.report.pdf');
        $this->from(route('persediaan.pickups.index'))->get($reportRoute . '?from=2026-11-23&to=2026-11-01')
            ->assertSessionHasErrors('to');
        $this->from(route('persediaan.pickups.index'))->get($reportRoute . '?from=2026-11-01')
            ->assertSessionHasErrors('to');

        $this->withoutExceptionHandling();
        foreach ([[$employee, 22, false], [$operator, 23, true]] as [$user, $total, $includesOther]) {
            $this->actingAs($user);
            $pdf = PDF::getFacadeRoot();
            PDF::shouldReceive('loadView')->once()->withArgs(function ($name, $data) use ($pdf, $last, $otherPickup, $total, $includesOther) {
                $this->assertSame('persediaan.supplies.pickups.pdf', $name);
                $rows = $data['pickups'];
                $this->assertCount($total, $rows);
                $this->assertSame($last->id, $rows->first()->id);
                $this->assertSame('2026-11-01', $rows->last()->pickup_date->format('Y-m-d'));
                $this->assertSame($includesOther, $rows->contains('id', $otherPickup->id));
                return true;
            })->andReturnUsing(function ($name, $data) use ($pdf) {
                return $pdf->loadView($name, $data);
            });
            $response = $this->get(route('persediaan.pickups.report.pdf', $filters));
            $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
            $this->app->forgetInstance('dompdf.wrapper');
            PDF::clearResolvedInstance('dompdf.wrapper');
        }
    }

    private function user(string $role, string $name): User
    {
        $user = User::create(compact('name'));
        $user->setRelation('roles', collect([new Role(['name' => $role])]));
        $user->setRelation('activeJabatanDelegations', collect());

        return $user;
    }
}
