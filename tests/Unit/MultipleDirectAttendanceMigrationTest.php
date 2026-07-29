<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MultipleDirectAttendanceMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('rapats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_surat_keluar_id')->nullable();
            $table->unique('attendance_surat_keluar_id', 'rapat_attendance_surat_unique');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('rapats');

        parent::tearDown();
    }

    public function testMigrationAllowsMultipleAttendancesFromOneOutgoingLetter()
    {
        require_once database_path(
            'migrations/2026_07_29_000002_allow_multiple_direct_attendances_per_surat_keluar.php'
        );

        (new \AllowMultipleDirectAttendancesPerSuratKeluar())->up();

        DB::table('rapats')->insert(['attendance_surat_keluar_id' => 10]);
        DB::table('rapats')->insert(['attendance_surat_keluar_id' => 10]);

        $this->assertSame(2, DB::table('rapats')->count());
    }
}
