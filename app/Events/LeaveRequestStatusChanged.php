<?php

namespace App\Events;

use App\LeaveRequest;
use App\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveRequestStatusChanged
{
    use Dispatchable, SerializesModels;
    public $leaveRequest; public $actor; public $oldStatus; public $newStatus; public $eventName;
    public function __construct(LeaveRequest $leaveRequest, User $actor, $oldStatus, $newStatus, $eventName)
    { $this->leaveRequest = $leaveRequest; $this->actor = $actor; $this->oldStatus = $oldStatus; $this->newStatus = $newStatus; $this->eventName = $eventName; }
}
