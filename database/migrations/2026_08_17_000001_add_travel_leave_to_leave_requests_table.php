<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTravelLeaveToLeaveRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_requests', 'travel_leave_requested')) {
                $table->boolean('travel_leave_requested')->default(false)->after('abroad_country');
            }
            if (!Schema::hasColumn('leave_requests', 'travel_leave_granted')) {
                $table->boolean('travel_leave_granted')->default(false)->after('travel_leave_requested');
            }
        });
    }

    public function down()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('leave_requests', 'travel_leave_granted')) {
                $table->dropColumn('travel_leave_granted');
            }
            if (Schema::hasColumn('leave_requests', 'travel_leave_requested')) {
                $table->dropColumn('travel_leave_requested');
            }
        });
    }
}
