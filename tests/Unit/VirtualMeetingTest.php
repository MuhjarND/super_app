<?php

namespace Tests\Unit;

use App\Role;
use App\User;
use App\VirtualMeeting;
use Carbon\Carbon;
use Tests\TestCase;

class VirtualMeetingTest extends TestCase
{
    public function test_virtual_meeting_exposes_formatted_schedule()
    {
        Carbon::setLocale('id');

        $meeting = new VirtualMeeting([
            'tanggal_kegiatan' => '2026-07-20',
            'waktu_mulai' => '09:00:00',
            'waktu_selesai' => '11:30:00',
        ]);

        $this->assertSame('09:00', $meeting->waktu_mulai_formatted);
        $this->assertSame('11:30', $meeting->waktu_selesai_formatted);
        $this->assertStringContainsString('09:00 WIT - 11:30 WIT', $meeting->jadwal_formatted);
    }

    /**
     * @dataProvider managerRoleProvider
     */
    public function test_expected_meeting_roles_can_manage_virtual_meetings($roleName, $expected)
    {
        $user = new User();
        $user->setRelation('jabatan', null);
        $user->setRelation('activeJabatanDelegations', collect());
        $user->setRelation('roles', collect([$this->role($roleName)]));

        $this->assertSame($expected, $user->canManageVirtualMeetings());
    }

    public function managerRoleProvider()
    {
        return [
            ['super_admin', true],
            ['admin', true],
            ['operator', true],
            ['protokoler', true],
            ['peserta', false],
            ['pegawai', false],
        ];
    }

    protected function role($name)
    {
        $role = new Role();
        $role->forceFill(['name' => $name, 'display_name' => $name]);

        return $role;
    }
}
