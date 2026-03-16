<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function create()
    {
        return redirect()->route('admin.units.index');
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        Unit::create($data);

        return redirect()->route('admin.units.index')->with('success', 'Unit berhasil ditambahkan.');
    }

    public function edit(Unit $unit)
    {
        return redirect()->route('admin.units.index');
    }

    public function update(Request $request, Unit $unit)
    {
        $data = $this->validateRequest($request, $unit->id);
        $unit->update($data);

        return redirect()->route('admin.units.index')->with('success', 'Unit berhasil diperbarui.');
    }

    public function destroy(Unit $unit)
    {
        if ($unit->users()->exists() || $unit->jabatans()->exists()) {
            return redirect()->route('admin.units.index')->with('error', 'Unit masih dipakai oleh user atau jabatan.');
        }

        $unit->delete();

        return redirect()->route('admin.units.index')->with('success', 'Unit berhasil dihapus.');
    }

    protected function validateRequest(Request $request, $unitId = null)
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:100', Rule::unique('units', 'kode')->ignore($unitId)],
            'keterangan' => ['nullable', 'string'],
        ]);
    }
}
