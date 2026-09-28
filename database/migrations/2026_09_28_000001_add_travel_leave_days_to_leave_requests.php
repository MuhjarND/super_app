<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTravelLeaveDaysToLeaveRequests extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('leave_requests') || Schema::hasColumn('leave_requests', 'travel_leave_days')) {
            return;
        }

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->unsignedInteger('travel_leave_days')->default(1)->after('travel_leave_requested');
        });
    }

    public function down()
    {
        if (Schema::hasTable('leave_requests') && Schema::hasColumn('leave_requests', 'travel_leave_days')) {
            Schema::table('leave_requests', function (Blueprint $table) {
                $table->dropColumn('travel_leave_days');
            });
        }
    }
}
