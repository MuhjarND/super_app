<?php

namespace App\Listeners;

use App\Events\LeaveRequestSubmitted;

class CreateLeaveApprovalSteps
{
    public function handle(LeaveRequestSubmitted $event) { return true; }
}
