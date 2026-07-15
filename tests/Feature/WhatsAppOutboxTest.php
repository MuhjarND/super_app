<?php

namespace Tests\Feature;

use App\Services\WhatsAppNotificationService;
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
}
