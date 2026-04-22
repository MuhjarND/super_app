<?php

namespace App\Http\Controllers;

use App\Notifications\TwoFactorStatusNotification;
use App\Services\TwoFactorAuthenticationService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorController extends Controller
{
    protected $service;
    protected $whatsAppService;

    public function __construct(TwoFactorAuthenticationService $service, WhatsAppNotificationService $whatsAppService)
    {
        $this->middleware('auth');
        $this->service = $service;
        $this->whatsAppService = $whatsAppService;
    }

    public function edit()
    {
        $user = auth()->user();
        $setupSecret = session('two_factor_setup_secret');
        $qrSvg = null;
        $generatedRecoveryCodes = session('two_factor_generated_recovery_codes', []);

        if ($setupSecret && !$user->hasTwoFactorEnabled()) {
            $qrSvg = QrCode::size(180)->margin(1)->generate(
                $this->service->provisioningUri($user, $setupSecret)
            );
        }

        return view('auth.two-factor', [
            'user' => $user,
            'setupSecret' => $setupSecret,
            'qrSvg' => $qrSvg,
            'generatedRecoveryCodes' => $generatedRecoveryCodes,
            'remainingRecoveryCodeCount' => $user->hasTwoFactorEnabled()
                ? $this->service->remainingRecoveryCodeCount($user)
                : 0,
        ]);
    }

    public function setup(Request $request)
    {
        $user = auth()->user();

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.edit');
        }

        $secret = $this->service->generateSecret();
        $request->session()->put('two_factor_setup_secret', $secret);

        return redirect()->route('two-factor.edit')->with('success', 'Authenticator 2 faktor siap dihubungkan. Scan QR code lalu verifikasi kode 6 digit.');
    }

    public function enable(Request $request)
    {
        $user = auth()->user();
        $secret = $request->session()->get('two_factor_setup_secret');

        abort_unless($secret, 422, 'Sesi aktivasi 2 faktor tidak ditemukan. Silakan mulai ulang setup.');

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if (!$this->service->verifyCode($secret, $validated['code'])) {
            return back()->withErrors([
                'code' => 'Kode authenticator tidak valid.',
            ]);
        }

        $recoveryCodes = $this->service->generateRecoveryCodes();

        $user->update([
            'two_factor_secret' => $this->service->encryptSecret($secret),
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $this->service->encryptRecoveryCodes($recoveryCodes),
        ]);

        $request->session()->forget('two_factor_setup_secret');
        $request->session()->flash('two_factor_generated_recovery_codes', $recoveryCodes);

        $this->notifyStatusChange(
            $user,
            'Authenticator 2 faktor berhasil diaktifkan',
            'Authenticator 2 faktor pada akun Anda telah berhasil diaktifkan. Mulai login berikutnya, sistem akan meminta kode verifikasi tambahan dari aplikasi authenticator.',
            true,
            count($recoveryCodes)
        );

        return redirect()->route('two-factor.edit')->with('success', 'Authenticator 2 faktor berhasil diaktifkan.');
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $user = auth()->user();

        abort_unless($user->hasTwoFactorEnabled(), 404);

        $recoveryCodes = $this->service->generateRecoveryCodes();

        $user->update([
            'two_factor_recovery_codes' => $this->service->encryptRecoveryCodes($recoveryCodes),
        ]);

        $request->session()->flash('two_factor_generated_recovery_codes', $recoveryCodes);

        $this->notifyStatusChange(
            $user,
            'Backup recovery code 2 faktor telah diperbarui',
            'Backup recovery code untuk authenticator 2 faktor pada akun Anda telah diperbarui. Recovery code lama tidak berlaku lagi.',
            true,
            count($recoveryCodes)
        );

        return redirect()->route('two-factor.edit')->with('success', 'Backup recovery code berhasil diperbarui.');
    }

    public function disable(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'Password saat ini tidak sesuai.',
            ]);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        $request->session()->forget('two_factor_setup_secret');

        $this->notifyStatusChange(
            $user,
            'Authenticator 2 faktor berhasil dinonaktifkan',
            'Authenticator 2 faktor pada akun Anda telah dinonaktifkan. Login berikutnya tidak lagi memerlukan kode verifikasi tambahan.',
            false
        );

        return redirect()->route('two-factor.edit')->with('success', 'Authenticator 2 faktor berhasil dinonaktifkan.');
    }

    protected function notifyStatusChange($user, $title, $message, $enabled, $recoveryCodeCount = null)
    {
        try {
            $user->notify(new TwoFactorStatusNotification(
                $title,
                $message,
                route('two-factor.edit'),
                $enabled ? 'enabled' : 'disabled',
                $recoveryCodeCount
            ));
        } catch (\Throwable $exception) {
            Log::warning('Gagal mengirim notifikasi 2FA via Laravel notification.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);
        }

        try {
            $this->whatsAppService->notifyTwoFactorStatusChanged($user, $enabled, $recoveryCodeCount);
        } catch (\Throwable $exception) {
            Log::warning('Gagal mengirim notifikasi 2FA via WhatsApp.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
