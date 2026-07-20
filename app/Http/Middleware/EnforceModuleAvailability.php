<?php

namespace App\Http\Middleware;

use App\Services\ModuleSettingService;
use Closure;

class EnforceModuleAvailability
{
    protected $settings;

    public function __construct(ModuleSettingService $settings)
    {
        $this->settings = $settings;
    }

    public function handle($request, Closure $next)
    {
        $moduleKey = $this->settings->resolveRoute(optional($request->route())->getName());
        if (!$moduleKey) {
            return $next($request);
        }

        $state = $this->settings->state($moduleKey, $request->user());
        if (!$state || $state['accessible']) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $state['status'] === 'maintenance'
                    ? $state['maintenance_message']
                    : 'Modul belum dipublikasikan.',
            ], 503);
        }

        return response()->view('errors.module-unavailable', ['module' => $state], 503);
    }
}
