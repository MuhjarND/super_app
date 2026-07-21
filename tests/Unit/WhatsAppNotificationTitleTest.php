<?php

namespace Tests\Unit;

use App\Services\WhatsAppMagicLinkService;
use App\Services\WhatsAppNotificationService;
use ReflectionMethod;
use Tests\TestCase;

class WhatsAppNotificationTitleTest extends TestCase
{
    /** @dataProvider moduleTitles */
    public function test_notification_header_matches_its_module($module, $expectedTitle)
    {
        $service = new WhatsAppNotificationService($this->createMock(WhatsAppMagicLinkService::class));
        $method = new ReflectionMethod($service, 'withNotificationHeader');
        $method->setAccessible(true);

        $message = $method->invoke($service, 'Pesan pengujian.', ['module' => $module]);

        $this->assertStringStartsWith('*PAPEDA | ' . $expectedTitle . '*', $message);
        $this->assertStringNotContainsString('SIMANTAP', $message);
    }

    public function moduleTitles()
    {
        return [
            ['security', 'KEAMANAN AKUN'],
            ['persuratan', 'PERSURATAN'],
            ['surat_tugas', 'SURAT TUGAS'],
            ['cuti', 'CUTI'],
            ['progress_zi', 'PROGRESS ZI'],
            ['rapat', 'RAPAT'],
            ['agenda_pimpinan', 'AGENDA PIMPINAN'],
            ['virtual_meeting', 'VIRTUAL MEETING'],
            ['voting', 'E-VOTING'],
            ['persediaan', 'PERSEDIAAN'],
            ['perawatan', 'PERAWATAN ALAT DAN MESIN'],
            ['master_data', 'AKUN PENGGUNA'],
            ['general', 'UMUM'],
        ];
    }

    public function test_queued_legacy_message_is_normalized_before_delivery()
    {
        $service = new WhatsAppNotificationService($this->createMock(WhatsAppMagicLinkService::class));
        $method = new ReflectionMethod($service, 'normalizeNotificationBrandAndTitle');
        $method->setAccessible(true);

        $message = $method->invoke(
            $service,
            "*SIMANTAP | UMUM*\n*Buka di SIMANTAP (login otomatis):*\nhttps://example.test",
            'rapat'
        );

        $this->assertStringStartsWith('*PAPEDA | RAPAT*', $message);
        $this->assertStringContainsString('*Buka di PAPEDA (login otomatis):*', $message);
        $this->assertStringNotContainsString('SIMANTAP', $message);
    }
}
