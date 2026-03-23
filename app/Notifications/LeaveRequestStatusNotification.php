<?php

namespace App\Notifications;

use App\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class LeaveRequestStatusNotification extends Notification
{
    use Queueable;
    protected $leaveRequest;
    protected $message;
    public function __construct(LeaveRequest $leaveRequest, $message) { $this->leaveRequest = $leaveRequest; $this->message = $message; }
    public function via($notifiable)
    {
        $channels = [];

        if (Schema::hasTable('notifications')) {
            $channels[] = 'database';
        }

        return $channels;
    }
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Informasi Pengajuan Cuti')
            ->greeting('Yth. ' . $notifiable->name)
            ->line($this->message)
            ->line('Jenis cuti: ' . optional($this->leaveRequest->leaveType)->name)
            ->line('Periode: ' . optional($this->leaveRequest->start_date)->format('d/m/Y') . ' s.d. ' . optional($this->leaveRequest->end_date)->format('d/m/Y'));
    }
    public function toArray($notifiable) { return ['leave_request_id' => $this->leaveRequest->id, 'message' => $this->message, 'status' => $this->leaveRequest->status]; }
}
