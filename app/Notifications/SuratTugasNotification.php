<?php

namespace App\Notifications;

use App\SuratKeluar;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class SuratTugasNotification extends Notification
{
    use Queueable;

    protected $suratKeluar;
    protected $title;
    protected $message;
    protected $actionUrl;
    protected $audience;

    public function __construct(SuratKeluar $suratKeluar, $title, $message, $actionUrl = null, $audience = 'recipient')
    {
        $this->suratKeluar = $suratKeluar;
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
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
            'module' => 'surat_tugas',
            'surat_keluar_id' => $this->suratKeluar->id,
            'nomor_surat' => $this->suratKeluar->nomor_surat_formatted,
            'perihal' => $this->suratKeluar->perihal,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'audience' => $this->audience,
        ];
    }
}
