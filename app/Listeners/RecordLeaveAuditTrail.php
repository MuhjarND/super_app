<?php

namespace App\Listeners;

use App\Events\LeaveRequestStatusChanged;
use App\Events\LeaveRequestSubmitted;
use App\LeaveAuditTrail;

class RecordLeaveAuditTrail
{
    public function handle($event)
    {
        if ($event instanceof LeaveRequestSubmitted) {
            $this->createAudit($event->leaveRequest->id, $event->actor->id, 'submitted', null, ['status' => $event->leaveRequest->status], 'Pengajuan cuti disubmit.');
        }
        if ($event instanceof LeaveRequestStatusChanged) {
            $this->createAudit($event->leaveRequest->id, $event->actor->id, $event->eventName, ['status' => $event->oldStatus], ['status' => $event->newStatus], null);
        }
    }
    protected function createAudit($leaveRequestId, $actorId, $event, $oldValues, $newValues, $note)
    {
        LeaveAuditTrail::create(['leave_request_id' => $leaveRequestId, 'actor_id' => $actorId, 'event' => $event, 'old_values_json' => $oldValues, 'new_values_json' => $newValues, 'note' => $note, 'ip_address' => request() ? request()->ip() : null, 'user_agent' => request() ? request()->userAgent() : null, 'created_at' => now()]);
    }
}
