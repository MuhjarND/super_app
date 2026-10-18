<?php

namespace App\Http\Middleware;

use Closure;

class EnsureChatbotApiKey
{
    public function handle($request, Closure $next)
    {
        $key = (string) config('chatbot.internal_api_key', '');
        $providedKey = (string) $request->header('X-INTERNAL-API-KEY', '');

        if ($key === '' || !hash_equals($key, $providedKey)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
