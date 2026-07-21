<?php

namespace Tests\Feature;

use App\Services\WhatsAppNotificationService;
use App\User;
use App\WhatsAppMagicLoginToken;
use App\WhatsAppNotificationLog;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsAppOutboxTest extends TestCase
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

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('whatsapp_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('module', 100);
            $table->string('event', 100);
            $table->string('notifiable_type')->nullable();
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->unsignedBigInteger('target_user_id')->nullable();
            $table->string('target_name')->nullable();
            $table->string('phone_number', 50)->nullable();
            $table->longText('message');
            $table->string('fingerprint', 64)->nullable();
            $table->string('status', 30)->default('queued');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->longText('response_body')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        DB::table('app_settings')->insert([
            'key' => 'whatsapp_notifications_enabled',
            'value' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        config([
            'services.whatsapp.api_url' => 'https://api.example.test/send',
            'services.whatsapp.api_key' => 'test-token',
            'services.whatsapp.work_start_hour' => 0,
            'services.whatsapp.work_end_hour' => 24,
            'services.whatsapp.work_days' => '1,2,3,4,5,6,7',
            'services.whatsapp.minimum_interval_seconds' => 1,
            'services.whatsapp.max_per_hour' => 30,
            'services.whatsapp.max_per_phone_hour' => 5,
            'services.whatsapp.max_per_day' => 150,
            'services.whatsapp.deduplicate_minutes' => 10,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Schema::dropIfExists('whatsapp_notification_logs');
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('whatsapp_magic_login_tokens');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function testMessageIsSentDirectlyWithoutScheduler()
    {
        Http::fake([
            'api.example.test/*' => Http::response(['status' => true], 200),
        ]);

        $result = app(WhatsAppNotificationService::class)->send(
            '081234567890',
            'Pesan pengujian.',
            ['module' => 'testing', 'event' => 'queued']
        );

        $this->assertTrue($result);
        $this->assertSame('sent', WhatsAppNotificationLog::first()->status);
        $this->assertNotNull(WhatsAppNotificationLog::first()->scheduled_at);
        Http::assertSentCount(1);
    }

    public function testDuplicateMessageIsOnlySentOnce()
    {
        Http::fake([
            'api.example.test/*' => Http::response(['status' => true], 200),
        ]);

        $service = app(WhatsAppNotificationService::class);
        $context = ['module' => 'testing', 'event' => 'duplicate', 'notifiable_id' => 10];

        $service->send('081234567890', 'Pesan yang sama.', $context);
        $service->send('081234567890', 'Pesan yang sama.', $context);

        $this->assertSame(1, WhatsAppNotificationLog::count());
    }

    public function testProviderFailureIsNotMarkedAsSent()
    {
        Http::fake([
            'api.example.test/*' => Http::response(['status' => false], 200),
        ]);

        $service = app(WhatsAppNotificationService::class);
        $result = $service->send('081234567890', 'Pesan siap dikirim.', [
            'module' => 'testing',
            'event' => 'delivery',
        ]);

        $log = WhatsAppNotificationLog::first();
        $this->assertFalse($result);
        $this->assertSame('failed', $log->fresh()->status);
        $this->assertSame(1, $log->fresh()->attempt_count);
        $this->assertNull($log->fresh()->sent_at);
        Http::assertSentCount(1);
    }

    public function testMessageCanBeScheduledOnSunday()
    {
        Carbon::setTestNow(Carbon::parse('2026-07-12 10:00:00', 'Asia/Jayapura'));
        Http::fake([
            'api.example.test/*' => Http::response(['status' => true], 200),
        ]);

        app(WhatsAppNotificationService::class)->send(
            '081234567890',
            'Notifikasi hari Minggu.',
            ['module' => 'testing', 'event' => 'sunday']
        );

        $this->assertSame(
            '2026-07-12',
            WhatsAppNotificationLog::first()->scheduled_at->timezone('Asia/Jayapura')->format('Y-m-d')
        );
    }

    public function testMessageUsesCompactPapedaFormat()
    {
        Http::fake([
            'api.example.test/*' => Http::response(['status' => true], 200),
        ]);

        app(WhatsAppNotificationService::class)->send(
            '081234567890',
            "Yth. Bapak/Ibu,\nDengan hormat, terdapat surat yang perlu ditinjau.\n\nNomor Surat: 123/ABC\nSilakan meninjau surat melalui tautan berikut:\nhttps://example.test/surat/123\n\nHormat kami,\nPAPEDA",
            ['module' => 'persuratan', 'event' => 'format']
        );

        $message = WhatsAppNotificationLog::first()->message;

        $this->assertStringStartsWith('*PAPEDA | PERSURATAN*', $message);
        $this->assertStringContainsString('*Nomor Surat:* 123/ABC', $message);
        $this->assertStringContainsString('*Buka tautan:*', $message);
        $this->assertStringNotContainsString('login otomatis', $message);
        $this->assertStringNotContainsString('Dengan hormat', $message);
        $this->assertSame(1, substr_count($message, 'PAPEDA'));
    }

    public function testUserNotificationContainsOneClickLoginLink()
    {
        Http::fake([
            'api.example.test/*' => Http::response(['status' => true], 200),
        ]);
        $user = factory(User::class)->create(['no_hp' => '081234567890']);

        app(WhatsAppNotificationService::class)->sendToUser(
            $user,
            "Dengan hormat, terdapat tugas baru.\n\nSilakan meninjau melalui tautan berikut:\n" . route('dashboard'),
            ['module' => 'general', 'event' => 'one_click']
        );

        $message = WhatsAppNotificationLog::first()->message;

        $this->assertStringContainsString('/masuk/whatsapp/', $message);
        $this->assertStringContainsString('*Buka di PAPEDA (login otomatis):*', $message);
        $this->assertStringContainsString('hanya dapat digunakan satu kali', $message);
        $this->assertSame((int) $user->id, (int) WhatsAppMagicLoginToken::first()->user_id);
    }
}
