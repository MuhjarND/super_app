<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\KategoriSurat;
use App\KlasifikasiKode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriSuratManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:super_admin']);
    }

    public function index(Request $request)
    {
        $query = KategoriSurat::query();

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

        $kategoriSurats = $query->orderBy('kode')->paginate(15)->appends($request->query());
        $filters = $request->only(['search', 'aktif']);

        return view('admin.kategori-surats.index', compact('kategoriSurats', 'filters'));
    }

    public function create()
    {
        return redirect()->route('admin.kategori-surats.index');
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);
        $data['aktif'] = $request->has('aktif');
        $kategoriSurat = KategoriSurat::create($data);
        $this->syncKlasifikasiWithKategori($kategoriSurat);

        return redirect()->route('admin.kategori-surats.index')->with('success', 'Kategori surat berhasil ditambahkan.');
    }

    public function edit(KategoriSurat $kategoriSurat)
    {
        return redirect()->route('admin.kategori-surats.index');
    }

    public function update(Request $request, KategoriSurat $kategoriSurat)
    {
        $oldKode = $kategoriSurat->kode;
        $data = $this->validateRequest($request, $kategoriSurat->id);
        $data['aktif'] = $request->has('aktif');
        $kategoriSurat->update($data);
        $this->syncKlasifikasiWithKategori($kategoriSurat, $oldKode);

        return redirect()->route('admin.kategori-surats.index')->with('success', 'Kategori surat berhasil diperbarui.');
    }

    public function destroy(KategoriSurat $kategoriSurat)
    {
        if ($kategoriSurat->suratMasuks()->exists() || $kategoriSurat->suratKeluars()->exists()) {
            return redirect()->route('admin.kategori-surats.index')->with('error', 'Kategori surat masih dipakai oleh data surat.');
        }

        $kode = $kategoriSurat->kode;
        $kategoriSurat->delete();
        $this->deleteSyncedKlasifikasiIfSafe($kode);

        return redirect()->route('admin.kategori-surats.index')->with('success', 'Kategori surat berhasil dihapus.');
    }

    protected function validateRequest(Request $request, $kategoriId = null)
    {
        return $request->validate([
            'kode' => ['required', 'string', 'max:100', Rule::unique('kategori_surats', 'kode')->ignore($kategoriId)],
            'nama' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
        ]);
    }

    protected function syncKlasifikasiWithKategori(KategoriSurat $kategoriSurat, $oldKode = null)
    {
        $kodeBaru = strtoupper(trim((string) $kategoriSurat->kode));
        if (!preg_match('/[A-Z]/', $kodeBaru)) {
            return;
        }

        $query = KlasifikasiKode::where('tipe', 'klasifikasi')->whereNull('parent_id');
        $klasifikasi = null;

        if ($oldKode && strtoupper(trim((string) $oldKode)) !== $kodeBaru) {
            $klasifikasi = (clone $query)->whereRaw('UPPER(kode) = ?', [strtoupper(trim((string) $oldKode))])->first();
        }

        if (!$klasifikasi) {
            $klasifikasi = (clone $query)->whereRaw('UPPER(kode) = ?', [$kodeBaru])->first();
        }

        if (!$klasifikasi) {
            $klasifikasi = new KlasifikasiKode([
                'tipe' => 'klasifikasi',
                'parent_id' => null,
            ]);
        }

        $klasifikasi->kode = $kategoriSurat->kode;
        $klasifikasi->nama = $kategoriSurat->nama;
        $klasifikasi->tipe = 'klasifikasi';
        $klasifikasi->parent_id = null;
        $klasifikasi->save();
    }

    protected function deleteSyncedKlasifikasiIfSafe($kode)
    {
        if (!$kode) {
            return;
        }

        $klasifikasi = KlasifikasiKode::where('tipe', 'klasifikasi')
            ->whereNull('parent_id')
            ->whereRaw('UPPER(kode) = ?', [strtoupper(trim((string) $kode))])
            ->first();

        if (!$klasifikasi) {
            return;
        }

        $hasChildren = KlasifikasiKode::where('parent_id', $klasifikasi->id)->exists();
        $hasSuratMasuk = \App\SuratMasuk::where('klasifikasi_kode_id', $klasifikasi->id)->exists();
        $hasSuratKeluar = \App\SuratKeluar::where('klasifikasi_kode_id', $klasifikasi->id)->exists();

        if (!$hasChildren && !$hasSuratMasuk && !$hasSuratKeluar) {
            $klasifikasi->delete();
        }
    }
}
