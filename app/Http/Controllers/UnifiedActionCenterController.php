<?php

namespace App\Http\Controllers;

use App\Services\UnifiedActionCenterService;
use Illuminate\Http\Request;

class UnifiedActionCenterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request, UnifiedActionCenterService $service)
    {
        $user = $request->user();
        abort_unless($user && $user->canAccessUnifiedActionCenter(), 403);

        $payload = $service->build($user, $request->only([
            'tab',
            'module',
            'status',
            'search',
        ]));

        return view('action-center.index', $payload);
    }
}
