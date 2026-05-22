<?php

namespace App\Http\Controllers;

use App\SupplyPickup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SupplyPickupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()->canAccessSupplyModule(), 403);

        if (!Schema::hasTable('supply_pickups')) {
            return response()->view('persediaan.supplies.setup');
        }

        $user = auth()->user();
        $canManage = $user->canManageSupplyModule();

        $query = SupplyPickup::with(['user', 'item', 'request'])
            ->latest('pickup_date')
            ->latest('id');

        if (!$canManage) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($builder) use ($search) {
                $builder->where('item_name_snapshot', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $pickups = $query->paginate(20);

        return view('persediaan.supplies.pickups.index', compact('pickups', 'canManage'));
    }
}
