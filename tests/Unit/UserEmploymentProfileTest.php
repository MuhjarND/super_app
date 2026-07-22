<?php

namespace Tests\Unit;

use App\User;
use Carbon\Carbon;
use Tests\TestCase;

class UserEmploymentProfileTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_masa_kerja_is_calculated_until_current_date()
    {
        Carbon::setTestNow(Carbon::parse('2026-07-22'));
        $user = new User(['tmt_pns' => '2020-05-10']);

        $this->assertSame('6 tahun 2 bulan', $user->masa_kerja);
    }

    public function test_direct_year_and_month_input_grows_from_reference_date()
    {
        Carbon::setTestNow(Carbon::parse('2027-03-15'));
        $user = new User([
            'masa_kerja_tahun' => 5,
            'masa_kerja_bulan' => 10,
            'masa_kerja_acuan' => '2026-01-15',
        ]);

        $this->assertSame(84, $user->masa_kerja_total_bulan);
        $this->assertSame(7, $user->masa_kerja_tahun_berjalan);
        $this->assertSame(0, $user->masa_kerja_bulan_berjalan);
        $this->assertSame('7 tahun', $user->masa_kerja);
    }

    public function test_direct_input_only_adds_complete_elapsed_months()
    {
        Carbon::setTestNow(Carbon::parse('2026-08-21'));
        $user = new User([
            'masa_kerja_tahun' => 6,
            'masa_kerja_bulan' => 2,
            'masa_kerja_acuan' => '2026-07-22',
        ]);

        $this->assertSame('6 tahun 2 bulan', $user->masa_kerja);

        Carbon::setTestNow(Carbon::parse('2026-08-22'));

        $this->assertSame('6 tahun 3 bulan', $user->masa_kerja);
    }

    public function test_masa_kerja_returns_zero_month_for_current_month()
    {
        Carbon::setTestNow(Carbon::parse('2026-07-22'));
        $user = new User(['tmt_pns' => '2026-07-01']);

        $this->assertSame('0 bulan', $user->masa_kerja);
    }

    public function test_masa_kerja_is_null_without_start_date()
    {
        $this->assertNull((new User())->masa_kerja);
    }
}
