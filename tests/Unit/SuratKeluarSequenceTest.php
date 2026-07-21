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
}
