<?php

namespace App\Http\Controllers\Admin;

use App\Bidang;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BidangManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:super_admin']);
    }

    public function index(Request $request)
    {
        $query = Bidang::withCount('users');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        $bidangs = $query->orderBy('nama')->paginate(15)->appends($request->query());
        $filters = $request->only(['search']);

        return view('admin.bidangs.index', compact('bidangs', 'filters'));
    }

    public function create()
    {
        return redirect()->route('admin.bidangs.index');
    }

    public function store(Request $request)
    {
        Bidang::create($this->validateRequest($request));

        return redirect()->route('admin.bidangs.index')->with('success', 'Bidang berhasil ditambahkan.');
    }

    public function edit(Bidang $bidang)
    {
        return redirect()->route('admin.bidangs.index');
    }

    public function update(Request $request, Bidang $bidang)
    {
        $bidang->update($this->validateRequest($request, $bidang->id));

        return redirect()->route('admin.bidangs.index')->with('success', 'Bidang berhasil diperbarui.');
    }

    public function destroy(Bidang $bidang)
    {
        if ($bidang->users()->exists()) {
            return redirect()->route('admin.bidangs.index')->with('error', 'Bidang masih dipakai oleh user.');
        }

        $bidang->delete();

        return redirect()->route('admin.bidangs.index')->with('success', 'Bidang berhasil dihapus.');
    }

    protected function validateRequest(Request $request, $bidangId = null)
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kode' => ['required', 'string', 'max:100', Rule::unique('bidangs', 'kode')->ignore($bidangId)],
            'keterangan' => ['nullable', 'string'],
        ]);
    }
}
