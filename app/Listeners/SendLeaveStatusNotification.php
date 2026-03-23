<?php

namespace App\Listeners;

use App\Events\LeaveRequestStatusChanged;
use App\Notifications\LeaveRequestStatusNotification;
use Illuminate\Support\Facades\Log;

class SendLeaveStatusNotification
{
    public function handle(LeaveRequestStatusChanged $event)
    {
        if ($event->leaveRequest->user) {
            try {
                $event->leaveRequest->user->notify(
                    new LeaveRequestStatusNotification(
                        $event->leaveRequest,
                        'Status pengajuan cuti berubah menjadi ' . $event->leaveRequest->status_label . '.'
                    )
                );
            } catch (\Throwable $e) {
                Log::warning('Leave status notification skipped', [
                    'leave_request_id' => $event->leaveRequest->id,
                    'user_id' => optional($event->leaveRequest->user)->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
