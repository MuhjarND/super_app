<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeRapatAttendanceSignatureNullable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('rapat_attendances') || !Schema::hasColumn('rapat_attendances', 'signature_path')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE rapat_attendances MODIFY signature_path VARCHAR(255) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE rapat_attendances ALTER COLUMN signature_path DROP NOT NULL');
        } elseif ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE rapat_attendances ALTER COLUMN signature_path NVARCHAR(255) NULL');
        }
    }

    public function down()
    {
        if (!Schema::hasTable('rapat_attendances') || !Schema::hasColumn('rapat_attendances', 'signature_path')) {
            return;
        }

        DB::table('rapat_attendances')->whereNull('signature_path')->update(['signature_path' => '']);
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE rapat_attendances MODIFY signature_path VARCHAR(255) NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE rapat_attendances ALTER COLUMN signature_path SET NOT NULL');
        } elseif ($driver === 'sqlsrv') {
            DB::statement('ALTER TABLE rapat_attendances ALTER COLUMN signature_path NVARCHAR(255) NOT NULL');
        }
    }
}
