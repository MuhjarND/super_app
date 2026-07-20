<?php

namespace Tests\Unit;

use App\RapatNotulensiTindakLanjut;
use App\Jabatan;
use App\Role;
use App\User;
use PHPUnit\Framework\TestCase;

class MeetingFollowUpPermissionTest extends TestCase
{
    public function test_assigned_participant_can_manage_own_follow_up_without_meeting_role()
    {
        $user = $this->user(17, ['pegawai']);
        $followUp = new RapatNotulensiTindakLanjut(['user_id' => 17]);
        $user->setRelation('tindakLanjutNotulensiItems', collect([$followUp]));

        $this->assertFalse($user->canAccessMeetingModule());
        $this->assertTrue($user->canAccessMeetingFollowUps());
        $this->assertTrue($user->canManageMeetingFollowUp($followUp));
        $this->assertTrue($user->canViewMeetingFollowUp($followUp));
    }

    public function test_participant_cannot_manage_another_participants_follow_up()
    {
        $user = $this->user(17, ['pegawai']);
        $followUp = new RapatNotulensiTindakLanjut(['user_id' => 29]);

        $this->assertFalse($user->canManageMeetingFollowUp($followUp));
        $this->assertFalse($user->canViewMeetingFollowUp($followUp));
    }

    public function test_notulensi_manager_can_manage_all_meeting_follow_ups()
    {
        $user = $this->user(17, ['notulis']);
        $followUp = new RapatNotulensiTindakLanjut(['user_id' => 29]);

        $this->assertTrue($user->canManageMeetingFollowUp($followUp));
        $this->assertTrue($user->canViewMeetingFollowUp($followUp));
    }

    public function test_leadership_can_monitor_all_follow_ups_without_editing_them()
    {
        $user = $this->user(17, ['approval']);
        $jabatan = new Jabatan();
        $jabatan->forceFill(['id' => 1, 'kode' => 'KPTA', 'nama' => 'Ketua']);
        $user->setRelation('jabatan', $jabatan);
        $followUp = new RapatNotulensiTindakLanjut(['user_id' => 29]);

        $this->assertTrue($user->isPimpinan());
        $this->assertTrue($user->canMonitorAllMeetingFollowUps());
        $this->assertTrue($user->canAccessMeetingFollowUps());
        $this->assertTrue($user->canViewMeetingFollowUp($followUp));
        $this->assertFalse($user->canManageMeetingFollowUp($followUp));
    }

    protected function user($id, array $roles)
    {
        $user = new User();
        $user->id = $id;
        $user->setRelation('jabatan', null);
        $user->setRelation('activeJabatanDelegations', collect());
        $user->setRelation('roles', collect($roles)->map(function ($name) {
            $role = new Role();
            $role->forceFill(['name' => $name, 'display_name' => $name]);

            return $role;
        }));

        return $user;
    }
}
