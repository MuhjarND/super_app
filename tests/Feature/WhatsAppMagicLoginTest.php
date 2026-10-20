<?php

namespace Tests\Feature;

use App\Services\WhatsAppMagicLinkService;
use App\Services\WhatsAppNotificationService;
use App\Services\WebPushNotificationService;
use App\Disposisi;
use App\SuratMasuk;
use App\User;
use App\WhatsAppMagicLoginToken;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsAppMagicLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('no_hp')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('whatsapp_magic_login_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token_hash', 64)->unique();
            $table->text('destination_url');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('whatsapp_magic_login_tokens');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function testMagicLinkCanBeUsedMoreThanOnceUntilItExpires()
    {
        $user = factory(User::class)->create(['no_hp' => '081234567890']);
        $destination = route('dashboard');
        $message = app(WhatsAppMagicLinkService::class)
            ->replaceApplicationUrls($user, 'Buka: ' . $destination);
        preg_match('/https?:\/\/\S+/', $message, $matches);

        $magicUrl = $matches[0];
        $this->assertSame(22, strlen(basename(parse_url($magicUrl, PHP_URL_PATH))));
        $response = $this->get($magicUrl);

        $response->assertRedirect($destination);
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(WhatsAppMagicLoginToken::first()->used_at);

        auth()->logout();
        $response = $this->get($magicUrl);

        $response->assertRedirect($destination);
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(WhatsAppMagicLoginToken::first()->fresh()->used_at);
    }

    public function testShortMagicLinkStillWorksWhenAuditRecordIsUnavailable()
    {
        $user = factory(User::class)->create(['no_hp' => '081234567890']);
        $destination = route('dashboard');
        $message = app(WhatsAppMagicLinkService::class)
            ->replaceApplicationUrls($user, 'Buka: ' . $destination);
        preg_match('/https?:\/\/\S+/', $message, $matches);

        $magicUrl = $matches[0];
        WhatsAppMagicLoginToken::query()->delete();

        $response = $this->get($magicUrl);

        $response->assertRedirect($destination);
        $this->assertAuthenticatedAs($user);
    }

    public function testMagicLinkRejectsAnAlteredToken()
    {
        $user = factory(User::class)->create(['no_hp' => '081234567890']);
        $message = app(WhatsAppMagicLinkService::class)
            ->replaceApplicationUrls($user, 'Buka: ' . route('dashboard'));
        preg_match('/https?:\/\/\S+/', $message, $matches);
        WhatsAppMagicLoginToken::query()->delete();

        $magicUrl = $matches[0];
        $alteredUrl = substr($magicUrl, 0, -1) . (substr($magicUrl, -1) === 'a' ? 'b' : 'a');
        $response = $this->get($alteredUrl);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function testMagicLinkExpiresAfterFourteenDays()
    {
        Carbon::setTestNow(Carbon::parse('2026-07-23 10:00:00', 'Asia/Jayapura'));

        try {
            $user = factory(User::class)->create(['no_hp' => '081234567890']);
            $message = app(WhatsAppMagicLinkService::class)
                ->replaceApplicationUrls($user, 'Buka: ' . route('dashboard'));
            preg_match('/https?:\/\/\S+/', $message, $matches);
            WhatsAppMagicLoginToken::query()->delete();

            Carbon::setTestNow(now()->addDays(15));
            $response = $this->get($matches[0]);

            $response->assertRedirect(route('login'));
            $this->assertGuest();
        } finally {
            Carbon::setTestNow();
        }
    }

    public function testExpiredMagicLinkCannotLogIn()
    {
        $user = factory(User::class)->create();
        $plainToken = Str::random(64);

        WhatsAppMagicLoginToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'destination_url' => route('dashboard'),
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->get(route('whatsapp.magic-login.consume', $plainToken));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNull(WhatsAppMagicLoginToken::first()->used_at);
    }

    public function testOnlyInternalApplicationUrlsAreReplaced()
    {
        $user = factory(User::class)->create();
        $internalUrl = route('dashboard');
        $externalUrl = 'https://zoom.us/j/123456789';

        $message = app(WhatsAppMagicLinkService::class)->replaceApplicationUrls(
            $user,
            'PAPEDA: ' . $internalUrl . "\nZoom: " . $externalUrl
        );

        $this->assertStringContainsString('/masuk/whatsapp/', $message);
        $this->assertStringNotContainsString($internalUrl, $message);
        $this->assertStringContainsString($externalUrl, $message);
        $this->assertSame(1, WhatsAppMagicLoginToken::count());
    }

    public function testSignedSuratKeluarFileUrlUsesAutomaticLogin()
    {
        $user = factory(User::class)->create();
        $fileUrl = URL::temporarySignedRoute(
            'surat-keluar.file',
            now()->addMinutes(5),
            ['suratKeluar' => 3259]
        );

        $message = app(WhatsAppMagicLinkService::class)->replaceApplicationUrls(
            $user,
            'File Surat Keluar: ' . $fileUrl
        );

        $this->assertStringContainsString('/masuk/whatsapp/', $message);
        $this->assertStringNotContainsString($fileUrl, $message);
        $this->assertSame(1, WhatsAppMagicLoginToken::count());
        $this->assertSame($fileUrl, WhatsAppMagicLoginToken::first()->destination_url);
    }

    public function testPlainSuratKeluarFileUrlUsesAutomaticLogin()
    {
        $user = factory(User::class)->create();
        $fileUrl = route('surat-keluar.file', ['suratKeluar' => 3258]);

        $message = app(WhatsAppMagicLinkService::class)->replaceApplicationUrls(
            $user,
            'File Surat Keluar: ' . $fileUrl
        );

        $this->assertStringContainsString('/masuk/whatsapp/', $message);
        $this->assertStringNotContainsString($fileUrl, $message);
        $this->assertSame(1, WhatsAppMagicLoginToken::count());
        $token = WhatsAppMagicLoginToken::first();
        $this->assertSame($fileUrl, $token->destination_url);
        $this->assertTrue($token->expires_at->between(
            now()->addDays(14)->subMinute(),
            now()->addDays(14)->addMinute()
        ));
    }

    public function testLegacyApplicationHostIsRewrittenToPapedaForMagicLoginAndDestination()
    {
        config([
            'app.url' => 'https://simisol.pta-papuabarat.go.id',
            'services.whatsapp.application_url' => 'https://papeda.pta-papuabarat.go.id',
        ]);

        $user = factory(User::class)->create();
        $legacyUrl = 'https://simisol.pta-papuabarat.go.id/cuti/123?mode=approval';
        $message = app(WhatsAppMagicLinkService::class)->replaceApplicationUrls(
            $user,
            'Tindak lanjuti cuti: ' . $legacyUrl
        );

        $this->assertStringContainsString('https://papeda.pta-papuabarat.go.id/masuk/whatsapp/', $message);
        $this->assertSame(
            'https://papeda.pta-papuabarat.go.id/cuti/123?mode=approval',
            WhatsAppMagicLoginToken::first()->destination_url
        );
    }

    /** @dataProvider incomingLetterNotificationTypes */
    public function test_incoming_letter_notification_auto_login_opens_the_letter_list($method, $type, $event)
    {
        $user = factory(User::class)->create(['no_hp' => '081234567890']);
        $letter = new SuratMasuk([
            'nomor_surat' => '123/TEST/X/2026',
            'pengirim' => 'Pengirim Pengujian',
            'perihal' => 'Surat untuk ditindaklanjuti',
            'tanggal_surat' => '2026-10-19',
            'sifat' => 'biasa',
        ]);
        $letter->id = 123;
        $disposition = new Disposisi(['tipe' => $type, 'petunjuk' => 'Untuk perhatian']);
        $disposition->id = 456;
        $disposition->setRelation('suratMasuk', $letter);
        $disposition->setRelation('dariUser', $user);
        $message = null;
        $pushPayload = null;
        $push = $this->getMockBuilder(WebPushNotificationService::class)
            ->onlyMethods(['sendToUser'])->getMock();
        $push->expects($this->once())->method('sendToUser')
            ->willReturnCallback(function ($target, $payload) use (&$pushPayload) {
                $pushPayload = $payload;
                return 1;
            });
        $this->app->instance(WebPushNotificationService::class, $push);
        $service = $this->getMockBuilder(WhatsAppNotificationService::class)
            ->setConstructorArgs([app(WhatsAppMagicLinkService::class)])
            ->onlyMethods(['send'])->getMock();
        $service->expects($this->once())->method('send')
            ->willReturnCallback(function ($phone, $text, $context) use (&$message, $user, $event) {
                $this->assertSame($user->no_hp, $phone);
                $this->assertSame($event, $context['event']);
                $message = $text;
                return true;
            });

        $this->assertTrue($service->{$method}($method === 'notifySuratMasuk' ? $letter : $disposition, $user));
        $this->assertStringContainsString('daftar Surat Masuk', $message);
        $this->assertStringNotContainsString('/surat-masuk/123', $message);
        $this->assertSame(1, WhatsAppMagicLoginToken::count());
        $this->assertSame(route('surat-masuk.index'), WhatsAppMagicLoginToken::first()->destination_url);
        preg_match('/https?:\/\/\S+/', $message, $matches);
        $this->assertStringContainsString('/masuk/whatsapp/', $matches[0]);
        $this->assertSame($matches[0], $pushPayload['url']);

        $this->get($matches[0])->assertRedirect(route('surat-masuk.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function incomingLetterNotificationTypes()
    {
        return [
            'new incoming letter' => ['notifySuratMasuk', 'disposisi', 'surat_masuk'],
            'disposition' => ['notifyDisposisi', 'disposisi', 'disposisi'],
            'escalation' => ['notifyDisposisi', 'naikan', 'disposisi'],
            'reminder' => ['notifyDisposisiReminder', 'disposisi', 'disposisi_reminder'],
        ];
    }
}
