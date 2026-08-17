<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StopRemainingApprovalsForRejectedLeaveRequests extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('leave_requests') || !Schema::hasTable('leave_approvals')) {
            return;
        }

        DB::table('leave_requests')
            ->where('status', 'rejected')
            ->orderBy('id')
            ->chunkById(100, function ($requests) {
                foreach ($requests as $request) {
                    $rejectedApproval = DB::table('leave_approvals')
                        ->where('leave_request_id', $request->id)
                        ->where('status', 'rejected')
                        ->orderBy('step_no')
                        ->first();

                    if (!$rejectedApproval) {
                        continue;
                    }

                    $stoppedAt = $rejectedApproval->acted_at ?: ($request->rejected_at ?: now());
                    DB::table('leave_approvals')
                        ->where('leave_request_id', $request->id)
                        ->where('step_no', '>', $rejectedApproval->step_no)
                        ->whereIn('status', ['waiting', 'pending'])
                        ->update([
                            'status' => 'cancelled',
                            'action' => 'stopped_after_rejection',
                            'acted_at' => $stoppedAt,
                            'note' => 'Alur dihentikan karena pengajuan ditolak pada tahap sebelumnya.',
                            'updated_at' => now(),
                        ]);

                    if (!$request->locked_at) {
                        DB::table('leave_requests')->where('id', $request->id)->update([
                            'locked_at' => $stoppedAt,
                            'updated_at' => now(),
                        ]);
                    }
                }
            });
    }

    public function down()
    {
        // Riwayat penolakan tidak dibuka kembali agar approval yang sudah berhenti
        // tidak aktif tanpa keputusan pejabat.
    }
}
