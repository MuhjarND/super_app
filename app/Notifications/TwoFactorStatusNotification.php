<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class TwoFactorStatusNotification extends Notification
{
    use Queueable;

    protected $title;
    protected $message;
    protected $actionUrl;
    protected $status;
    protected $recoveryCodeCount;

    public function __construct($title, $message, $actionUrl = null, $status = 'enabled', $recoveryCodeCount = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
        $this->status = $status;
        $this->recoveryCodeCount = $recoveryCodeCount;
    }

    public function via($notifiable)
    {
        $channels = [];

        if (Schema::hasTable('notifications')) {
            $channels[] = 'database';
        }

        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject($this->title . ' - PTA Papua Barat')
            ->greeting('Yth. Bapak/Ibu ' . $notifiable->name . ',')
            ->line($this->message);

        if ($this->recoveryCodeCount) {
            $mail->line('Jumlah backup recovery code aktif: ' . $this->recoveryCodeCount . ' kode.');
        }

        if ($this->actionUrl) {
            $mail->action('Buka Pengaturan Keamanan', $this->actionUrl);
        }

        return $mail->line('Apabila perubahan ini bukan dilakukan oleh Anda, mohon segera ubah password akun dan hubungi administrator.');
    }

    public function toArray($notifiable)
    {
        return [
            'module' => 'security',
            'category' => 'two_factor',
            'title' => $this->title,
            'message' => $this->message,
            'status' => $this->status,
            'recovery_code_count' => $this->recoveryCodeCount,
            'action_url' => $this->actionUrl,
        ];
    }
}
