<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jabatan;
use App\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JabatanManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:super_admin']);
    }

    public function index(Request $request)
    {
        $query = Jabatan::with(['parent', 'unit']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        $jabatans = $query->orderBy('level')->orderBy('nama')->paginate(15)->appends($request->query());
        $parents = Jabatan::orderBy('nama')->get();
        $units = Unit::orderBy('nama')->get();
        $filters = $request->only(['search', 'unit_id', 'level']);

        return view('admin.jabatans.index', compact('jabatans', 'parents', 'units', 'filters'));
    }

    public function create()
    {
        return redirect()->route('admin.jabatans.index');
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        Jabatan::create($data);

        return redirect()->route('admin.jabatans.index')->with('success', 'Jabatan berhasil ditambahkan.');
    }

    public function edit(Jabatan $jabatan)
    {
        return redirect()->route('admin.jabatans.index');
    }

    public function update(Request $request, Jabatan $jabatan)
    {
        $data = $this->validateRequest($request, $jabatan->id);
        $jabatan->update($data);

        return redirect()->route('admin.jabatans.index')->with('success', 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Jabatan $jabatan)
    {
        if ($jabatan->users()->exists()) {
            return redirect()->route('admin.jabatans.index')->with('error', 'Jabatan masih dipakai oleh user.');
        }

        if ($jabatan->children()->exists()) {
            return redirect()->route('admin.jabatans.index')->with('error', 'Jabatan ini masih memiliki turunan.');
        }

        $jabatan->delete();

        return redirect()->route('admin.jabatans.index')->with('success', 'Jabatan berhasil dihapus.');
    }

    protected function validateRequest(Request $request, $jabatanId = null)
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:100', Rule::unique('jabatans', 'kode')->ignore($jabatanId)],
            'parent_id' => ['nullable', 'exists:jabatans,id'],
            'level' => ['required', 'integer', 'min:1'],
            'unit_id' => ['nullable', 'exists:units,id'],
        ]);
    }
}
