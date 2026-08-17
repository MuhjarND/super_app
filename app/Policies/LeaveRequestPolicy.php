<?php

namespace App\Policies;

use App\LeaveRequest;
use App\User;

class LeaveRequestPolicy
{
    public function view(User $user, LeaveRequest $leaveRequest)
    {
        $leaveRequest->loadMissing('user');

        return $user->isSuperAdmin()
            || (int) $leaveRequest->user_id === (int) $user->id
            || $user->isDirectSupervisorOf($leaveRequest->user)
            || $user->canApproveLeave();
    }

    public function update(User $user, LeaveRequest $leaveRequest) { return ((int) $leaveRequest->user_id === (int) $user->id) && in_array($leaveRequest->status, [LeaveRequest::STATUS_DRAFT, LeaveRequest::STATUS_CHANGED, LeaveRequest::STATUS_DEFERRED], true) && !$leaveRequest->isLocked(); }
    public function submit(User $user, LeaveRequest $leaveRequest) { return $this->update($user, $leaveRequest); }
    public function cancel(User $user, LeaveRequest $leaveRequest) { return $user->isSuperAdmin() || (((int) $leaveRequest->user_id === (int) $user->id) && in_array($leaveRequest->status, [LeaveRequest::STATUS_DRAFT, LeaveRequest::STATUS_SUBMITTED, LeaveRequest::STATUS_UNDER_REVIEW, LeaveRequest::STATUS_VERIFIED], true) && !$leaveRequest->isLocked()); }
}
