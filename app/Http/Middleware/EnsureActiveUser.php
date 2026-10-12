<?php

namespace App\Http\Middleware;

use App\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class EnsureActiveUser
{
    public function handle($request, Closure $next)
    {
        $user = $request->user();
        if (!$user || $user->canLogin()) {
            return $next($request);
        }

        // Also revoke existing sessions and remember-me authentication after
        // an administrator deactivates an account.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => User::INACTIVE_ACCOUNT_MESSAGE], 403);
        }

        return redirect()->route('login')->withErrors(['login' => User::INACTIVE_ACCOUNT_MESSAGE]);
    }
}
