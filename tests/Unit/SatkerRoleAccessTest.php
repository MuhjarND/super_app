<?php

namespace Tests\Unit;

use App\Role;
use App\User;
use PHPUnit\Framework\TestCase;

class SatkerRoleAccessTest extends TestCase
{
    public function test_satker_has_read_only_meeting_access()
    {
        $user = $this->satkerUser();

        $this->assertTrue($user->canAccessMeetingModule());
        $this->assertTrue($user->canAccessMeetingMinutes());
        $this->assertFalse($user->canManageRapat());
        $this->assertFalse($user->canManageMeetingMinutes());
        $this->assertFalse($user->canAccessMeetingReports());
        $this->assertFalse($user->canAccessMeetingFollowUps());
        $this->assertFalse($user->canAccessMeetingApproval());
        $this->assertFalse($user->canAccessVoting());
    }

    public function test_satker_can_submit_leave_without_accessing_leave_management()
    {
        $user = $this->satkerUser();

        $this->assertTrue($user->canAccessLeaveModule());
        $this->assertFalse($user->canAccessLeaveApproval());
        $this->assertFalse($user->canAccessLeaveBalanceReport());
        $this->assertFalse($user->canAccessLeaveReports());
        $this->assertFalse($user->canManageLeaveMasterData());
    }

    protected function satkerUser()
    {
        $role = new Role();
        $role->forceFill(['name' => 'satker', 'display_name' => 'Satuan Kerja']);

        $user = new User();
        $user->setRelation('jabatan', null);
        $user->setRelation('activeJabatanDelegations', collect());
        $user->setRelation('roles', collect([$role]));

        return $user;
    }
}
