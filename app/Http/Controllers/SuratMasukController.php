<?php

namespace App\Http\Controllers;

use App\SuratMasuk;
use App\KlasifikasiKode;
use App\KategoriSurat;
use App\User;
use App\Disposisi;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SuratMasukController extends Controller
{
    protected $waService;

    public function __construct(WhatsAppNotificationService $waService)
    {
        $this->middleware('auth');
        $this->waService = $waService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $suratMasuk = SuratMasuk::visibleTo($user)
            ->with('klasifikasiKode', 'kategoriSurat', 'creator', 'disposisis.dariUser', 'disposisis.kepadaUser', 'disposisis.kepadaJabatan')
            ->orderBy('created_at', 'desc')
            ->get();
        $klasifikasiKodes = $this->getKlasifikasiHuruf();
        $kategoriSurats = KategoriSurat::where('aktif', true)->orderBy('kode')->get();
        $petunjukOptions = $this->getPetunjukOptions();
        $suratHistories = $suratMasuk->mapWithKeys(function ($surat) {
            $items = $surat->disposisis
                ->sortByDesc('created_at')
                ->values()
                ->map(function ($disposisi) {
                    return [
                        'tipe_badge' => $disposisi->tipe_badge,
                        'status_badge' => $disposisi->status_badge,
                        'dari' => optional($disposisi->dariUser)->name,
                        'kepada' => optional($disposisi->kepadaUser)->name,
                        'jabatan' => optional($disposisi->kepadaJabatan)->nama,
                        'petunjuk' => $disposisi->petunjuk,
                        'catatan' => $disposisi->catatan,
                        'catatan_tindak_lanjut' => $disposisi->catatan_tindak_lanjut,
                        'waktu' => $disposisi->created_at->format('d/m/Y H:i'),
                        'waktu_human' => $disposisi->created_at->diffForHumans(),
                    ];
                });

            return [$surat->id => $items];
        });

        return view('surat-masuk.index', compact(
            'suratMasuk',
            'klasifikasiKodes',
            'kategoriSurats',
            'petunjukOptions',
            'suratHistories'
        ));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canCreateSuratMasuk(), 403);

        $request->validate([
            'nomor_surat' => 'required|string|max:255',
            'opsi_pengirim' => 'required|in:mahkamah_agung,non_mahkamah_agung',
            'klasifikasi_kode_id' => 'nullable|exists:klasifikasi_kodes,id',
            'kategori_surat_id' => 'nullable|exists:kategori_surats,id',
            'pengirim' => 'required|string|max:255',
            'perihal' => 'required|string',
            'tanggal_surat' => 'required|date',
            'sifat' => 'required|in:biasa,rahasia,sangat_rahasia',
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $sync = $this->syncKategoriDanKlasifikasi(
            $request->opsi_pengirim,
            $request->klasifikasi_kode_id,
            $request->kategori_surat_id
        );

        $filePath = $request->file('file')->store('surat-masuk', 'public');

        $suratMasuk = SuratMasuk::create([
            'nomor_surat' => $request->nomor_surat,
            'opsi_pengirim' => $request->opsi_pengirim,
            'klasifikasi_kode_id' => $sync['klasifikasi_kode_id'],
            'kategori_surat_id' => $sync['kategori_surat_id'],
            'pengirim' => $request->pengirim,
            'perihal' => $request->perihal,
            'tanggal_surat' => $request->tanggal_surat,
            'sifat' => $request->sifat,
            'file_path' => $filePath,
            'status' => 'baru',
            'created_by' => auth()->id(),
        ]);

        // Send WA notification to admin surat
        $adminSurat = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin_surat');
        })->get();

        foreach ($adminSurat as $admin) {
            $this->waService->notifySuratMasuk($suratMasuk, $admin);
        }

        return response()->json([
            'success' => true,
            'message' => 'Surat masuk berhasil disimpan.',
        ]);
    }

    public function show(SuratMasuk $suratMasuk)
    {
        $suratMasuk->load('klasifikasiKode', 'kategoriSurat', 'creator', 'disposisis.dariUser', 'disposisis.kepadaUser', 'disposisis.kepadaJabatan');

        $user = auth()->user();
        abort_unless($user->canViewSuratMasuk($suratMasuk), 403);

        $canDisposisi = $user->canForwardSuratMasuk($suratMasuk);
        $targetDisposisi = [];
        $showPetunjuk = $user->requiresPetunjukDisposisi();
        $canEdit = $user->canEditSuratMasuk($suratMasuk);
        $canDelete = $user->canDeleteSuratMasuk($suratMasuk);

        if ($canDisposisi && $user->jabatan) {
            $targetIds = $user->jabatan->getTargetDisposisi();
            if (!empty($targetIds)) {
                $targetDisposisi = \App\Jabatan::whereIn('id', $targetIds)->with('users')->get();
            }
        }

        $petunjukOptions = $this->getPetunjukOptions();

        return view('surat-masuk.show', compact(
            'suratMasuk',
            'canDisposisi',
            'targetDisposisi',
            'petunjukOptions',
            'showPetunjuk',
            'canEdit',
            'canDelete'
        ));
    }

    public function getKlasifikasi()
    {
        $klasifikasi = $this->getKlasifikasiHuruf()->map(function ($item) {
            return [
                'id' => $item->id,
                'kode' => $item->kode,
                'nama' => $item->nama,
            ];
        })->values();

        return response()->json($klasifikasi);
    }

    protected function getKlasifikasiHuruf()
    {
        return KlasifikasiKode::where('tipe', 'klasifikasi')
            ->orderBy('kode')
            ->get()
            ->filter(function ($item) {
                return (bool) preg_match('/[A-Za-z]/', (string) $item->kode);
            })
            ->values();
    }

    protected function getPetunjukOptions()
    {
        return collect(Disposisi::getPetunjukOptions());
    }

    public function update(Request $request, SuratMasuk $suratMasuk)
    {
        if (!auth()->user()->canEditSuratMasuk($suratMasuk)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengedit surat ini.',
            ], 403);
        }

        $request->validate([
            'nomor_surat' => 'required|string|max:255',
            'opsi_pengirim' => 'required|in:mahkamah_agung,non_mahkamah_agung',
            'klasifikasi_kode_id' => 'nullable|exists:klasifikasi_kodes,id',
            'kategori_surat_id' => 'nullable|exists:kategori_surats,id',
            'pengirim' => 'required|string|max:255',
            'perihal' => 'required|string',
            'tanggal_surat' => 'required|date',
            'sifat' => 'required|in:biasa,rahasia,sangat_rahasia',
            'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $sync = $this->syncKategoriDanKlasifikasi(
            $request->opsi_pengirim,
            $request->klasifikasi_kode_id,
            $request->kategori_surat_id
        );

        $data = [
            'nomor_surat' => $request->nomor_surat,
            'opsi_pengirim' => $request->opsi_pengirim,
            'klasifikasi_kode_id' => $sync['klasifikasi_kode_id'],
            'kategori_surat_id' => $sync['kategori_surat_id'],
            'pengirim' => $request->pengirim,
            'perihal' => $request->perihal,
            'tanggal_surat' => $request->tanggal_surat,
            'sifat' => $request->sifat,
        ];

        if ($request->hasFile('file')) {
            if ($suratMasuk->file_path) {
                Storage::disk('public')->delete($suratMasuk->file_path);
            }
            $data['file_path'] = $request->file('file')->store('surat-masuk', 'public');
        }

        $suratMasuk->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Surat masuk berhasil diperbarui.',
        ]);
    }

    public function destroy(SuratMasuk $suratMasuk)
    {
        if (!auth()->user()->canDeleteSuratMasuk($suratMasuk)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus surat ini.',
            ], 403);
        }

        if ($suratMasuk->file_path) {
            Storage::disk('public')->delete($suratMasuk->file_path);
        }

        $suratMasuk->disposisis()->delete();
        $suratMasuk->delete();

        return response()->json([
            'success' => true,
            'message' => 'Surat masuk berhasil dihapus.',
        ]);
    }

    public function previewFile(SuratMasuk $suratMasuk)
    {
        abort_unless(auth()->user()->canViewSuratMasuk($suratMasuk), 403);

        if (!$suratMasuk->file_path) {
            abort(404, 'File tidak ditemukan.');
        }
        return response()->file(storage_path('app/public/' . $suratMasuk->file_path));
    }

    public function downloadFile(SuratMasuk $suratMasuk)
    {
        abort_unless(auth()->user()->canViewSuratMasuk($suratMasuk), 403);

        return Storage::disk('public')->download($suratMasuk->file_path);
    }

    protected function syncKategoriDanKlasifikasi($opsiPengirim, $klasifikasiId, $kategoriId)
    {
        $klasifikasi = $this->findKlasifikasiHuruf($klasifikasiId);
        $kategori = $kategoriId ? KategoriSurat::find($kategoriId) : null;

        if ($opsiPengirim !== 'mahkamah_agung') {
            if (!$kategori && $klasifikasi) {
                $kategori = $this->findKategoriByKode($klasifikasi->kode);
            }

            return [
                'klasifikasi_kode_id' => null,
                'kategori_surat_id' => optional($kategori)->id,
            ];
        }

        if ($klasifikasi) {
            $kategori = $this->findKategoriByKode($klasifikasi->kode) ?: $kategori;
        } elseif ($kategori) {
            $klasifikasi = $this->findKlasifikasiByKode($kategori->kode);
        }

        return [
            'klasifikasi_kode_id' => optional($klasifikasi)->id,
            'kategori_surat_id' => optional($kategori)->id,
        ];
    }

    protected function findKlasifikasiHuruf($id)
    {
        if (!$id) {
            return null;
        }

        $klasifikasi = KlasifikasiKode::where('tipe', 'klasifikasi')->where('id', $id)->first();

        if (!$klasifikasi || !preg_match('/[A-Za-z]/', (string) $klasifikasi->kode)) {
            return null;
        }

        return $klasifikasi;
    }

    protected function findKlasifikasiByKode($kode)
    {
        if (!$kode) {
            return null;
        }

        return KlasifikasiKode::where('tipe', 'klasifikasi')
            ->whereRaw('UPPER(kode) = ?', [strtoupper(trim((string) $kode))])
            ->first();
    }

    protected function findKategoriByKode($kode)
    {
        if (!$kode) {
            return null;
        }

        return KategoriSurat::whereRaw('UPPER(kode) = ?', [strtoupper(trim((string) $kode))])->first();
    }
}
