<?php

namespace App\Http\Middleware;

use Closure;

class EnsureLibraryManage
{
    public function handle($request, Closure $next)
    {
        abort_unless(auth()->check() && auth()->user()->canManageLibraryModule(), 403);

        return $next($request);
    }
}
