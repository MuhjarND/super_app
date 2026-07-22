<?php

namespace Tests\Unit;

use App\Disposisi;
use App\Jabatan;
use App\User;
use App\UserJabatanDelegation;
use PHPUnit\Framework\TestCase;

class SuratMasukDelegationTest extends TestCase
{
    public function test_plt_sees_original_recipient_and_delegated_action_context()
    {
        $jabatan = $this->jabatan(8, 'KASUBAG_TURT', 'Kepala Sub Bagian Tata Usaha dan Rumah Tangga');
        $original = $this->user(10, 'Pejabat Definitif', $jabatan);
        $delegate = $this->delegatedUser(20, 'Pelaksana Tugas', $jabatan, 'plt', [20, 10]);
        $jabatan->setRelation('users', collect([$original, $delegate]));

        $disposisi = new Disposisi([
            'kepada_user_id' => 10,
            'kepada_jabatan_id' => 8,
            'status' => 'pending',
        ]);
        $disposisi->setRelation('kepadaUser', $original);
        $disposisi->setRelation('kepadaJabatan', $jabatan);

        $context = $disposisi->assignmentContextFor($delegate);

        $this->assertSame('delegated', $context['mode']);
        $this->assertSame('PLT', $context['type']);
        $this->assertSame('Pejabat Definitif', $context['original_user_name']);
        $this->assertNull($context['description']);
        $this->assertStringContainsString('sebagai PLT', $context['action_label']);
        $this->assertTrue($delegate->canFollowUpDisposisi($disposisi));
    }

    public function test_direct_letter_is_not_mislabeled_as_delegated()
    {
        $ownJabatan = $this->jabatan(3, 'PEGAWAI', 'Pegawai');
        $delegatedJabatan = $this->jabatan(8, 'KASUBAG_TURT', 'Kepala Sub Bagian Tata Usaha dan Rumah Tangga');
        $user = $this->delegatedUser(20, 'Pelaksana Tugas', $delegatedJabatan, 'plh', [20]);
        $user->setRelation('jabatan', $ownJabatan);

        $disposisi = new Disposisi([
            'kepada_user_id' => 20,
            'kepada_jabatan_id' => 3,
            'status' => 'pending',
        ]);
        $disposisi->setRelation('kepadaUser', $user);
        $disposisi->setRelation('kepadaJabatan', $ownJabatan);

        $context = $disposisi->assignmentContextFor($user);

        $this->assertSame('direct', $context['mode']);
        $this->assertSame('Untuk Saya', $context['badge']);
    }

    public function test_delegate_cannot_follow_up_unrelated_recipient_without_matching_position()
    {
        $delegatedJabatan = $this->jabatan(8, 'KASUBAG_TURT', 'Kepala Sub Bagian Tata Usaha dan Rumah Tangga');
        $unrelatedJabatan = $this->jabatan(9, 'PANMUD_HUKUM', 'Panitera Muda Hukum');
        $delegate = $this->delegatedUser(20, 'Pelaksana Harian', $delegatedJabatan, 'plh', [20, 10]);

        $disposisi = new Disposisi([
            'kepada_user_id' => 99,
            'kepada_jabatan_id' => 9,
            'status' => 'pending',
        ]);
        $disposisi->setRelation('kepadaJabatan', $unrelatedJabatan);

        $this->assertFalse($delegate->canFollowUpDisposisi($disposisi));
    }

    public function test_active_plt_position_is_available_as_an_approval_option_label()
    {
        $jabatan = $this->jabatan(3, 'SEK', 'Sekretaris');
        $delegate = $this->delegatedUser(20, 'Pelaksana Tugas', $jabatan, 'plt', [20]);

        $this->assertSame(['PLT Sekretaris'], $delegate->activeDelegationLabels()->all());
    }

    protected function delegatedUser($id, $name, Jabatan $jabatan, $type, array $assignmentIds)
    {
        $user = new SuratMasukAssignmentTestUser();
        $user->forceFill(['id' => $id, 'name' => $name, 'status_aktif_pegawai' => true]);
        $user->assignmentIds = $assignmentIds;
        $user->setRelation('jabatan', null);
        $user->setRelation('roles', collect());

        $delegation = new UserJabatanDelegation([
            'user_id' => $id,
            'jabatan_id' => $jabatan->id,
            'delegation_type' => $type,
            'is_active' => true,
        ]);
        $delegation->setRelation('jabatan', $jabatan);
        $user->setRelation('activeJabatanDelegations', collect([$delegation]));

        return $user;
    }

    protected function user($id, $name, Jabatan $jabatan)
    {
        $user = new User();
        $user->forceFill([
            'id' => $id,
            'name' => $name,
            'jabatan_id' => $jabatan->id,
            'status_aktif_pegawai' => true,
        ]);
        $user->setRelation('jabatan', $jabatan);

        return $user;
    }

    protected function jabatan($id, $code, $name)
    {
        $jabatan = new Jabatan();
        $jabatan->forceFill(['id' => $id, 'kode' => $code, 'nama' => $name]);

        return $jabatan;
    }
}

class SuratMasukAssignmentTestUser extends User
{
    public $assignmentIds = [];

    public function suratMasukAssignmentUserIds()
    {
        return $this->assignmentIds;
    }
}
