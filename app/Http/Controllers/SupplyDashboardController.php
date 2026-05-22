<?php

namespace App\Http\Controllers;

use App\SupplyItem;
use App\SupplyPickup;
use App\SupplyRequest;
use Illuminate\Support\Facades\Schema;

class SupplyDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        abort_unless(auth()->user()->canAccessSupplyModule(), 403);

        if (!$this->moduleReady()) {
            return $this->setupResponse();
        }

        $user = auth()->user();
        $canManage = $user->canManageSupplyModule();

        $requestQuery = SupplyRequest::query();
        $pickupQuery = SupplyPickup::query();

        if (!$canManage) {
            $requestQuery->where('user_id', $user->id);
            $pickupQuery->where('user_id', $user->id);
        }

        $stats = [
            'item_count' => SupplyItem::active()->count(),
            'stock_total' => (int) SupplyItem::active()->sum('stock'),
            'pending_count' => (clone $requestQuery)->where('status', SupplyRequest::STATUS_PENDING)->count(),
            'month_pickup_count' => (clone $pickupQuery)->whereBetween('pickup_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('quantity'),
        ];

        $availableItems = SupplyItem::active()
            ->orderByDesc('stock')
            ->orderBy('name')
            ->take(12)
            ->get();

        $recentRequests = $requestQuery->with(['requester', 'items'])
            ->latest('id')
            ->take(8)
            ->get();

        return view('persediaan.supplies.dashboard', compact('stats', 'availableItems', 'recentRequests', 'canManage'));
    }

    protected function moduleReady()
    {
        return Schema::hasTable('supply_items') && Schema::hasTable('supply_requests');
    }

    protected function setupResponse()
    {
        return response()->view('persediaan.supplies.setup');
    }
}
