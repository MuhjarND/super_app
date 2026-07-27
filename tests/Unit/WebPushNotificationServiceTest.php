<?php

namespace Tests\Unit;

use App\Services\WebPushNotificationService;
use Tests\TestCase;

class WebPushNotificationServiceTest extends TestCase
{
    public function test_it_builds_a_concise_module_notification_with_the_action_url()
    {
        $message = implode("\n", [
            '*PAPEDA | RAPAT*',
            'Yth. Bapak/Ibu,',
            'Dengan hormat, rapat telah disetujui.',
            'Agenda: Monitoring Kinerja',
            'Waktu: 27 Juli 2026 10:00 WIT',
            'Silakan buka undangan:',
            'https://papeda.pta-papuabarat.go.id/masuk/whatsapp/token',
        ]);

        $payload = (new WebPushNotificationService())->payloadFromWhatsAppMessage($message, [
            'module' => 'rapat',
            'event' => 'approved',
            'notifiable_id' => 25,
        ]);

        $this->assertSame('Rapat dan Agenda', $payload['title']);
        $this->assertSame('https://papeda.pta-papuabarat.go.id/masuk/whatsapp/token', $payload['url']);
        $this->assertStringContainsString('Agenda: Monitoring Kinerja', $payload['body']);
        $this->assertStringNotContainsString('https://', $payload['body']);
        $this->assertSame('papeda-rapat-approved-25', $payload['tag']);
    }
}
