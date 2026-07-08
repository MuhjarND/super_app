<?php

namespace App\Http\Controllers;

use App\Services\ChatbotGatewayService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AutoLoginController extends Controller
{
    public function show(Request $request)
    {
        $token = $this->tokenFromRequest($request);
        $redirect = $this->safeRedirectPath((string) $request->query('redirect', ''));

        if ($token === '') {
            return $this->errorResponse();
        }

        return response()
            ->view('auth.autologin-continue', compact('token', 'redirect'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    public function login(Request $request, ChatbotGatewayService $gateway)
    {
        $token = (string) $request->input('token', '');
        $redirect = $this->safeRedirectPath((string) $request->input('redirect', ''));

        if ($token === '') {
            return $this->errorResponse();
        }

        $validation = $gateway->validateMagicToken($token);

        if (empty($validation['valid']) || empty($validation['app_user_id'])) {
            if (Auth::guard(config('auth.defaults.guard', 'web'))->check()) {
                return redirect($redirect ?: $this->redirectPathFor(Auth::user()));
            }

            return $this->errorResponse();
        }

        $appUserId = (string) $validation['app_user_id'];
        $user = $this->findUser($appUserId);

        if (!$user) {
            Log::warning('Chatbot magic login user not found', [
                'app_user_id_hash' => substr(hash('sha256', $appUserId), 0, 16),
            ]);

            return $this->errorResponse();
        }

        $guard = config('auth.defaults.guard', 'web');

        Auth::guard($guard)->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::guard($guard)->login($user);
        $request->session()->regenerate();

        Log::info('Chatbot magic login user matched', [
            'user_id' => $user->id,
            'guard' => $guard,
        ]);

        return redirect($redirect ?: $this->redirectPathFor($user));
    }

    private function findUser(string $appUserId)
    {
        $query = User::query()->where('id', $appUserId);

        if (filter_var($appUserId, FILTER_VALIDATE_EMAIL)) {
            $query->orWhere('email', $appUserId);
        }

        $user = $query->first();

        if (!$user) {
            return null;
        }

        if (Schema::hasColumn('users', 'status_aktif_pegawai') && (int) $user->status_aktif_pegawai !== 1) {
            return null;
        }

        if (Schema::hasColumn('users', 'is_active') && isset($user->is_active) && (int) $user->is_active !== 1) {
            return null;
        }

        return $user;
    }

    private function redirectPathFor($user): string
    {
        return '/dashboard';
    }

    private function safeRedirectPath(string $path): string
    {
        if ($path === '') {
            return '';
        }

        $decoded = rawurldecode($path);

        if (strpos($decoded, '://') !== false || strpos($decoded, '//') === 0) {
            return '';
        }

        if (substr($decoded, 0, 1) !== '/') {
            return '';
        }

        $allowedPrefixes = [
            '/dashboard',
            '/home',
            '/profil',
            '/surat-masuk',
            '/surat-keluar',
            '/surat-keluar-approval',
            '/template-surat',
            '/approval',
            '/cuti',
            '/progress-zi',
            '/rapat',
            '/persediaan',
            '/perawatan-alat-dan-mesin',
            '/tindak-lanjut-terpadu',
            '/kalender-terpadu',
            '/audit-trail',
        ];

        foreach ($allowedPrefixes as $prefix) {
            if ($decoded === $prefix || strpos($decoded, $prefix . '/') === 0 || strpos($decoded, $prefix . '?') === 0) {
                return $decoded;
            }
        }

        return '';
    }

    private function errorResponse()
    {
        return response()
            ->view('auth.autologin-error', [
                'message' => config('chatbot.autologin_error_message'),
            ], 401);
    }

    private function tokenFromRequest(Request $request)
    {
        $rawQuery = (string) $request->server('QUERY_STRING', '');

        foreach (explode('&', $rawQuery) as $part) {
            if ($part === '') {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');

            if (rawurldecode($key) === 'token') {
                return rawurldecode($value);
            }
        }

        return (string) $request->query('token', '');
    }
}
