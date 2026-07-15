<?php

namespace App\Http\Middleware;

use Closure;

class EnsureLibraryAccess
{
    public function handle($request, Closure $next)
    {
        abort_unless(auth()->check() && auth()->user()->canAccessLibraryModule(), 403);

        return $next($request);
    }
}
