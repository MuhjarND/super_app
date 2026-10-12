<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\ResetPasswordController;
use App\Services\ChatbotGatewayService;
use App\Services\TwoFactorAuthenticationService;
use App\Services\WhatsAppMagicLinkService;
use App\User;
use App\WhatsAppMagicLoginToken;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class InactiveUserAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('nip')->nullable();
            $table->string('password');
            $table->boolean('status_aktif_pegawai')->default(true);
            $table->boolean('is_active')->nullable()->default(true);
            $table->string('api_token')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->timestamp('two_factor_confirmed_at')->nullable();
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
        Route::middleware(['web', 'auth'])->get('/_test/active-account', function () {
            return response('Allowed');
        });
    }

    public function test_inactive_user_cannot_login_by_email_or_nip_even_with_remember_me()
    {
        $user = $this->createUser(false);
        foreach ([$user->email, $user->nip] as $login) {
            $this->from('/login')->post('/login', [
                'login' => $login,
                'password' => 'valid-password',
                'remember' => '1',
            ])->assertRedirect('/login')->assertSessionHasErrors('login');
            $this->assertGuest();
            $this->assertNull($user->fresh()->remember_token);
            $this->assertFalse(session()->has('auth.2fa:user_id'));
        }
    }

    public function test_active_user_can_still_login()
    {
        $user = $this->createUser(true);
        $this->post('/login', ['login' => $user->email, 'password' => 'valid-password'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->get('/_test/active-account')->assertOk()->assertSee('Allowed');
    }

    public function test_legacy_inactive_flag_also_prevents_login()
    {
        $user = $this->createUser(true);
        $user->forceFill(['is_active' => false])->save();
        $this->from('/login')->post('/login', ['login' => $user->email, 'password' => 'valid-password'])
            ->assertRedirect('/login')->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_old_whatsapp_link_cannot_login_after_deactivation()
    {
        $user = $this->createUser(true);
        $url = app(WhatsAppMagicLinkService::class)->create($user, route('dashboard'));
        $user->update(['status_aktif_pegawai' => false]);
        $this->get($url)->assertRedirect(route('login'))->assertSessionHasErrors('magic_link');
        $this->assertGuest();
    }

    public function test_cached_whatsapp_link_cannot_bypass_inactive_status()
    {
        $user = $this->createUser(true);
        $url = app(WhatsAppMagicLinkService::class)->create($user, route('dashboard'));
        WhatsAppMagicLoginToken::query()->delete();
        $user->update(['status_aktif_pegawai' => false]);
        $this->get($url)->assertRedirect(route('login'))->assertSessionHasErrors('magic_link');
        $this->assertGuest();
    }

    public function test_chatbot_auto_login_rejects_inactive_user()
    {
        $user = $this->createUser(false);
        $gateway = $this->createMock(ChatbotGatewayService::class);
        $gateway->expects($this->once())->method('validateMagicToken')
            ->willReturn(['valid' => true, 'app_user_id' => (string) $user->id]);
        $this->app->instance(ChatbotGatewayService::class, $gateway);

        $this->post('/autologin', ['token' => 'valid-chatbot-token'])->assertStatus(401);
        $this->assertGuest();
    }

    public function test_deactivation_during_two_factor_challenge_prevents_login()
    {
        $user = $this->createUser(true);
        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_secret' => 'stored-secret',
            'two_factor_confirmed_at' => now(),
        ])->save();
        $this->post('/login', ['login' => $user->email, 'password' => 'valid-password'])
            ->assertRedirect(route('two-factor.challenge.show'));
        $user->update(['status_aktif_pegawai' => false]);
        $service = $this->createMock(TwoFactorAuthenticationService::class);
        $service->expects($this->never())->method('verifyCode');
        $service->expects($this->never())->method('useRecoveryCode');
        $this->app->instance(TwoFactorAuthenticationService::class, $service);

        $this->post('/login/2fa', ['code' => '123456'])->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');
        $this->assertGuest();
        $this->assertFalse(session()->has('auth.2fa:user_id'));
        $this->assertFalse(session()->has('auth.2fa:remember'));
    }

    public function test_existing_session_is_logged_out_on_next_request_after_deactivation()
    {
        $user = $this->createUser(true);
        $this->post('/login', ['login' => $user->email, 'password' => 'valid-password']);
        $user->update(['status_aktif_pegawai' => false]);
        // Laravel 7 caches guards during tests; a real request gets a new guard.
        $guards = new \ReflectionProperty($this->app['auth'], 'guards');
        $guards->setAccessible(true);
        $guards->setValue($this->app['auth'], []);

        $this->get('/_test/active-account')->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_inactive_session_cannot_access_json_routes()
    {
        $user = $this->createUser(false);
        $this->actingAs($user)->getJson('/_test/active-account')->assertStatus(403)
            ->assertJson(['message' => User::INACTIVE_ACCOUNT_MESSAGE]);
        $this->assertGuest();
    }

    public function test_remember_me_cookie_cannot_restore_an_inactive_account()
    {
        $user = $this->createUser(false);
        $user->setRememberToken('saved-remember-token');
        $user->save();
        $cookieName = Auth::guard()->getRecallerName();
        $cookieValue = $user->id . '|' . $user->getRememberToken() . '|' . $user->password;

        $this->withCookie($cookieName, $cookieValue)->get('/_test/active-account')
            ->assertRedirect(route('login'))->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_inactive_api_token_cannot_access_user_endpoint()
    {
        $user = $this->createUser(false);
        $user->forceFill(['api_token' => 'test-api-token'])->save();
        $this->getJson('/api/user?api_token=test-api-token')->assertUnauthorized();
    }

    public function test_password_reset_does_not_log_in_an_inactive_user()
    {
        $user = $this->createUser(false);
        $controller = new ResetPasswordController();
        $method = new ReflectionMethod($controller, 'resetPassword');
        $method->setAccessible(true);
        $method->invoke($controller, $user, 'new-password');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertFalse($user->fresh()->status_aktif_pegawai);
        $this->assertGuest();
    }

    protected function createUser($active)
    {
        return User::create([
            'name' => 'Test Login',
            'email' => 'login-test@example.test',
            'nip' => '198001012010011001',
            'password' => Hash::make('valid-password'),
            'status_aktif_pegawai' => $active,
        ]);
    }
}
