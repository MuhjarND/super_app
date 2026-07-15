<?php

namespace App\Http\Controllers;

use App\SuratKeluar;
use App\KlasifikasiKode;
use App\KategoriSurat;
use App\Services\SuratTemplateDocumentService;
use App\Services\LeaveDocumentService;
use App\Services\RapatDocumentService;
use App\Services\DocumentPreviewService;
use App\Services\WhatsAppNotificationService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SuratKeluarController extends Controller
{
    protected $templateDocumentService;
    protected $documentPreviewService;
    protected $whatsAppService;

    public function __construct()
    {
        $this->middleware('auth');
        $this->templateDocumentService = app(SuratTemplateDocumentService::class);
        $this->documentPreviewService = app(DocumentPreviewService::class);
        $this->whatsAppService = app(WhatsAppNotificationService::class);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $activeFilter = in_array($request->input('filter'), ['semua', 'dibuat', 'terkait'], true)
            ? $request->input('filter')
            : 'semua';

        if (Schema::hasColumn('surat_keluar_penerima', 'read_at')) {
            DB::table('surat_keluar_penerima')
                ->whereIn('user_id', $user->effectiveAssignmentUserIds())
                ->whereNull('read_at')
                ->update(['read_at' => now('Asia/Jayapura')]);
        }

        $suratKeluarQuery = SuratKeluar::visibleTo($user);

        if ($activeFilter === 'dibuat') {
            $suratKeluarQuery->where('created_by', $user->id);
        } elseif ($activeFilter === 'terkait') {
            $suratKeluarQuery
                ->where('created_by', '!=', $user->id)
                ->whereHas('penerimaInternal', function ($recipientQuery) use ($user) {
                    $recipientQuery->whereIn('users.id', $user->effectiveAssignmentUserIds());
                });
        }

        $suratKeluar = $suratKeluarQuery
            ->with([
                'klasifikasiKode',
                'kategoriSurat',
                'kodeFungsi',
                'kodeKegiatan',
                'kodeTransaksi',
                'creator',
                'penerimaInternal.jabatan',
                'templateApproval',
                'rapat',
                'leaveRequest',
                'pdfVerifications' => function ($query) {
                    $query->whereNotNull('file_path')->where('file_path', '!=', '');
                },
            ])
            ->withCount('penerimaInternal', 'pdfVerifications')
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('surat-keluar._list', compact('suratKeluar'))->render(),
                'recipient_map' => $this->buildRecipientMap($suratKeluar),
                'filter' => $activeFilter,
            ]);
        }

        $kategoriSurats = KategoriSurat::where('aktif', true)->orderBy('kode')->get();

        $klasifikasiKodes = KlasifikasiKode::where('tipe', 'klasifikasi')
            ->orderBy('kode')
            ->get()
            ->filter(function ($item) {
                // Tampilkan hanya kode klasifikasi berbentuk huruf (contoh: KU, HK, DL)
                return (bool) preg_match('/[A-Za-z]/', (string) $item->kode);
            })
            ->values();
        $kodeFungsi = KlasifikasiKode::where('tipe', 'fungsi')->get();
        $kodeKegiatan = KlasifikasiKode::where('tipe', 'kegiatan')->get();
        $kodeTransaksi = KlasifikasiKode::where('tipe', 'transaksi')->get();
        $canCreateSuratKeluar = $user->canCreateSuratKeluar();
        $users = $canCreateSuratKeluar ? User::active()->ordered()->get() : collect();

        $kodeFungsiOptions = $kodeFungsi->map(function ($k) {
            return [
                'id' => $k->id,
                'kode' => $k->kode,
                'nama' => $k->nama,
                'parent_id' => $k->parent_id,
            ];
        })->values();
        $kodeKegiatanOptions = $kodeKegiatan->map(function ($k) {
            return [
                'id' => $k->id,
                'kode' => $k->kode,
                'nama' => $k->nama,
                'parent_id' => $k->parent_id,
            ];
        })->values();
        $kodeTransaksiOptions = $kodeTransaksi->map(function ($k) {
            return [
                'id' => $k->id,
                'kode' => $k->kode,
                'nama' => $k->nama,
                'parent_id' => $k->parent_id,
            ];
        })->values();
        $templatePrefill = session('surat_template_prefill');
        return view('surat-keluar.index', compact(
            'suratKeluar',
            'klasifikasiKodes',
            'kategoriSurats',
            'kodeFungsi',
            'kodeKegiatan',
            'kodeTransaksi',
            'users',
            'kodeFungsiOptions',
            'kodeKegiatanOptions',
            'kodeTransaksiOptions',
            'canCreateSuratKeluar',
            'activeFilter',
            'templatePrefill'
        ));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canCreateSuratKeluar(), 403);

        $request->validate([
            'tahun_surat' => 'required|integer|min:2020|max:2099',
            'klasifikasi_kode_id' => 'required|exists:klasifikasi_kodes,id',
            'kategori_surat_id' => 'nullable|exists:kategori_surats,id',
            'kode_fungsi_id' => 'nullable|exists:klasifikasi_kodes,id',
            'kode_kegiatan_id' => 'nullable|exists:klasifikasi_kodes,id',
            'kode_transaksi_id' => 'nullable|exists:klasifikasi_kodes,id',
            'nomenklatur_jabatan' => 'required|in:ketua,wakil_ketua,sekretaris,panitera',
            'opsi_penerima' => 'required|in:internal,external',
            'penerima_internal' => 'nullable|array',
            'penerima_internal.*' => Rule::exists('users', 'id')->where('status_aktif_pegawai', true),
            'penerima_external' => 'nullable|string|max:255',
            'perihal' => 'required|string',
            'tanggal_surat' => 'required|date',
            'has_lampiran' => 'nullable|in:ya,tidak',
            'template_source' => 'nullable|in:template_surat',
            'template_name' => 'nullable|string|max:255',
            'template_slug' => 'nullable|string|max:255',
            'template_rendered_body' => 'nullable|string',
            'template_field_values' => 'nullable|string',
        ]);

        $sync = $this->syncKategoriDanKlasifikasi($request->klasifikasi_kode_id, $request->kategori_surat_id);
        $request->merge([
            'klasifikasi_kode_id' => $sync['klasifikasi_kode_id'],
            'kategori_surat_id' => $sync['kategori_surat_id'],
        ]);

        $kode = $this->resolveKodeHierarchy($request);
        if (isset($kode['error'])) {
            return response()->json([
                'message' => 'Validasi kode surat gagal.',
                'errors' => [
                    $kode['field'] => [$kode['error']],
                ],
            ], 422);
        }

        $klasifikasi = $kode['klasifikasi'];
        $fungsi = $kode['fungsi'];
        $kegiatan = $kode['kegiatan'];
        $transaksi = $kode['transaksi'];

        $result = SuratKeluar::generateNomorSurat(
            $request->nomenklatur_jabatan,
            $klasifikasi->kode,
            $fungsi ? $fungsi->kode : null,
            $kegiatan ? $kegiatan->kode : null,
            $transaksi ? $transaksi->kode : null,
            $request->tahun_surat,
            date('n', strtotime($request->tanggal_surat))
        );

        $suratKeluar = SuratKeluar::create([
            'nomor_surat' => $result['nomor'],
            'nomor_urut' => $result['urut'],
            'tahun_surat' => $request->tahun_surat,
            'klasifikasi_kode_id' => $request->klasifikasi_kode_id,
            'kategori_surat_id' => $request->kategori_surat_id,
            'kode_fungsi_id' => $request->kode_fungsi_id,
            'kode_kegiatan_id' => $request->kode_kegiatan_id,
            'kode_transaksi_id' => $request->kode_transaksi_id,
            'nomenklatur_jabatan' => $request->nomenklatur_jabatan,
            'opsi_penerima' => $request->opsi_penerima,
            'penerima_external' => $request->opsi_penerima == 'external' ? $request->penerima_external : null,
            'perihal' => $request->perihal,
            'tanggal_surat' => $request->tanggal_surat,
            'has_lampiran' => $request->input('has_lampiran', 'tidak') == 'ya',
            'status' => 'draft',
            'created_by' => auth()->id(),
        ]);

        if ($request->opsi_penerima == 'internal' && $request->penerima_internal) {
            $suratKeluar->penerimaInternal()->attach($request->penerima_internal);
            $this->notifyInternalRecipients($suratKeluar, $request->penerima_internal);
        }

        if ($request->template_source === 'template_surat' && $request->filled('template_rendered_body')) {
            $this->templateDocumentService->attachGeneratedDocument($suratKeluar, [
                'template_name' => $request->template_name,
                'template_slug' => $request->template_slug,
                'rendered_body' => $request->template_rendered_body,
                'field_values' => $this->decodeTemplateFieldValues($request->template_field_values),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Surat keluar berhasil dibuat. Nomor Surat: ' . $result['nomor'],
        ]);
    }

    public function update(Request $request, SuratKeluar $suratKeluar)
    {
        abort_unless(auth()->user()->canModifySuratKeluar($suratKeluar), 403);

        $request->validate([
            'tahun_surat' => 'required|integer|min:2020|max:2099',
            'klasifikasi_kode_id' => 'required|exists:klasifikasi_kodes,id',
            'opsi_penerima' => 'required|in:internal,external',
            'kategori_surat_id' => 'nullable|exists:kategori_surats,id',
            'kode_fungsi_id' => 'nullable|exists:klasifikasi_kodes,id',
            'kode_kegiatan_id' => 'nullable|exists:klasifikasi_kodes,id',
            'kode_transaksi_id' => 'nullable|exists:klasifikasi_kodes,id',
            'nomenklatur_jabatan' => 'required|in:ketua,wakil_ketua,sekretaris,panitera',
            'penerima_internal' => 'nullable|array',
            'penerima_internal.*' => Rule::exists('users', 'id')->where('status_aktif_pegawai', true),
            'penerima_external' => 'nullable|string|max:255',
            'perihal' => 'required|string',
            'tanggal_surat' => 'required|date',
            'has_lampiran' => 'nullable|in:ya,tidak',
        ]);

        $sync = $this->syncKategoriDanKlasifikasi($request->klasifikasi_kode_id, $request->kategori_surat_id);
        $request->merge([
            'klasifikasi_kode_id' => $sync['klasifikasi_kode_id'],
            'kategori_surat_id' => $sync['kategori_surat_id'],
        ]);

        $kode = $this->resolveKodeHierarchy($request);
        if (isset($kode['error'])) {
            return response()->json([
                'message' => 'Validasi kode surat gagal.',
                'errors' => [
                    $kode['field'] => [$kode['error']],
                ],
            ], 422);
        }

        $shouldRegenerateNomor =
            (int) $suratKeluar->tahun_surat !== (int) $request->tahun_surat ||
            (string) $suratKeluar->nomenklatur_jabatan !== (string) $request->nomenklatur_jabatan ||
            (string) $suratKeluar->klasifikasi_kode_id !== (string) $request->klasifikasi_kode_id ||
            (string) $suratKeluar->kode_fungsi_id !== (string) $request->kode_fungsi_id ||
            (string) $suratKeluar->kode_kegiatan_id !== (string) $request->kode_kegiatan_id ||
            (string) $suratKeluar->kode_transaksi_id !== (string) $request->kode_transaksi_id ||
            optional($suratKeluar->tanggal_surat)->format('n') !== date('n', strtotime($request->tanggal_surat));

        $payload = [
            'tahun_surat' => $request->tahun_surat,
            'klasifikasi_kode_id' => $request->klasifikasi_kode_id,
            'kategori_surat_id' => $request->kategori_surat_id,
            'kode_fungsi_id' => $request->kode_fungsi_id,
            'kode_kegiatan_id' => $request->kode_kegiatan_id,
            'kode_transaksi_id' => $request->kode_transaksi_id,
            'nomenklatur_jabatan' => $request->nomenklatur_jabatan,
            'opsi_penerima' => $request->opsi_penerima,
            'penerima_external' => $request->opsi_penerima == 'external' ? $request->penerima_external : null,
            'perihal' => $request->perihal,
            'tanggal_surat' => $request->tanggal_surat,
            'has_lampiran' => $request->input('has_lampiran', 'tidak') == 'ya',
        ];

        if ($shouldRegenerateNomor) {
            $result = SuratKeluar::generateNomorSurat(
                $request->nomenklatur_jabatan,
                $kode['klasifikasi']->kode,
                $kode['fungsi'] ? $kode['fungsi']->kode : null,
                $kode['kegiatan'] ? $kode['kegiatan']->kode : null,
                $kode['transaksi'] ? $kode['transaksi']->kode : null,
                $request->tahun_surat,
                date('n', strtotime($request->tanggal_surat)),
                $suratKeluar->nomenklatur_jabatan === $request->nomenklatur_jabatan
                    && (int) $suratKeluar->tahun_surat === (int) $request->tahun_surat
                    ? $suratKeluar->nomor_urut
                    : null
            );

            $payload['nomor_surat'] = $result['nomor'];
            $payload['nomor_urut'] = $result['urut'];
        }

        $oldRecipientIds = $suratKeluar->penerimaInternal()->pluck('users.id')->map(function ($id) {
            return (int) $id;
        })->all();

        $suratKeluar->update($payload);

        if ($request->opsi_penerima == 'internal' && $request->penerima_internal) {
            $suratKeluar->penerimaInternal()->sync($request->penerima_internal);
            $newRecipientIds = array_values(array_unique(array_map('intval', $request->penerima_internal)));
            $this->notifyInternalRecipients($suratKeluar, array_values(array_diff($newRecipientIds, $oldRecipientIds)));
        } else {
            $suratKeluar->penerimaInternal()->detach();
        }

        return response()->json([
            'success' => true,
            'message' => 'Surat keluar berhasil diperbarui.',
        ]);
    }

    public function destroy(SuratKeluar $suratKeluar)
    {
        abort_unless(auth()->user()->canModifySuratKeluar($suratKeluar), 403);

        if ($suratKeluar->file_path) {
            Storage::disk('public')->delete($suratKeluar->file_path);
        }

        $suratKeluar->penerimaInternal()->detach();
        $suratKeluar->delete();

        return response()->json([
            'success' => true,
            'message' => 'Surat keluar berhasil dihapus.',
        ]);
    }

    public function uploadLampiran(Request $request, SuratKeluar $suratKeluar)
    {
        abort_unless(auth()->user()->canModifySuratKeluar($suratKeluar), 403);

        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,webp,txt,csv,zip,rar|max:10240',
        ]);

        if ($suratKeluar->file_path) {
            Storage::disk('public')->delete($suratKeluar->file_path);
        }

        $filePath = $request->file('file')->store('surat-keluar', 'public');

        $suratKeluar->update([
            'file_path' => $filePath,
            'status' => 'lengkap',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berkas surat keluar berhasil diunggah.',
        ]);
    }

    public function previewNomor(Request $request)
    {
        abort_unless(auth()->user()->canCreateSuratKeluar(), 403);

        $kode = $this->resolveKodeHierarchy($request);
        if (isset($kode['error'])) {
            return response()->json(['nomor' => '-']);
        }

        $result = SuratKeluar::generateNomorSurat(
            $request->nomenklatur_jabatan ?? 'ketua',
            $kode['klasifikasi']->kode,
            $kode['fungsi'] ? $kode['fungsi']->kode : null,
            $kode['kegiatan'] ? $kode['kegiatan']->kode : null,
            $kode['transaksi'] ? $kode['transaksi']->kode : null,
            $request->tahun_surat ?? date('Y')
        );

        return response()->json(['nomor' => $result['nomor']]);
    }

    public function viewFile(SuratKeluar $suratKeluar)
    {
        abort_unless(auth()->user()->canViewSuratKeluar($suratKeluar), 403);

        $suratKeluar->syncCompletionStatusFromFile();

        if ($suratKeluar->file_path) {
            return $this->documentPreviewService->streamPublicFile(
                $suratKeluar->file_path,
                $suratKeluar->nomor_surat_formatted . ' - ' . $suratKeluar->perihal
            );
        }

        $suratKeluar->loadMissing('templateApproval', 'rapat', 'leaveRequest');
        if ($suratKeluar->templateApproval) {
            return $this->templateDocumentService->streamApprovalPreview($suratKeluar->templateApproval);
        }

        if ($suratKeluar->rapat) {
            return app(RapatDocumentService::class)->streamUndanganPdf($suratKeluar->rapat);
        }

        if ($suratKeluar->leaveRequest) {
            return app(LeaveDocumentService::class)->streamDecisionLetter($suratKeluar->leaveRequest);
        }

        $verification = \App\PdfVerification::where('module', 'surat_keluar')
            ->where('document_id', (string) $suratKeluar->id)
            ->latest()
            ->first();

        if ($verification && $verification->file_path) {
            return response()->file(Storage::disk('public')->path($verification->file_path));
        }

        abort(404, 'File tidak ditemukan.');
    }

    public function arsip(Request $request)
    {
        $query = SuratKeluar::visibleTo(auth()->user())
            ->with('klasifikasiKode', 'creator', 'penerimaInternal', 'templateApproval', 'pdfVerifications', 'rapat', 'leaveRequest')
            ->where('status', 'lengkap');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_surat', 'like', "%{$search}%")
                    ->orWhere('perihal', 'like', "%{$search}%");
            });
        }

        $arsip = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('surat-keluar.arsip', compact('arsip'));
    }

    protected function resolveKodeHierarchy(Request $request)
    {
        $klasifikasi = KlasifikasiKode::where('id', $request->klasifikasi_kode_id)
            ->where('tipe', 'klasifikasi')
            ->first();

        if (!$klasifikasi) {
            return ['field' => 'klasifikasi_kode_id', 'error' => 'Kode klasifikasi tidak valid.'];
        }
        if (!preg_match('/[A-Za-z]/', (string) $klasifikasi->kode)) {
            return ['field' => 'klasifikasi_kode_id', 'error' => 'Kode klasifikasi harus menggunakan format huruf.'];
        }

        $fungsi = null;
        if ($request->filled('kode_fungsi_id')) {
            $fungsi = KlasifikasiKode::where('id', $request->kode_fungsi_id)
                ->where('tipe', 'fungsi')
                ->where('parent_id', $klasifikasi->id)
                ->first();

            if (!$fungsi) {
                return ['field' => 'kode_fungsi_id', 'error' => 'Kode fungsi tidak sesuai dengan kode klasifikasi yang dipilih.'];
            }
            if (!preg_match('/\d/', (string) $fungsi->kode)) {
                return ['field' => 'kode_fungsi_id', 'error' => 'Kode fungsi tidak mengandung angka.'];
            }
        }

        $kegiatan = null;
        if ($request->filled('kode_kegiatan_id')) {
            if (!$fungsi) {
                return ['field' => 'kode_kegiatan_id', 'error' => 'Pilih kode fungsi terlebih dahulu.'];
            }

            $kegiatan = KlasifikasiKode::where('id', $request->kode_kegiatan_id)
                ->where('tipe', 'kegiatan')
                ->where('parent_id', $fungsi->id)
                ->first();

            if (!$kegiatan) {
                return ['field' => 'kode_kegiatan_id', 'error' => 'Kode kegiatan tidak sesuai dengan kode fungsi yang dipilih.'];
            }
            if (!preg_match('/\d/', (string) $kegiatan->kode)) {
                return ['field' => 'kode_kegiatan_id', 'error' => 'Kode kegiatan tidak mengandung angka.'];
            }
        }

        $transaksi = null;
        if ($request->filled('kode_transaksi_id')) {
            if (!$kegiatan) {
                return ['field' => 'kode_transaksi_id', 'error' => 'Pilih kode kegiatan terlebih dahulu.'];
            }

            $transaksi = KlasifikasiKode::where('id', $request->kode_transaksi_id)
                ->where('tipe', 'transaksi')
                ->where('parent_id', $kegiatan->id)
                ->first();

            if (!$transaksi) {
                return ['field' => 'kode_transaksi_id', 'error' => 'Kode transaksi tidak sesuai dengan kode kegiatan yang dipilih.'];
            }
            if (!preg_match('/\d/', (string) $transaksi->kode)) {
                return ['field' => 'kode_transaksi_id', 'error' => 'Kode transaksi tidak mengandung angka.'];
            }
        }

        return [
            'klasifikasi' => $klasifikasi,
            'fungsi' => $fungsi,
            'kegiatan' => $kegiatan,
            'transaksi' => $transaksi,
        ];
    }

    protected function syncKategoriDanKlasifikasi($klasifikasiId, $kategoriId)
    {
        $klasifikasi = $this->findKlasifikasiHuruf($klasifikasiId);
        $kategori = $kategoriId ? KategoriSurat::find($kategoriId) : null;

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

    protected function buildRecipientMap($suratKeluar)
    {
        return $suratKeluar->getCollection()->mapWithKeys(function ($surat) {
            return [
                $surat->id => $surat->penerimaInternal->map(function ($user) {
                    return [
                        'name' => $user->name,
                        'jabatan' => optional($user->jabatan)->nama ?: ($user->jabatan_keterangan ?: '-'),
                    ];
                })->values(),
            ];
        })->all();
    }

    protected function decodeTemplateFieldValues($json)
    {
        if (!$json) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function notifyInternalRecipients(SuratKeluar $suratKeluar, array $recipientIds)
    {
        $recipientIds = array_values(array_unique(array_filter(array_map('intval', $recipientIds))));
        if (empty($recipientIds)) {
            return;
        }

        $suratKeluar->loadMissing('creator');
        $users = User::active()->whereIn('id', $recipientIds)->get();

        foreach ($users as $user) {
            $this->whatsAppService->notifySuratKeluarRecipient($suratKeluar, $user);
        }
    }
}
