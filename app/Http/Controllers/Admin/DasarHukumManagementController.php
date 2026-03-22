<?php

namespace App\Http\Controllers\Admin;

use App\DasarHukum;
use App\Http\Controllers\Controller;
use App\KlasifikasiKode;
use Illuminate\Http\Request;

class DasarHukumManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:super_admin']);
    }

    public function index(Request $request)
    {
        $query = DasarHukum::with('kategoriSuratKode');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($builder) use ($search) {
                $builder->where('tema', 'like', "%{$search}%")
                    ->orWhere('kata_kunci', 'like', "%{$search}%")
                    ->orWhere('uraian', 'like', "%{$search}%");
            });
        }

        if ($request->filled('aktif')) {
            $query->where('aktif', $request->aktif === '1');
        }

        $dasarHukums = $query->orderBy('urutan')->orderBy('tema')->paginate(15)->appends($request->query());
        $filters = $request->only(['search', 'aktif']);
        $kategoriOptions = KlasifikasiKode::whereDoesntHave('children')->orderBy('kode')->get();

        return view('admin.dasar-hukums.index', compact('dasarHukums', 'filters', 'kategoriOptions'));
    }

    public function create()
    {
        return redirect()->route('admin.dasar-hukums.index');
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $data['aktif'] = $request->has('aktif');
        DasarHukum::create($data);

        return redirect()->route('admin.dasar-hukums.index')->with('success', 'Dasar hukum berhasil ditambahkan.');
    }

    public function edit(DasarHukum $dasarHukum)
    {
        return redirect()->route('admin.dasar-hukums.index');
    }

    public function update(Request $request, DasarHukum $dasarHukum)
    {
        $data = $this->validateRequest($request);
        $data['aktif'] = $request->has('aktif');
        $dasarHukum->update($data);

        return redirect()->route('admin.dasar-hukums.index')->with('success', 'Dasar hukum berhasil diperbarui.');
    }

    public function destroy(DasarHukum $dasarHukum)
    {
        $dasarHukum->delete();

        return redirect()->route('admin.dasar-hukums.index')->with('success', 'Dasar hukum berhasil dihapus.');
    }

    protected function validateRequest(Request $request)
    {
        return $request->validate([
            'tema' => ['required', 'string', 'max:255'],
            'kategori_surat_kode_id' => ['nullable', 'exists:klasifikasi_kodes,id'],
            'kata_kunci' => ['nullable', 'string'],
            'uraian' => ['required', 'string'],
            'urutan' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
