<?php

namespace Tests\Unit;

use App\AgendaPimpinan;
use App\Role;
use App\SuratMasuk;
use App\User;
use App\VirtualMeeting;
use Tests\TestCase;

class SuratMasukAgendaAccessTest extends TestCase
{
    public function test_agenda_pimpinan_recipient_can_view_originating_incoming_letter()
    {
        $user = $this->employee(21);
        $agenda = new AgendaPimpinan();
        $agenda->setRelation('recipients', collect([$user]));
        $surat = $this->suratWithRelations($agenda, null);

        $this->assertTrue($user->canViewSuratMasuk($surat));
    }

    public function test_protokoler_can_view_incoming_letter_linked_to_agenda_pimpinan()
    {
        $user = $this->employee(22, 'protokoler');
        $agenda = new AgendaPimpinan();
        $agenda->setRelation('recipients', collect());
        $surat = $this->suratWithRelations($agenda, null);

        $this->assertTrue($user->canViewSuratMasuk($surat));
    }

    public function test_virtual_meeting_participant_can_view_originating_incoming_letter()
    {
        $user = $this->employee(23);
        $meeting = new VirtualMeeting();
        $meeting->setRelation('participants', collect([$user]));
        $surat = $this->suratWithRelations(null, $meeting);

        $this->assertTrue($user->canViewSuratMasuk($surat));
    }

    protected function suratWithRelations($agenda, $meeting)
    {
        $surat = new SuratMasuk(['created_by' => 999]);
        $surat->setRelation('agendaPimpinan', $agenda);
        $surat->setRelation('virtualMeeting', $meeting);

        return $surat;
    }

    protected function employee($id, $roleName = 'pegawai')
    {
        $user = new User();
        $user->forceFill(['id' => $id, 'status_aktif_pegawai' => true]);
        $user->setRelation('jabatan', null);
        $user->setRelation('activeJabatanDelegations', collect());

        $role = new Role();
        $role->forceFill(['name' => $roleName, 'display_name' => $roleName]);
        $user->setRelation('roles', collect([$role]));

        return $user;
    }
}
