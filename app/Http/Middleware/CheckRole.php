<?php

namespace App\Http\Middleware;

use Closure;

class CheckRole
{
    public function handle($request, Closure $next, ...$roles)
    {
        if (!$request->user()) {
            return redirect('login');
        }

        if ($request->user()->isSuperAdmin()) {
            return $next($request);
        }

        foreach ($roles as $role) {
            if ($request->user()->hasRole($role)) {
                return $next($request);
            }
        }

        abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }
}
