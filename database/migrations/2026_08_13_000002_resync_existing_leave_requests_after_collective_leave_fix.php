<?php

use App\Services\LeaveExistingRequestSyncService;
use Illuminate\Database\Migrations\Migration;

class ResyncExistingLeaveRequestsAfterCollectiveLeaveFix extends Migration
{
    public function up()
    {
        app(LeaveExistingRequestSyncService::class)->syncAll();
    }

    public function down()
    {
        // Sinkronisasi ini memperbaiki data lama dan tidak perlu dibatalkan.
    }
}
