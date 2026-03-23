<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeaveContactFieldsToLeaveRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('leave_address')->nullable()->after('reason_detail');
            $table->string('contact_phone', 50)->nullable()->after('leave_address');
        });
    }

    public function down()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['leave_address', 'contact_phone']);
        });
    }
}
