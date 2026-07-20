<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Unit;
use Illuminate\Http\Request;

class UnitManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:super_admin']);
    }

    public function index(Request $request)
    {
        $query = Unit::withCount(['users', 'jabatans']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        $units = $query->orderBy('nama')->paginate(15)->appends($request->query());
        $filters = $request->only(['search']);

        return view('admin.units.index', compact('units', 'filters'));
    }

}
