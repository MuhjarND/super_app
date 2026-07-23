<?php

namespace App\Http\Controllers;

use App\SuratMasuk;
use App\AgendaPimpinan;
use App\VirtualMeeting;
use App\KlasifikasiKode;
use App\KategoriSurat;
use App\User;
use App\Disposisi;
use App\Services\WhatsAppNotificationService;
use App\Services\DocumentPreviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SuratMasukController extends Controller
{
    protected $waService;
    protected $documentPreviewService;

    public function __construct(WhatsAppNotificationService $waService, DocumentPreviewService $documentPreviewService)
    {
        $this->middleware('auth');
        $this->waService = $waService;
        $this->documentPreviewService = $documentPreviewService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $user->loadMissing('jabatan', 'activeJabatanDelegations.jabatan');

        $hasDelegationFilter = $user->hasActiveJabatanDelegation();
        $allowedWorkflowFilters = $hasDelegationFilter
            ? ['all', 'direct', 'delegated']
            : ['all', 'disposition', 'follow_up'];
        $defaultWorkflowFilter = 'all';
        $workflowFilter = in_array($request->input('workflow'), $allowedWorkflowFilters, true)
            ? $request->input('workflow')
            : $defaultWorkflowFilter;
        $search = trim((string) $request->input('search', ''));
        $visibleQuery = SuratMasuk::visibleTo($user);
        $notificationYear = now('Asia/Jayapura')->year;

        if ($hasDelegationFilter) {
            $currentYearVisibleQuery = (clone $visibleQuery)->forLetterYear($notificationYear);
            $workflowCounts = [
                'all' => (clone $currentYearVisibleQuery)->count(),
                'direct' => $this->applyDelegationScopeFilter(clone $currentYearVisibleQuery, $user, 'direct')->count(),
                'delegated' => $this->applyDelegationScopeFilter(clone $currentYearVisibleQuery, $user, 'delegated')->count(),
            ];
        } else {
            $workflowCounts = [
                'disposition' => $this->applyNeedsDispositionFilter(
                    (clone $visibleQuery)->forLetterYear($notificationYear),
                    $user
                )->count(),
                'follow_up' => $this->applyNeedsFollowUpFilter(
                    (clone $visibleQuery)->forLetterYear($notificationYear),
                    $user
                )->count(),
            ];
        }

        $suratQuery = clone $visibleQuery;
        if ($hasDelegationFilter) {
            $suratQuery = $this->applyDelegationScopeFilter($suratQuery, $user, $workflowFilter);
        } elseif ($workflowFilter === 'disposition') {
            $suratQuery = $this->applyNeedsDispositionFilter(
                $suratQuery->forLetterYear($notificationYear),
                $user
            );
        } elseif ($workflowFilter === 'follow_up') {
            $suratQuery = $this->applyNeedsFollowUpFilter(
                $suratQuery->forLetterYear($notificationYear),
                $user
            );
        }

        if ($search !== '') {
            $suratQuery = $this->applySearchFilter($suratQuery, $search);
        }

        $suratMasuk = $suratQuery
            ->with('klasifikasiKode', 'kategoriSurat', 'creator', 'agendaPimpinan.recipients', 'virtualMeeting.participants', 'disposisis.dariUser', 'disposisis.kepadaUser.jabatan', 'disposisis.kepadaJabatan.users', 'disposisis.dokumentasis')
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();
        $klasifikasiKodes = $this->getKlasifikasiHuruf();
        $kategoriSurats = KategoriSurat::where('aktif', true)->orderBy('kode')->get();
        $petunjukOptions = $this->getPetunjukOptions();
        $virtualMeetingUsers = User::with('jabatan')->active()->ordered()->get();
        $delegationFilterLabel = 'Sebagai ' . $user->activeJabatanDelegations
            ->pluck('delegation_type')
            ->filter()
            ->map(function ($type) {
                return strtoupper((string) $type);
            })
            ->unique()
            ->implode(' / ');
        $suratHistories = $suratMasuk->getCollection()->mapWithKeys(function ($surat) use ($user) {
            $items = $surat->disposisis
                ->sortByDesc('created_at')
                ->values()
                ->map(function ($disposisi) use ($user) {
                    $assignmentContext = $disposisi->assignmentContextFor($user);

                    return [
                        'tipe_badge' => $disposisi->tipe_badge,
                        'status_badge' => $disposisi->status_badge,
                        'dari' => optional($disposisi->dariUser)->name,
                        'kepada' => optional($disposisi->kepadaUser)->name,
                        'jabatan' => optional($disposisi->kepadaJabatan)->nama,
                        'petunjuk' => $disposisi->petunjuk,
                        'catatan' => $disposisi->catatan,
                        'catatan_tindak_lanjut' => $disposisi->catatan_tindak_lanjut,
                        'tautan_tindak_lanjut' => $disposisi->tautan_tindak_lanjut,
                        'dokumentasi' => $disposisi->dokumentasis->map(function ($dokumentasi) {
                            return [
                                'nama' => $dokumentasi->original_name,
                                'ukuran' => $dokumentasi->formatted_size,
                                'preview_url' => route('disposisi.dokumentasi.preview', $dokumentasi),
                                'download_url' => route('disposisi.dokumentasi.download', $dokumentasi),
                            ];
                        })->values(),
                        'priority_badge' => $disposisi->priority_badge,
                        'target_label' => $disposisi->target_label,
                        'waktu' => $disposisi->created_at->format('d/m/Y H:i'),
                        'waktu_human' => $disposisi->created_at->diffForHumans(),
                        'assignment_context' => $assignmentContext,
                    ];
                });

            return [$surat->id => $items];
        });

        if ($request->ajax()) {
            return response()->json([
                'html' => view('surat-masuk._list', compact('suratMasuk'))->render(),
                'histories' => $suratHistories->all(),
                'workflow' => $workflowFilter,
                'search' => $search,
            ]);
        }

        return view('surat-masuk.index', compact(
            'suratMasuk',
            'klasifikasiKodes',
            'kategoriSurats',
            'petunjukOptions',
            'suratHistories',
            'virtualMeetingUsers',
            'workflowFilter',
            'workflowCounts',
            'hasDelegationFilter',
            'delegationFilterLabel',
            'notificationYear',
            'search'
        ));
    }

    protected function applyDelegationScopeFilter($query, User $user, $scope)
    {
        if ($scope === 'all') {
            return $query;
        }

        $delegatedJabatanIds = $user->activeJabatanDelegations
            ->pluck('jabatan_id')
            ->filter()
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values()
            ->all();

        if (empty($delegatedJabatanIds)) {
            return $scope === 'delegated' ? $query->whereRaw('1 = 0') : $query;
        }

        $definitiveUserIds = User::active()
            ->whereIn('jabatan_id', $delegatedJabatanIds)
            ->where('id', '!=', $user->id)
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();

        $delegatedTarget = function ($disposisiQuery) use ($delegatedJabatanIds, $definitiveUserIds, $user) {
            $disposisiQuery->where(function ($targetQuery) use ($delegatedJabatanIds, $definitiveUserIds, $user) {
                if (!empty($definitiveUserIds)) {
                    $targetQuery->whereIn('kepada_user_id', $definitiveUserIds);
                } else {
                    $targetQuery->whereRaw('1 = 0');
                }

                $targetQuery->orWhere(function ($jabatanTargetQuery) use ($delegatedJabatanIds, $user) {
                    $jabatanTargetQuery
                        ->whereIn('kepada_jabatan_id', $delegatedJabatanIds)
                        ->where(function ($recipientQuery) use ($user) {
                            $recipientQuery->whereNull('kepada_user_id')
                                ->orWhere('kepada_user_id', '!=', $user->id);
                        });
                });
            });
        };

        $directTarget = function ($disposisiQuery) use ($user) {
            $disposisiQuery->where('kepada_user_id', $user->id);
        };

        if ($scope === 'delegated') {
            return $query
                ->whereHas('disposisis', $delegatedTarget)
                ->whereDoesntHave('disposisis', $directTarget);
        }

        return $query->whereHas('disposisis', $directTarget);
    }

    protected function applySearchFilter($query, $search)
    {
        $like = '%' . $search . '%';

        return $query->where(function ($builder) use ($like) {
            $builder->where('nomor_surat', 'like', $like)
                ->orWhere('pengirim', 'like', $like)
                ->orWhere('perihal', 'like', $like)
                ->orWhere('status', 'like', $like)
                ->orWhere('sifat', 'like', $like)
                ->orWhere('tanggal_surat', 'like', $like)
                ->orWhere('created_at', 'like', $like)
                ->orWhereHas('creator', function ($creatorQuery) use ($like) {
                    $creatorQuery->where('name', 'like', $like);
                })
                ->orWhereHas('klasifikasiKode', function ($classificationQuery) use ($like) {
                    $classificationQuery->where('kode', 'like', $like)
                        ->orWhere('nama', 'like', $like);
                });
        });
    }

    protected function applyNeedsDispositionFilter($query, User $user)
    {
        if ($user->isSuperAdmin()) {
            return $query->where('status', '!=', 'selesai');
        }

        if (empty($user->targetDisposisiJabatanIds())) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($builder) use ($user) {
            if ($user->canManageInitialSuratMasuk()) {
                $builder->where('status', 'baru');
            }

            $method = $user->canManageInitialSuratMasuk() ? 'orWhere' : 'where';
            $builder->{$method}(function ($pendingQuery) use ($user) {
                $pendingQuery->where('status', 'didisposisi')
                    ->whereHas('disposisis', function ($disposisiQuery) use ($user) {
                        $disposisiQuery->where('status', 'pending')->addressedToUser($user);
                    });
            });
        });
    }

    protected function applyNeedsFollowUpFilter($query, User $user)
    {
        if (!$user->isSuperAdmin() && !$user->isKabagAtauKasubag()) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->where('status', 'didisposisi')
            ->whereHas('disposisis', function ($disposisiQuery) use ($user) {
                $disposisiQuery->where('status', 'pending')->addressedToUser($user);
            });
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canCreateSuratMasuk(), 403);

        $request->validate([
            'nomor_surat' => 'required|string|max:255',
            'opsi_pengirim' => 'required|in:mahkamah_agung,non_mahkamah_agung',
            'klasifikasi_kode_id' => 'required_if:opsi_pengirim,mahkamah_agung|nullable|exists:klasifikasi_kodes,id',
            'kategori_surat_id' => 'nullable|exists:kategori_surats,id',
            'pengirim' => 'required|string|max:255',
            'perihal' => 'required|string',
            'tanggal_surat' => 'required|date',
            'sifat' => 'required|in:biasa,rahasia,sangat_rahasia',
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'agenda_pimpinan' => 'nullable|boolean',
            'agenda_tanggal_kegiatan' => 'required_if:agenda_pimpinan,1|nullable|date',
            'agenda_waktu' => 'required_if:agenda_pimpinan,1|nullable|date_format:H:i',
            'agenda_tempat' => 'required_if:agenda_pimpinan,1|nullable|string|max:255',
            'agenda_virtual' => 'nullable|boolean',
            'virtual_tanggal_kegiatan' => 'required_if:agenda_virtual,1|nullable|date',
            'virtual_waktu_mulai' => 'required_if:agenda_virtual,1|nullable|date_format:H:i',
            'virtual_waktu_selesai' => 'nullable|date_format:H:i|after:virtual_waktu_mulai',
            'virtual_zoom_link' => 'required_if:agenda_virtual,1|nullable|url|max:2000',
            'virtual_participant_ids' => 'required_if:agenda_virtual,1|nullable|array|min:1',
            'virtual_participant_ids.*' => 'exists:users,id',
        ]);

        $sync = $this->syncKategoriDanKlasifikasi(
            $request->opsi_pengirim,
            $request->klasifikasi_kode_id,
            $request->kategori_surat_id
        );

        $filePath = $request->file('file')->store('surat-masuk', 'public');

        $agenda = null;
        $virtualMeeting = null;

        try {
            DB::beginTransaction();

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

            if ($request->boolean('agenda_pimpinan')) {
                $agenda = $this->createAgendaPimpinanFromSuratMasuk($suratMasuk, $request);
            }

            if ($request->boolean('agenda_virtual')) {
                $virtualMeeting = $this->createVirtualMeetingFromSuratMasuk($suratMasuk, $request);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Storage::disk('public')->delete($filePath);
            throw $e;
        }

        // Send WA notification to admin surat
        $adminSurat = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin_surat');
        })->active()->get();

        foreach ($adminSurat as $admin) {
            $this->waService->notifySuratMasuk($suratMasuk, $admin);
        }

        if ($agenda) {
            $this->notifyProtokolerAgendaPimpinan($agenda);
        }

        if ($virtualMeeting) {
            $this->waService->notifyVirtualMeetingParticipants($virtualMeeting->load('participants.jabatan'));
        }

        $createdItems = array_filter([
            $agenda ? 'agenda pimpinan' : null,
            $virtualMeeting ? 'agenda virtual' : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => $createdItems
                ? 'Surat masuk dan ' . implode(' serta ', $createdItems) . ' berhasil disimpan.'
                : 'Surat masuk berhasil disimpan.',
        ]);
    }

    public function show(SuratMasuk $suratMasuk)
    {
        $suratMasuk->load('klasifikasiKode', 'kategoriSurat', 'creator', 'agendaPimpinan.recipients', 'virtualMeeting.participants', 'disposisis.dariUser', 'disposisis.kepadaUser.jabatan', 'disposisis.kepadaJabatan.users', 'disposisis.dokumentasis');

        $user = auth()->user();
        $user->loadMissing('jabatan', 'activeJabatanDelegations.jabatan');
        abort_unless($user->canViewSuratMasuk($suratMasuk), 403);

        $suratMasuk->disposisis()
            ->addressedToUser($user)
            ->whereNull('read_at')
            ->update(['read_at' => now('Asia/Jayapura')]);

        $canDisposisi = $user->canForwardSuratMasuk($suratMasuk);
        $targetDisposisi = [];
        $showPetunjuk = $user->requiresPetunjukDisposisi();
        $canNaikanSurat = $user->canNaikanSuratMasuk();
        $canEdit = $user->canEditSuratMasuk($suratMasuk);
        $canDelete = $user->canDeleteSuratMasuk($suratMasuk);
        $assignmentContext = $suratMasuk->assignmentContextFor($user);

        if ($canDisposisi) {
            $targetIds = $user->targetDisposisiJabatanIds();
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
            'canNaikanSurat',
            'canEdit',
            'canDelete',
            'assignmentContext'
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

    protected function createAgendaPimpinanFromSuratMasuk(SuratMasuk $suratMasuk, Request $request)
    {
        return AgendaPimpinan::create([
            'surat_masuk_id' => $suratMasuk->id,
            'tanggal_kegiatan' => $request->agenda_tanggal_kegiatan,
            'judul_agenda' => $suratMasuk->perihal,
            'tempat' => $request->agenda_tempat,
            'waktu' => $request->agenda_waktu,
            'nomor_naskah_dinas' => $suratMasuk->nomor_surat,
            'lampiran_link' => route('surat-masuk.preview', $suratMasuk),
            'catatan' => 'Agenda dibuat otomatis dari input Surat Masuk.',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);
    }

    protected function notifyProtokolerAgendaPimpinan(AgendaPimpinan $agenda)
    {
        $protokolerUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'protokoler');
        })->active()->get();

        $agenda->loadMissing('suratMasuk');

        foreach ($protokolerUsers as $protokoler) {
            $this->waService->notifyAgendaPimpinanCreatedForProtokoler($agenda, $protokoler);
        }
    }

    protected function createVirtualMeetingFromSuratMasuk(SuratMasuk $suratMasuk, Request $request)
    {
        $meeting = VirtualMeeting::create([
            'surat_masuk_id' => $suratMasuk->id,
            'judul' => $suratMasuk->perihal,
            'tanggal_kegiatan' => $request->virtual_tanggal_kegiatan,
            'waktu_mulai' => $request->virtual_waktu_mulai,
            'waktu_selesai' => $request->virtual_waktu_selesai,
            'zoom_link' => $request->virtual_zoom_link,
            'catatan' => 'Agenda virtual dibuat otomatis dari Surat Masuk ' . $suratMasuk->nomor_surat . '.',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $participants = User::whereIn('id', array_unique((array) $request->virtual_participant_ids))
            ->active()
            ->ordered()
            ->get();
        $syncData = [];
        foreach ($participants as $index => $participant) {
            $syncData[$participant->id] = ['urutan' => $index + 1];
        }
        $meeting->participants()->sync($syncData);

        return $meeting;
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
            'klasifikasi_kode_id' => 'required_if:opsi_pengirim,mahkamah_agung|nullable|exists:klasifikasi_kodes,id',
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
        $suratMasuk->agendaPimpinan()->update([
            'judul_agenda' => $suratMasuk->perihal,
            'nomor_naskah_dinas' => $suratMasuk->nomor_surat,
            'updated_by' => auth()->id(),
        ]);
        $suratMasuk->virtualMeeting()->update([
            'judul' => $suratMasuk->perihal,
            'updated_by' => auth()->id(),
        ]);

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

        DB::transaction(function () use ($suratMasuk) {
            $suratMasuk->agendaPimpinan()->delete();
            $suratMasuk->virtualMeeting()->delete();
            $suratMasuk->disposisis()->delete();
            $suratMasuk->delete();
        });

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
        return $this->documentPreviewService->streamPublicFile(
            $suratMasuk->file_path,
            $suratMasuk->nomor_surat . ' - ' . $suratMasuk->perihal,
            route('surat-masuk.download', $suratMasuk)
        );
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
            return [
                'klasifikasi_kode_id' => null,
                'kategori_surat_id' => null,
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
