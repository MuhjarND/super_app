<?php

namespace App\Notifications;

use App\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class LeaveWorkflowNotification extends Notification
{
    use Queueable;

    protected $leaveRequest;
    protected $title;
    protected $message;
    protected $actionUrl;
    protected $status;
    protected $audience;

    public function __construct(LeaveRequest $leaveRequest, $title, $message, $actionUrl = null, $status = null, $audience = 'requester')
    {
        $this->leaveRequest = $leaveRequest;
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->status = $status ?: $leaveRequest->status;
        $this->audience = $audience;
    }

    public function via($notifiable)
    {
        $channels = [];

        if (Schema::hasTable('notifications')) {
            $channels[] = 'database';
        }

        return $channels;
    }

    public function toArray($notifiable)
    {
        return [
            'module' => 'cuti',
            'leave_request_id' => $this->leaveRequest->id,
            'request_number' => $this->leaveRequest->request_number,
            'letter_number' => $this->leaveRequest->letter_number,
            'title' => $this->title,
            'message' => $this->message,
            'status' => $this->status,
            'status_label' => $this->leaveRequest->status_label,
            'action_url' => $this->actionUrl,
            'audience' => $this->audience,
            'leave_type' => optional($this->leaveRequest->leaveType)->name,
            'period' => $this->leaveRequest->period_label,
        ];
    }
}
