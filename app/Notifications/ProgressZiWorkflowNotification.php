<?php

namespace App\Notifications;

use App\ZiActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class ProgressZiWorkflowNotification extends Notification
{
    use Queueable;

    protected $activity;
    protected $title;
    protected $message;
    protected $actionUrl;
    protected $audience;
    protected $status;

    public function __construct(ZiActivity $activity, $title, $message, $actionUrl = null, $audience = 'pic', $status = null)
    {
        $this->activity = $activity;
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->audience = $audience;
        $this->status = $status ?: $activity->status;
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
            'module' => 'progress_zi',
            'zi_activity_id' => $this->activity->id,
            'title' => $this->title,
            'message' => $this->message,
            'status' => $this->status,
            'status_label' => $this->activity->status_label,
            'action_url' => $this->actionUrl,
            'audience' => $this->audience,
            'activity_name' => $this->activity->name,
            'area_name' => optional($this->activity->area)->name,
        ];
    }
}
