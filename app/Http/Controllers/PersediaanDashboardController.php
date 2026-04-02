<?php

namespace App\Http\Controllers;

use App\InventoryItem;
use App\InventoryItemDetail;
use App\InventoryMaintenanceTransaction;
use App\InventoryRoom;
use Illuminate\Support\Facades\DB;

class PersediaanDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        abort_unless(auth()->user()->canAccessInventoryModule(), 403);

        $stats = [
            'item_count' => InventoryItem::count(),
            'detail_count' => InventoryItemDetail::count(),
            'room_count' => InventoryRoom::count(),
            'maintenance_count' => InventoryMaintenanceTransaction::count(),
            'maintenance_total' => (float) InventoryMaintenanceTransaction::sum('amount'),
            'month_total' => (float) InventoryMaintenanceTransaction::whereBetween('transaction_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
        ];

        $recentTransactions = InventoryMaintenanceTransaction::with(['item', 'detail', 'attachments'])
            ->latest('transaction_date')
            ->take(8)
            ->get();

        $topItems = InventoryMaintenanceTransaction::select('inventory_item_id', DB::raw('SUM(amount) as total_amount'))
            ->groupBy('inventory_item_id')
            ->with('item')
            ->orderByDesc('total_amount')
            ->take(6)
            ->get();

        $conditionSummary = InventoryItemDetail::select('inventory_condition_id', DB::raw('COUNT(*) as total'))
            ->groupBy('inventory_condition_id')
            ->with('condition')
            ->get();

        return view('persediaan.dashboard', compact('stats', 'recentTransactions', 'topItems', 'conditionSummary'));
    }
}
