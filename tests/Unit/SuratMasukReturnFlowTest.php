<?php

namespace Tests\Unit;

use App\Disposisi;
use App\Jabatan;
use App\SuratMasuk;
use App\User;
use PHPUnit\Framework\TestCase;

class SuratMasukReturnFlowTest extends TestCase
{
    public function test_pending_disposition_returned_to_kasubag_turt_only_allows_follow_up()
    {
        $surat = $this->suratWithDisposition('KASUBAG_TURT', 'pending');

        $this->assertTrue($surat->isAwaitingKasubagTurtFollowUp());
        $this->assertFalse((new User())->canForwardSuratMasuk($surat));
    }

    public function test_completed_disposition_to_kasubag_turt_does_not_lock_the_letter()
    {
        $surat = $this->suratWithDisposition('KASUBAG_TURT', 'ditindaklanjuti');

        $this->assertFalse($surat->isAwaitingKasubagTurtFollowUp());
    }

    public function test_pending_disposition_to_another_position_is_not_treated_as_returned_to_turt()
    {
        $surat = $this->suratWithDisposition('PANMUD_HUKUM', 'pending');

        $this->assertFalse($surat->isAwaitingKasubagTurtFollowUp());
    }

    protected function suratWithDisposition($jabatanKode, $status)
    {
        $jabatan = new Jabatan();
        $jabatan->forceFill(['id' => 8, 'kode' => $jabatanKode, 'nama' => $jabatanKode]);

        $disposisi = new Disposisi([
            'kepada_jabatan_id' => 8,
            'status' => $status,
        ]);
        $disposisi->setRelation('kepadaJabatan', $jabatan);
        $disposisi->setRelation('kepadaUser', null);

        $surat = new SuratMasuk(['status' => 'didisposisi']);
        $surat->setRelation('disposisis', collect([$disposisi]));

        return $surat;
    }
}
