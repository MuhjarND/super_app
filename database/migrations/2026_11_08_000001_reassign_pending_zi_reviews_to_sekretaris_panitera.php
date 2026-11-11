<?php

use App\Services\ProgressZi\ZiActivityApprovalService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class ReassignPendingZiReviewsToSekretarisPanitera extends Migration
{
    public function up()
    {
        if (Schema::hasTable('zi_activity_approvals')) {
            app(ZiActivityApprovalService::class)->reassignPendingReviews();
        }
    }

    public function down()
    {
        // Data keputusan dan reviewer historis tidak diubah saat rollback.
    }
}
