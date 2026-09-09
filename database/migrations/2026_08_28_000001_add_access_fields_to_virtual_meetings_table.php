<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAccessFieldsToVirtualMeetingsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('virtual_meetings')) {
            return;
        }

        Schema::table('virtual_meetings', function (Blueprint $table) {
            if (!Schema::hasColumn('virtual_meetings', 'meeting_id')) {
                $table->string('meeting_id')->nullable()->after('zoom_link');
            }

            if (!Schema::hasColumn('virtual_meetings', 'meeting_passcode')) {
                $table->string('meeting_passcode')->nullable()->after('meeting_id');
            }
        });

        $this->setZoomLinkNullable(true);
    }

    public function down()
    {
        if (!Schema::hasTable('virtual_meetings')) {
            return;
        }

        DB::table('virtual_meetings')->whereNull('zoom_link')->update(['zoom_link' => '']);
        $this->setZoomLinkNullable(false);

        Schema::table('virtual_meetings', function (Blueprint $table) {
            $columns = array_values(array_filter(['meeting_id', 'meeting_passcode'], function ($column) {
                return Schema::hasColumn('virtual_meetings', $column);
            }));

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }

    protected function setZoomLinkNullable($nullable)
    {
        if (!Schema::hasColumn('virtual_meetings', 'zoom_link')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        $nullSql = $nullable ? 'NULL' : 'NOT NULL';

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE virtual_meetings MODIFY zoom_link TEXT {$nullSql}");
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE virtual_meetings ALTER COLUMN zoom_link ' . ($nullable ? 'DROP NOT NULL' : 'SET NOT NULL'));
        } elseif ($driver === 'sqlsrv') {
            DB::statement("ALTER TABLE virtual_meetings ALTER COLUMN zoom_link NVARCHAR(MAX) {$nullSql}");
        }
    }
}
