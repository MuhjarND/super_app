<?php

namespace Tests\Feature;

use App\Services\WhatsAppMagicLinkService;
use App\User;
use App\WhatsAppMagicLoginToken;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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

    public function testMagicLinkCanBeUsedRepeatedlyBeforeItExpires()
    {
        $user = factory(User::class)->create(['no_hp' => '081234567890']);
        $destination = route('dashboard');
        $message = app(WhatsAppMagicLinkService::class)
            ->replaceApplicationUrls($user, 'Buka: ' . $destination);
        preg_match('/https?:\/\/\S+/', $message, $matches);

        $magicUrl = $matches[0];
        $response = $this->get($magicUrl);

        $response->assertRedirect($destination);
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(WhatsAppMagicLoginToken::first()->used_at);

        auth()->logout();
        $response = $this->get($magicUrl);

        $response->assertRedirect($destination);
        $this->assertAuthenticatedAs($user);
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
}
