<?php

namespace Tests\Unit;

use App\Services\DocumentShareLinkService;
use Carbon\Carbon;
use Tests\TestCase;

class DocumentShareLinkServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_short_token_resolves_without_database_or_login_state(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-26 10:00:00', 'Asia/Jayapura'));
        $service = app(DocumentShareLinkService::class);
        $token = $service->token(
            DocumentShareLinkService::TYPE_OUTGOING,
            123,
            Carbon::parse('2026-09-02 23:59:59', 'Asia/Jayapura')
        );

        $this->assertSame(23, strlen($token));
        $this->assertRegExp('/^[A-Za-z0-9_-]{23}$/', $token);
        $this->assertSame([
            'type' => DocumentShareLinkService::TYPE_OUTGOING,
            'document_id' => 123,
            'expires_at' => Carbon::parse('2026-09-02 23:59:59', 'Asia/Jayapura')->timestamp,
            'expired' => false,
        ], $service->resolve($token));
    }

    public function test_tampered_or_expired_token_is_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-26 10:00:00', 'Asia/Jayapura'));
        $service = app(DocumentShareLinkService::class);
        $validToken = $service->token(
            DocumentShareLinkService::TYPE_INCOMING,
            45,
            Carbon::parse('2026-08-27 10:00:00', 'Asia/Jayapura')
        );
        $tampered = ($validToken[0] === 'A' ? 'B' : 'A') . substr($validToken, 1);

        $this->assertNull($service->resolve($tampered));

        $expiredToken = $service->token(
            DocumentShareLinkService::TYPE_INCOMING,
            45,
            Carbon::parse('2026-08-25 10:00:00', 'Asia/Jayapura')
        );
        $this->assertTrue($service->resolve($expiredToken)['expired']);
    }
}
