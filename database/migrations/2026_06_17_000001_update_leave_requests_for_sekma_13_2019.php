<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLeaveRequestsForSekma132019 extends Migration
{
    public function up()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_requests', 'is_abroad')) {
                $table->boolean('is_abroad')->default(false)->after('leave_address');
            }
            if (!Schema::hasColumn('leave_requests', 'abroad_country')) {
                $table->string('abroad_country')->nullable()->after('is_abroad');
            }
        });
    }

    public function down()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('leave_requests', 'abroad_country')) {
                $table->dropColumn('abroad_country');
            }
            if (Schema::hasColumn('leave_requests', 'is_abroad')) {
                $table->dropColumn('is_abroad');
            }
        });
    }
}
