<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RapatAttendanceQrSchemaTest extends TestCase
{
    public function test_fresh_attendance_schema_allows_qr_attendance_without_signature_file()
    {
        $migration = file_get_contents(
            dirname(__DIR__, 2) . '/database/migrations/2026_03_16_000013_create_rapat_attendances_table.php'
        );

        $this->assertStringContainsString("string('signature_path')->nullable()", $migration);
    }

    public function test_upgrade_migration_makes_existing_mysql_column_nullable()
    {
        $migration = file_get_contents(
            dirname(__DIR__, 2) . '/database/migrations/2026_07_23_000001_make_rapat_attendance_signature_nullable.php'
        );

        $this->assertStringContainsString(
            'ALTER TABLE rapat_attendances MODIFY signature_path VARCHAR(255) NULL',
            $migration
        );
    }
}
