<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\KategoriRapat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriRapatManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:super_admin']);
    }

    public function index(Request $request)
    {
        $query = KategoriRapat::query();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('kode', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        if ($request->filled('aktif')) {
            $query->where('aktif', $request->aktif === '1');
        }

        if ($request->filled('butuh_pakaian')) {
            $query->where('butuh_pakaian', $request->butuh_pakaian === '1');
        }

        $kategoriRapats = $query->orderBy('kode')->paginate(15)->appends($request->query());
        $filters = $request->only(['search', 'aktif', 'butuh_pakaian']);

        return view('admin.kategori-rapats.index', compact('kategoriRapats', 'filters'));
    }

    public function create()
    {
        return redirect()->route('admin.kategori-rapats.index');
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $data['aktif'] = $request->has('aktif');
        $data['butuh_pakaian'] = $request->has('butuh_pakaian');
        KategoriRapat::create($data);

        return redirect()->route('admin.kategori-rapats.index')->with('success', 'Kategori rapat berhasil ditambahkan.');
    }

    public function edit(KategoriRapat $kategoriRapat)
    {
        return redirect()->route('admin.kategori-rapats.index');
    }

    public function update(Request $request, KategoriRapat $kategoriRapat)
    {
        $data = $this->validateRequest($request, $kategoriRapat->id);
        $data['aktif'] = $request->has('aktif');
        $data['butuh_pakaian'] = $request->has('butuh_pakaian');
        $kategoriRapat->update($data);

        return redirect()->route('admin.kategori-rapats.index')->with('success', 'Kategori rapat berhasil diperbarui.');
    }

    public function destroy(KategoriRapat $kategoriRapat)
    {
        $kategoriRapat->delete();

        return redirect()->route('admin.kategori-rapats.index')->with('success', 'Kategori rapat berhasil dihapus.');
    }

    protected function validateRequest(Request $request, $kategoriId = null)
    {
        return $request->validate([
            'kode' => ['required', 'string', 'max:100', Rule::unique('kategori_rapats', 'kode')->ignore($kategoriId)],
            'nama' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ]);
    }
}
