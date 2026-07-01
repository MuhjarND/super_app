<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;
    protected $twoFactorService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(TwoFactorAuthenticationService $twoFactorService)
    {
        $this->middleware('guest')->except('logout');
        $this->twoFactorService = $twoFactorService;
    }

    public function username()
    {
        return 'login';
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ], [], [
            'login' => 'Email atau NIP pegawai',
            'password' => 'password',
        ]);
    }

    protected function credentials(Request $request)
    {
        $login = trim((string) $request->input('login'));

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => strtolower($login),
                'password' => $request->input('password'),
            ];
        }

        $nip = preg_replace('/\D+/', '', $login);
        $nip = $nip !== '' ? $nip : $login;

        return [
            'nip' => $nip,
            'password' => $request->input('password'),
        ];
    }

    protected function authenticated(Request $request, $user)
    {
        if ($user->hasTwoFactorEnabled()) {
            $remember = (bool) $request->boolean('remember');

            Auth::logout();
            $request->session()->put('auth.2fa:user_id', $user->id);
            $request->session()->put('auth.2fa:remember', $remember);

            return redirect()->route('two-factor.challenge.show');
        }
    }
}
