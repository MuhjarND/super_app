<?php

namespace Tests\Unit;

use App\Jabatan;
use App\SuratKeluar;
use App\User;
use App\UserJabatanDelegation;
use PHPUnit\Framework\TestCase;

class SuratKeluarPermissionTest extends TestCase
{
    /**
     * @dataProvider creatorPositionProvider
     */
    public function test_expected_positions_can_create_surat_keluar($code, $expected)
    {
        $user = $this->userWithPosition($code);

        $this->assertSame($expected, $user->canCreateSuratKeluar());
    }

    public function test_non_admin_creator_can_only_modify_own_surat()
    {
        $user = $this->userWithPosition('PANMUD_HUKUM');
        $user->forceFill(['id' => 15]);

        $this->assertTrue($user->canModifySuratKeluar(new SuratKeluar(['created_by' => 15])));
        $this->assertFalse($user->canModifySuratKeluar(new SuratKeluar(['created_by' => 99])));
    }

    public function test_active_plt_sekretaris_can_create_surat_keluar()
    {
        $user = $this->userWithPosition('STAF_KEPEGAWAIAN');
        $delegatedJabatan = $this->jabatan('SEK');
        $delegation = new UserJabatanDelegation();
        $delegation->setRelation('jabatan', $delegatedJabatan);
        $user->setRelation('activeJabatanDelegations', collect([$delegation]));

        $this->assertTrue($user->canCreateSuratKeluar());
        $this->assertFalse($user->canManageSuratKeluar());
    }

    public function creatorPositionProvider()
    {
        return [
            ['KASUBAG_KEPEG', true],
            ['KASUBAG_RENPRO', true],
            ['KASUBAG_LAPKEU', true],
            ['KASUBAG_TURT', true],
            ['PANMUD_BANDING', true],
            ['PANMUD_HUKUM', true],
            ['KABAG_UMUM', true],
            ['SEK', true],
            ['STAF_KEPEGAWAIAN', false],
        ];
    }

    protected function userWithPosition($code)
    {
        $user = new User();
        $user->setRelation('jabatan', $this->jabatan($code));
        $user->setRelation('roles', collect());
        $user->setRelation('activeJabatanDelegations', collect());

        return $user;
    }

    protected function jabatan($code)
    {
        $jabatan = new Jabatan();
        $jabatan->forceFill([
            'id' => crc32($code),
            'kode' => $code,
            'nama' => $code,
        ]);

        return $jabatan;
    }
}
