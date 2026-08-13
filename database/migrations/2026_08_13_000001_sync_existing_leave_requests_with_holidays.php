<?php

use App\Services\LeaveExistingRequestSyncService;
use Illuminate\Database\Migrations\Migration;

class SyncExistingLeaveRequestsWithHolidays extends Migration
{
    public function up()
    {
        app(LeaveExistingRequestSyncService::class)->syncAll();
    }

    public function down()
    {
        // Nilai lama tidak dikembalikan karena sudah digantikan oleh hitungan tanggal efektif yang benar.
    }
}
