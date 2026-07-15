<?php

namespace Tests\Unit;

use App\Jabatan;
use App\Role;
use App\User;
use PHPUnit\Framework\TestCase;

class InventoryPermissionTest extends TestCase
{
    /**
     * @dataProvider positionProvider
     */
    public function test_only_expected_positions_can_manage_inventory($code, $expected)
    {
        $user = $this->userWithPosition($code);

        $this->assertSame($expected, $user->canManageInventoryModule());
        $this->assertSame($expected, $user->canManageInventoryMasterData());
        $this->assertSame($expected, $user->canManageInventoryTransactions());
    }

    public function test_operator_siperlatin_can_manage_inventory()
    {
        $user = new User();
        $user->setRelation('jabatan', null);
        $user->setRelation('activeJabatanDelegations', collect());
        $user->setRelation('roles', collect([$this->role('operator_siperlatin')]));

        $this->assertTrue($user->canAccessInventoryModule());
        $this->assertTrue($user->canManageInventoryModule());
        $this->assertTrue($user->canScheduleInventoryMaintenance());
    }

    public function test_regular_employee_has_monitoring_access_only()
    {
        $user = new User();
        $user->setRelation('jabatan', null);
        $user->setRelation('activeJabatanDelegations', collect());
        $user->setRelation('roles', collect([$this->role('pegawai')]));

        $this->assertTrue($user->canAccessInventoryModule());
        $this->assertFalse($user->canManageInventoryModule());
        $this->assertFalse($user->canScheduleInventoryMaintenance());
    }

    public function test_inventory_managing_head_cannot_change_maintenance_schedule()
    {
        $turt = $this->userWithPosition('KASUBAG_TURT');
        $finance = $this->userWithPosition('KASUBAG_LAPKEU');

        $this->assertTrue($turt->canManageInventoryModule());
        $this->assertFalse($turt->canScheduleInventoryMaintenance());
        $this->assertTrue($finance->canManageInventoryModule());
        $this->assertFalse($finance->canScheduleInventoryMaintenance());
    }

    public function positionProvider()
    {
        return [
            ['KASUBAG_TURT', true],
            ['KASUBAG_LAPKEU', true],
            ['KASUBAG_RENPRO', false],
            ['KASUBAG_KEPEG', false],
            ['PANMUD_HUKUM', false],
            ['STAF_KEUANGAN', false],
        ];
    }

    protected function userWithPosition($code)
    {
        $jabatan = new Jabatan();
        $jabatan->forceFill(['id' => crc32($code), 'kode' => $code, 'nama' => $code]);

        $user = new User();
        $user->setRelation('jabatan', $jabatan);
        $user->setRelation('activeJabatanDelegations', collect());
        $user->setRelation('roles', collect());

        return $user;
    }

    protected function role($name)
    {
        $role = new Role();
        $role->forceFill(['name' => $name, 'display_name' => $name]);

        return $role;
    }
}
