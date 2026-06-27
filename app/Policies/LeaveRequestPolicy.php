<?php

namespace App\Policies;

use App\LeaveRequest;
use App\User;

class LeaveRequestPolicy
{
    public function view(User $user, LeaveRequest $leaveRequest) { return $user->isSuperAdmin() || (int) $leaveRequest->user_id === (int) $user->id || $user->canApproveLeave(); }
    public function update(User $user, LeaveRequest $leaveRequest) { return ((int) $leaveRequest->user_id === (int) $user->id) && in_array($leaveRequest->status, [LeaveRequest::STATUS_DRAFT, LeaveRequest::STATUS_REJECTED, LeaveRequest::STATUS_CHANGED, LeaveRequest::STATUS_DEFERRED], true) && !$leaveRequest->isLocked(); }
    public function submit(User $user, LeaveRequest $leaveRequest) { return $this->update($user, $leaveRequest); }
    public function cancel(User $user, LeaveRequest $leaveRequest) { return $user->isSuperAdmin() || (((int) $leaveRequest->user_id === (int) $user->id) && in_array($leaveRequest->status, [LeaveRequest::STATUS_DRAFT, LeaveRequest::STATUS_SUBMITTED, LeaveRequest::STATUS_UNDER_REVIEW, LeaveRequest::STATUS_VERIFIED], true) && !$leaveRequest->isLocked()); }
}
