<?php

namespace Tests\Unit;

use App\SuratKeluar;
use Tests\TestCase;

class SuratKeluarSequenceTest extends TestCase
{
    public function test_outlier_local_numbers_do_not_override_latest_legacy_number()
    {
        $base = SuratKeluar::resolveContiguousSequenceBase(813, [1260, 1261, 1262]);

        $this->assertSame(813, $base);
        $this->assertSame(814, $base + 1);
    }

    public function test_contiguous_local_numbers_continue_after_latest_legacy_number()
    {
        $base = SuratKeluar::resolveContiguousSequenceBase(813, [814, 815, 1260]);

        $this->assertSame(815, $base);
        $this->assertSame(816, $base + 1);
    }

    public function test_secretary_and_registrar_use_pta_nomenclature()
    {
        $secretary = SuratKeluar::generateNomorSurat('sekretaris', 'KP', null, null, null, 2026, 8, 814);
        $registrar = SuratKeluar::generateNomorSurat('panitera', 'HK', null, null, null, 2026, 8, 815);

        $this->assertSame('814/SEK.PTA.W31-A/KP/VIII/2026', $secretary['nomor']);
        $this->assertSame('815/PAN.PTA.W31-A/HK/VIII/2026', $registrar['nomor']);
    }

    public function test_existing_sequence_number_is_reused_during_edit()
    {
        $letter = new SuratKeluar([
            'nomor_surat' => '814/SEK.PTA.W31-A/KP/VIII/2026',
            'nomor_urut' => 814,
        ]);

        $this->assertSame(814, $letter->reusableNomorUrut());

        $legacyLetter = new SuratKeluar([
            'nomor_surat' => '6086/SEK/SK.KP3.3/VI/2026',
        ]);

        $this->assertSame(6086, $legacyLetter->reusableNomorUrut());
    }

    public function test_legacy_secretary_and_registrar_numbers_are_displayed_with_pta_nomenclature()
    {
        $secretary = new SuratKeluar(['nomor_surat' => '6086/SEK/SK.KP3.3/VI/2026']);
        $registrar = new SuratKeluar(['nomor_surat' => '6087/PAN/HK1.1/VI/2026']);

        $this->assertSame('6086/SEK.PTA/SK.KP3.3/VI/2026', $secretary->nomor_surat_formatted);
        $this->assertSame('6087/PAN.PTA/HK1.1/VI/2026', $registrar->nomor_surat_formatted);
    }
}
