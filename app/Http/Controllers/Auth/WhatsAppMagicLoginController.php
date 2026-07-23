<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\WhatsAppMagicLinkService;
use App\User;
use App\WhatsAppMagicLoginToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WhatsAppMagicLoginController extends Controller
{
    public function consume(Request $request, $token, WhatsAppMagicLinkService $magicLinkService)
    {
        $magicToken = DB::transaction(function () use ($token) {
            $record = WhatsAppMagicLoginToken::with('user')
                ->where('token_hash', hash('sha256', (string) $token))
                ->lockForUpdate()
                ->first();

            if (!$record || !$record->expires_at || $record->expires_at->isPast() || !$record->user) {
                return null;
            }

            // Keep the last access time for audit without invalidating the reusable link.
            $record->forceFill(['used_at' => now()])->save();

            return $record;
        });

        $user = $magicToken ? $magicToken->user : null;
        $destinationUrl = $magicToken ? $magicToken->destination_url : null;

        if (!$user) {
            $signedPayload = $magicLinkService->resolveSignedToken($token);
            if ($signedPayload) {
                $user = User::find($signedPayload['user_id']);
                $destinationUrl = $signedPayload['destination_url'];
            }
        }

        if (!$user) {
            return redirect()->route('login')
                ->withErrors(['magic_link' => 'Tautan WhatsApp tidak valid atau telah kedaluwarsa.']);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to($destinationUrl ?: route('dashboard'));
    }
}
