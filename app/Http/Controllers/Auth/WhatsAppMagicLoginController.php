<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\WhatsAppMagicLoginToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WhatsAppMagicLoginController extends Controller
{
    public function consume(Request $request, $token)
    {
        $magicToken = DB::transaction(function () use ($token) {
            $record = WhatsAppMagicLoginToken::with('user')
                ->where('token_hash', hash('sha256', (string) $token))
                ->lockForUpdate()
                ->first();

            if (!$record || !$record->expires_at || $record->expires_at->isPast() || !$record->user) {
                return null;
            }

            $record->forceFill(['used_at' => now()])->save();

            return $record;
        });

        if (!$magicToken) {
            return redirect()->route('login')
                ->withErrors(['magic_link' => 'Tautan WhatsApp tidak valid atau telah kedaluwarsa.']);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::login($magicToken->user);
        $request->session()->regenerate();

        return redirect()->to($magicToken->destination_url);
    }
}
