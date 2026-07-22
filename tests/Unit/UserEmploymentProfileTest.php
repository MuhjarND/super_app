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
