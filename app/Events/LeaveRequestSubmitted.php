<?php

namespace App\Events;

use App\LeaveRequest;
use App\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeaveRequestSubmitted
{
    use Dispatchable, SerializesModels;
    public $leaveRequest; public $actor;
    public function __construct(LeaveRequest $leaveRequest, User $actor) { $this->leaveRequest = $leaveRequest; $this->actor = $actor; }
}
