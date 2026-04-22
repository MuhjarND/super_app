<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthenticationService;
use App\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorChallengeController extends Controller
{
    protected $service;

    public function __construct(TwoFactorAuthenticationService $service)
    {
        $this->service = $service;
        $this->middleware('guest');
    }

    public function show(Request $request)
    {
        if (!$request->session()->has('auth.2fa:user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $userId = $request->session()->get('auth.2fa:user_id');
        $remember = (bool) $request->session()->pull('auth.2fa:remember', false);
        $user = User::find($userId);

        if (!$user || !$user->hasTwoFactorEnabled()) {
            $request->session()->forget('auth.2fa:user_id');
            return redirect()->route('login');
        }

        $secret = app(TwoFactorAuthenticationService::class)->decryptSecret($user->two_factor_secret);

        $input = trim((string) $request->input('code'));
        $isTotpCode = preg_match('/^\d{6}$/', $input) === 1;
        $validated = false;

        if ($isTotpCode && $secret) {
            $validated = $this->service->verifyCode($secret, $input);
        } else {
            $validated = $this->service->useRecoveryCode($user, $input);
        }

        if (!$validated) {
            return back()->withErrors([
                'code' => 'Kode authenticator atau recovery code tidak valid.',
            ]);
        }

        $request->session()->forget('auth.2fa:user_id');
        Auth::login($user, $remember);
        $request->session()->regenerate();

        if (!$isTotpCode) {
            return redirect()->route('two-factor.edit')->with(
                'warning',
                'Login berhasil menggunakan recovery code. Mohon segera perbarui backup recovery code Anda.'
            );
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
