<?php

namespace Tests\Unit;

use App\AgendaPimpinan;
use App\Role;
use App\User;
use App\VirtualMeeting;
use Tests\TestCase;

class MeetingAgendaAndVotingAccessTest extends TestCase
{
    public function test_tagged_user_can_access_agenda_without_management_permissions()
    {
        $user = $this->userWithRole('pegawai');
        $user->setRelation('agendaPimpinans', collect([new AgendaPimpinan()]));

        $this->assertTrue($user->canAccessAgendaPimpinan());
        $this->assertFalse($user->canManageAgendaPimpinanDetails());
        $this->assertFalse($user->canManageAgendaPimpinanParticipants());
    }

    public function test_untagged_user_cannot_access_agenda()
    {
        $user = $this->userWithRole('pegawai');
        $user->setRelation('agendaPimpinans', collect());

        $this->assertFalse($user->canAccessAgendaPimpinan());
    }

    public function test_tagged_user_can_access_virtual_meeting_without_management_permissions()
    {
        $user = $this->userWithRole('pegawai');
        $user->setRelation('virtualMeetings', collect([new VirtualMeeting()]));

        $this->assertTrue($user->canAccessVirtualMeetings());
        $this->assertFalse($user->canManageVirtualMeetings());
    }

    public function test_every_authenticated_user_can_access_voting_but_only_manager_can_manage_it()
    {
        $employee = $this->userWithRole('pegawai');
        $manager = $this->userWithRole('admin');

        $this->assertTrue($employee->canAccessVoting());
        $this->assertFalse($employee->canManageVoting());
        $this->assertTrue($manager->canAccessVoting());
        $this->assertTrue($manager->canManageVoting());
    }

    protected function userWithRole($roleName)
    {
        $user = new User();
        $user->setRelation('jabatan', null);
        $user->setRelation('activeJabatanDelegations', collect());
        $user->setRelation('roles', collect([$this->role($roleName)]));

        return $user;
    }

    protected function role($name)
    {
        $role = new Role();
        $role->forceFill(['name' => $name, 'display_name' => $name]);

        return $role;
    }
}
