<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRapatNotulensiRequest;
use App\Http\Requests\UploadRapatNotulensiRequest;
use App\Rapat;
use App\RapatNotulensi;
use App\RapatNotulensiTindakLanjut;
use App\Services\RapatDocumentService;
use App\Services\RapatNotulensiApprovalService;
use App\Services\SignaturePadService;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RapatNotulensiController extends Controller
{
    protected $notulensiApprovalService;
    protected $signaturePadService;

    public function __construct(RapatNotulensiApprovalService $notulensiApprovalService, SignaturePadService $signaturePadService)
    {
        $this->middleware('auth');
        $this->notulensiApprovalService = $notulensiApprovalService;
        $this->signaturePadService = $signaturePadService;
    }

    public function index()
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $rapats = Rapat::with([
                'kategoriSuratKode',
                'creator',
                'pesertas',
                'notulensi.notulis',
                'notulensi.tindakLanjuts.user',
            ])
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu_mulai')
            ->get();

        $belumAda = $rapats->filter(function ($rapat) {
            return !$rapat->notulensi;
        })->values();

        $sudahAda = $rapats->filter(function ($rapat) {
            return (bool) $rapat->notulensi;
        })->values();

        return view('rapat.notulensi.index', compact('belumAda', 'sudahAda'));
    }

    public function create(Rapat $rapat)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        if ($rapat->notulensi) {
            return redirect()->route('rapat.notulensi.edit', $rapat->notulensi);
        }

        $rapat->loadMissing('kategoriSuratKode', 'creator', 'pesertas.jabatan');

        return view('rapat.notulensi.form', [
            'rapat' => $rapat,
            'notulensi' => new RapatNotulensi([
                'mode' => 'template_a',
                'judul' => $rapat->judul,
                'uraian_kegiatan' => $this->buildDefaultUraianKegiatan($rapat),
                'rekomendasi_items' => [],
            ]),
            'formAction' => route('rapat.notulensi.store', $rapat),
            'formMethod' => 'POST',
            'pageTitle' => 'Buat Notulensi',
        ]);
    }

    public function store(StoreRapatNotulensiRequest $request, Rapat $rapat)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        if ($rapat->notulensi) {
            return redirect()->route('rapat.notulensi.edit', $rapat->notulensi)
                ->with('success', 'Notulensi untuk agenda ini sudah ada. Silakan edit data yang tersedia.');
        }

        $rapat->loadMissing('pesertas.jabatan', 'kategoriSuratKode', 'creator', 'approver1', 'approver2');
        $data = $request->validated();
        $recommendationItems = $this->normalizeRecommendationItems($rapat, $data['rekomendasi_items'] ?? []);

        $notulensi = DB::transaction(function () use ($rapat, $data, $request, $recommendationItems) {
            $signature = $this->signaturePadService->resolveForUser(auth()->user(), 'rapat/notulis-signatures', $data['signature_data'] ?? null);
            $notulensi = RapatNotulensi::create([
                'rapat_id' => $rapat->id,
                'notulis_id' => auth()->id(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'mode' => 'template_a',
                'status' => 'pending_approval',
                'judul' => $data['judul'] ?: $rapat->judul,
                'uraian_kegiatan' => $data['uraian_kegiatan'] ?: $this->buildDefaultUraianKegiatan($rapat),
                'agenda_rapat' => $data['agenda_rapat'],
                'susunan_agenda' => $data['susunan_agenda'] ?? null,
                'hasil_rapat' => $data['hasil_rapat'],
                'rekomendasi' => $this->buildRecommendationHtml($recommendationItems),
                'rekomendasi_items' => $recommendationItems,
                'dokumentasi_rapat' => null,
                'notulis_signature_path' => $signature['path'],
                'notulis_signature_mime' => $signature['mime'],
                'notulis_signature_size' => $signature['size'],
                'approval_ready' => false,
                'submitted_at' => Carbon::now('Asia/Jayapura'),
            ]);

            $this->storeDocumentationFiles($notulensi, $request);
            $this->syncTindakLanjuts($notulensi, $recommendationItems);
            $this->notulensiApprovalService->syncWorkflow($notulensi);

            return $notulensi;
        });

        return redirect()->route('rapat.notulensi.edit', $notulensi)->with('success', 'Notulensi berhasil dibuat dan diajukan untuk approval.');
    }

    public function uploadFromRapat(UploadRapatNotulensiRequest $request, Rapat $rapat)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $notulensi = $rapat->notulensi;

        if (!$notulensi) {
            $notulensi = RapatNotulensi::create([
                'rapat_id' => $rapat->id,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'notulis_id' => auth()->id(),
                'mode' => 'upload',
                'status' => 'draft',
                'judul' => $rapat->judul,
            ]);
        }

        return $this->upload($request, $notulensi);
    }

    public function edit(RapatNotulensi $notulensi)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $notulensi->load('rapat.kategoriSuratKode', 'rapat.creator', 'rapat.pesertas.jabatan', 'notulis', 'tindakLanjuts.user');

        return view('rapat.notulensi.form', [
            'rapat' => $notulensi->rapat,
            'notulensi' => $notulensi,
            'formAction' => route('rapat.notulensi.update', $notulensi),
            'formMethod' => 'PUT',
            'pageTitle' => 'Edit Notulensi',
        ]);
    }

    public function update(StoreRapatNotulensiRequest $request, RapatNotulensi $notulensi)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $notulensi->loadMissing('rapat.pesertas.jabatan', 'rapat.kategoriSuratKode', 'rapat.creator');
        $data = $request->validated();
        $recommendationItems = $this->normalizeRecommendationItems($notulensi->rapat, $data['rekomendasi_items'] ?? []);

        DB::transaction(function () use ($notulensi, $data, $request, $recommendationItems) {
            $rapat = $notulensi->rapat;
            $signature = $this->signaturePadService->resolveForUser(auth()->user(), 'rapat/notulis-signatures', $data['signature_data'] ?? null);

            $notulensi->update([
                'notulis_id' => auth()->id(),
                'updated_by' => auth()->id(),
                'mode' => 'template_a',
                'status' => $notulensi->tidak_membuat_notulen ? 'tanpa_notulen' : 'pending_approval',
                'judul' => $data['judul'] ?: $rapat->judul,
                'uraian_kegiatan' => $data['uraian_kegiatan'] ?: $this->buildDefaultUraianKegiatan($rapat),
                'agenda_rapat' => $data['agenda_rapat'],
                'susunan_agenda' => $data['susunan_agenda'] ?? null,
                'hasil_rapat' => $data['hasil_rapat'],
                'rekomendasi' => $this->buildRecommendationHtml($recommendationItems),
                'rekomendasi_items' => $recommendationItems,
                'dokumentasi_rapat' => null,
                'notulis_signature_path' => $signature['path'],
                'notulis_signature_mime' => $signature['mime'],
                'notulis_signature_size' => $signature['size'],
                'approval_ready' => false,
                'submitted_at' => Carbon::now('Asia/Jayapura'),
            ]);

            $this->cleanupDocumentationFiles($notulensi, $request->input('remove_dokumentasi_files', []));
            $this->storeDocumentationFiles($notulensi, $request);
            $this->syncTindakLanjuts($notulensi, $recommendationItems);
            $this->notulensiApprovalService->syncWorkflow($notulensi, true);
        });

        return redirect()->route('rapat.notulensi.edit', $notulensi)->with('success', 'Notulensi berhasil diperbarui dan diajukan untuk approval.');
    }

    public function upload(UploadRapatNotulensiRequest $request, RapatNotulensi $notulensi)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $file = $request->file('notulensi_file');

        if ($notulensi->file_path) {
            Storage::disk('public')->delete($notulensi->file_path);
        }

        $path = $file->store('rapat/notulensi', 'public');

        $notulensi->update([
            'updated_by' => auth()->id(),
            'mode' => 'upload',
            'status' => 'pending_approval',
            'tidak_membuat_notulen' => false,
            'file_path' => $path,
            'file_nama' => $file->getClientOriginalName(),
            'file_mime' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'approval_ready' => false,
            'submitted_at' => Carbon::now('Asia/Jayapura'),
        ]);

        $this->notulensiApprovalService->syncWorkflow($notulensi, true);

        return redirect()->route('rapat.notulensi.edit', $notulensi)->with('success', 'File notulensi berhasil diupload dan diajukan untuk approval.');
    }

    public function skip(Rapat $rapat)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);

        $notulensi = $rapat->notulensi ?: new RapatNotulensi([
            'rapat_id' => $rapat->id,
            'created_by' => auth()->id(),
        ]);

        $notulensi->fill([
            'notulis_id' => auth()->id(),
            'updated_by' => auth()->id(),
            'mode' => 'skip',
            'status' => 'tanpa_notulen',
            'tidak_membuat_notulen' => true,
            'judul' => $rapat->judul,
            'approval_ready' => false,
            'submitted_at' => Carbon::now('Asia/Jayapura'),
        ]);

        $notulensi->save();
        $this->notulensiApprovalService->syncWorkflow($notulensi);

        return redirect()->route('rapat.notulensi.index')->with('success', 'Agenda ditandai tanpa notulen dan status selesai.');
    }

    public function pdf(RapatNotulensi $notulensi)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes() || auth()->user()->canViewRapat($notulensi->rapat), 403);

        if ($notulensi->mode === 'upload' && $notulensi->file_path && $notulensi->file_mime === 'application/pdf') {
            return response()->file(storage_path('app/public/' . $notulensi->file_path));
        }

        $notulensi->load('rapat.kategoriSuratKode', 'rapat.creator', 'rapat.pesertas.jabatan', 'rapat.approver1', 'notulis', 'approval.approver', 'tindakLanjuts.user');
        $verifier = app(\App\Services\PdfVerificationService::class);
        $signers = [[
            'name' => optional($notulensi->notulis)->name ?: '-',
            'role' => 'Notulis',
            'signed_at' => ($notulensi->submitted_at ?: $notulensi->updated_at)
                ? ($notulensi->submitted_at ?: $notulensi->updated_at)->copy()->timezone('Asia/Jayapura')->translatedFormat('d F Y H:i') . ' WIT'
                : '-',
        ]];
        if ($notulensi->approval && $notulensi->approval->status === 'approved') {
            $signers[] = [
                'name' => optional($notulensi->approval->approver)->name ?: '-',
                'role' => 'Approval Notulen',
                'signed_at' => $notulensi->approval->acted_at
                    ? $notulensi->approval->acted_at->copy()->timezone('Asia/Jayapura')->translatedFormat('d F Y H:i') . ' WIT'
                    : '-',
            ];
        }
        $verification = $verifier->begin('rapat', 'notulensi', $notulensi->id, 'Notulensi - ' . ($notulensi->rapat->judul ?: $notulensi->id), $signers, [
            'rapat_id' => $notulensi->rapat_id,
        ]);
        $pdfVerification = $verifier->viewData($verification);

        $pdf = PDF::loadView('rapat.notulensi.pdf', [
            'notulensi' => $notulensi,
            'rapat' => $notulensi->rapat,
            'kopImage' => $this->resolveKopImage(),
            'dokumentasiImages' => $this->resolveDocumentationImages($notulensi),
            'uraianKegiatanRows' => $this->resolveUraianKegiatanRows($notulensi),
            'notulisSignature' => app(RapatDocumentService::class)->buildNotulensiSignatureData($notulensi),
            'approvalSignature' => app(RapatDocumentService::class)->buildNotulensiApprovalSignatureData($notulensi),
            'pdfVerification' => $pdfVerification,
        ])->setPaper('a4', 'portrait');

        return $verifier->response($pdf->output(), $verification, 'notulensi-' . $notulensi->rapat->id . '.pdf');
    }

    public function file(RapatNotulensi $notulensi)
    {
        abort_unless(auth()->user()->canAccessMeetingMinutes(), 403);
        abort_unless($notulensi->file_path, 404);

        return response()->file(storage_path('app/public/' . $notulensi->file_path));
    }

    public function followUpIndex()
    {
        abort_unless(auth()->check() && auth()->user()->canAccessMeetingModule(), 403);

        $user = auth()->user();
        $query = RapatNotulensiTindakLanjut::with(['notulensi.rapat', 'user.unit', 'completedBy']);

        if (!$user->canAccessMeetingMinutes()) {
            if ($user->canMonitorNotulensiFollowUps()) {
                $monitorableUnits = $user->monitorable_meeting_unit_codes;
                $query->whereHas('user.unit', function ($unitQuery) use ($monitorableUnits) {
                    $unitQuery->whereIn('kode', $monitorableUnits);
                });
            } else {
                $query->where('user_id', $user->id);
            }
        }

        $items = $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'process' THEN 1 ELSE 2 END")
            ->orderByDesc('updated_at')
            ->get();

        $pendingItems = $items->whereIn('status', ['pending', 'process'])->values();
        $completedItems = $items->where('status', 'completed')->values();

        return view('rapat.notulensi.follow-ups', compact('pendingItems', 'completedItems'));
    }

    public function updateFollowUpStatus(Request $request, RapatNotulensiTindakLanjut $tindakLanjut)
    {
        abort_unless(auth()->check() && auth()->user()->canAccessMeetingModule(), 403);

        abort_unless(
            auth()->user()->canAccessMeetingMinutes() || (int) $tindakLanjut->user_id === (int) auth()->id(),
            403
        );

        $request->validate([
            'status' => ['required', 'in:pending,process,completed'],
        ]);

        $status = $request->input('status');

        if ($status === 'completed' && !$tindakLanjut->eviden_path) {
            return response()->json([
                'success' => false,
                'message' => 'Eviden wajib diupload sebelum status dapat diubah menjadi selesai.',
            ], 422);
        }

        $payload = [
            'status' => $status,
        ];

        if ($status === 'completed') {
            $payload['completed_at'] = Carbon::now('Asia/Jayapura');
            $payload['completed_by'] = auth()->id();
        } else {
            $payload['completed_at'] = null;
            $payload['completed_by'] = null;
        }

        $tindakLanjut->update($payload);

        return response()->json([
            'success' => true,
            'message' => 'Status tindak lanjut berhasil diperbarui.',
            'badge' => $tindakLanjut->fresh()->status_badge,
        ]);
    }

    public function uploadFollowUpEvidence(Request $request, RapatNotulensiTindakLanjut $tindakLanjut)
    {
        abort_unless(auth()->check() && auth()->user()->canAccessMeetingModule(), 403);

        abort_unless(
            auth()->user()->canAccessMeetingMinutes() || (int) $tindakLanjut->user_id === (int) auth()->id(),
            403
        );

        $request->validate([
            'eviden_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:10240'],
        ], [
            'eviden_file.required' => 'File eviden wajib diupload.',
            'eviden_file.mimes' => 'Format eviden harus PDF, JPG, JPEG, PNG, DOC, DOCX, XLS, atau XLSX.',
            'eviden_file.max' => 'Ukuran eviden maksimal 10MB.',
        ]);

        $file = $request->file('eviden_file');

        if ($tindakLanjut->eviden_path) {
            Storage::disk('public')->delete($tindakLanjut->eviden_path);
        }

        $path = $file->store('rapat/notulensi/eviden', 'public');

        $tindakLanjut->update([
            'eviden_path' => $path,
            'eviden_name' => $file->getClientOriginalName(),
            'eviden_mime' => $file->getClientMimeType(),
            'eviden_size' => $file->getSize(),
        ]);

        return back()->with('success', 'Eviden tindak lanjut berhasil diupload.');
    }

    public function followUpEvidence(RapatNotulensiTindakLanjut $tindakLanjut)
    {
        abort_unless(auth()->check() && auth()->user()->canAccessMeetingModule(), 403);

        abort_unless(
            auth()->user()->canAccessMeetingMinutes()
            || auth()->user()->canMonitorFollowUpForUser($tindakLanjut->user)
            || (int) $tindakLanjut->user_id === (int) auth()->id(),
            403
        );

        abort_unless($tindakLanjut->eviden_path, 404);

        return response()->file(storage_path('app/public/' . $tindakLanjut->eviden_path));
    }

    public function completeFollowUp(Request $request, RapatNotulensiTindakLanjut $tindakLanjut)
    {
        abort_unless(auth()->check() && auth()->user()->canAccessMeetingModule(), 403);

        abort_unless(
            auth()->user()->canAccessMeetingMinutes() || (int) $tindakLanjut->user_id === (int) auth()->id(),
            403
        );

        $request->validate([
            'catatan_penyelesaian' => ['nullable', 'string'],
        ]);

        if (!$tindakLanjut->eviden_path) {
            return back()->withErrors([
                'eviden_file' => 'Eviden wajib diupload sebelum tindak lanjut dapat diselesaikan.',
            ])->withInput();
        }

        $tindakLanjut->update([
            'status' => 'completed',
            'catatan_penyelesaian' => $request->input('catatan_penyelesaian'),
            'completed_at' => Carbon::now('Asia/Jayapura'),
            'completed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Tindak lanjut notulensi berhasil diselesaikan.');
    }

    protected function normalizeRecommendationItems(Rapat $rapat, array $recommendationItems)
    {
        $participantIds = $rapat->pesertas->pluck('id')->map(function ($id) {
            return (int) $id;
        })->all();

        return collect($recommendationItems)
            ->map(function ($item) use ($participantIds) {
                $aksi = trim((string) ($item['aksi'] ?? ''));
                $userIds = collect($item['user_ids'] ?? [])
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->filter(function ($id) use ($participantIds) {
                        return in_array($id, $participantIds, true);
                    })
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'aksi' => $aksi,
                    'user_ids' => $userIds,
                ];
            })
            ->filter(function ($item) {
                return !empty($item['aksi']) || !empty($item['user_ids']);
            })
            ->values()
            ->all();
    }

    protected function buildRecommendationHtml(array $recommendationItems)
    {
        if (empty($recommendationItems)) {
            return null;
        }

        $html = '<ol>';

        foreach ($recommendationItems as $item) {
            $html .= '<li>' . $item['aksi'] . '</li>';
        }

        $html .= '</ol>';

        return $html;
    }

    protected function syncTindakLanjuts(RapatNotulensi $notulensi, array $recommendationItems)
    {
        $existing = $notulensi->tindakLanjuts()->get()->keyBy(function ($item) {
            return $item->item_index . ':' . $item->user_id;
        });

        $validKeys = collect();

        foreach ($recommendationItems as $index => $item) {
            $snapshot = trim(Str::limit(strip_tags((string) $item['aksi']), 500, ''));

            foreach ($item['user_ids'] as $userId) {
                $key = $index . ':' . (int) $userId;
                $validKeys->push($key);

                $existingItem = $existing->get($key);
                if ($existingItem) {
                    $existingItem->update([
                        'deskripsi_snapshot' => $snapshot ?: $existingItem->deskripsi_snapshot,
                        'public_token' => $existingItem->public_token ?: (string) Str::uuid(),
                    ]);
                    continue;
                }

                $notulensi->tindakLanjuts()->create([
                    'item_index' => $index,
                    'user_id' => (int) $userId,
                    'public_token' => (string) Str::uuid(),
                    'status' => 'pending',
                    'deskripsi_snapshot' => $snapshot,
                ]);
            }
        }

        foreach ($existing as $key => $item) {
            if (!$validKeys->contains($key)) {
                $item->delete();
            }
        }
    }

    protected function buildDefaultUraianKegiatan(Rapat $rapat)
    {
        $rapat->loadMissing('pesertas.jabatan', 'approver1', 'approver2');

        $tanggal = optional($rapat->tanggal)->translatedFormat('l, d F Y');
        $waktu = trim($rapat->waktu_mulai_formatted . ' WIT s.d. selesai');
        $signatory = app(RapatDocumentService::class)->resolveDocumentSignatory($rapat);
        $pimpinan = optional($signatory)->name
            ?: optional($rapat->creator)->name
            ?: '-';
        $peserta = $rapat->pesertas->map(function ($user) {
            return $user->name;
        })->implode(', ');

        return '<div class="notulen-auto-list">' .
            '<p><strong>Hari/Tanggal/Jam</strong> : ' . e(trim($tanggal . ' / ' . $waktu, ' /')) . '</p>' .
            '<p><strong>Tempat</strong> : ' . e($rapat->tempat ?: '-') . '</p>' .
            '<p><strong>Pimpinan Agenda</strong> : ' . e($pimpinan) . '</p>' .
            '<p><strong>Peserta yang Diundang</strong> : ' . e($peserta ?: '-') . '</p>' .
            '</div>';
    }

    protected function resolveUraianKegiatanRows(RapatNotulensi $notulensi)
    {
        $html = (string) $notulensi->uraian_kegiatan;

        if (trim($html) === '') {
            return [];
        }

        $rows = [];

        if (preg_match_all('/<p>\s*<strong>(.*?)<\/strong>\s*:\s*(.*?)<\/p>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $label = trim(strip_tags($match[1]));
                $value = trim(strip_tags(html_entity_decode($match[2], ENT_QUOTES, 'UTF-8')));

                if ($label !== '' && $value !== '') {
                    $rows[] = [
                        'label' => $label,
                        'value' => $value,
                    ];
                }
            }
        }

        if (!empty($rows)) {
            return $rows;
        }

        if (preg_match_all('/<tr>\s*<td>(.*?)<\/td>\s*<td>(.*?)<\/td>\s*<\/tr>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $label = trim(strip_tags($match[1]));
                $value = trim(strip_tags(html_entity_decode($match[2], ENT_QUOTES, 'UTF-8')));

                if ($label !== '' && $value !== '') {
                    $rows[] = [
                        'label' => $label,
                        'value' => $value,
                    ];
                }
            }
        }

        return $rows;
    }

    public function resolveUraianKegiatanRowsForExport(RapatNotulensi $notulensi)
    {
        return $this->resolveUraianKegiatanRows($notulensi);
    }

    protected function storeDocumentationFiles(RapatNotulensi $notulensi, Request $request)
    {
        if (!$request->hasFile('dokumentasi_files')) {
            return;
        }

        $existingFiles = collect($notulensi->dokumentasi_files ?: []);

        foreach ($request->file('dokumentasi_files') as $file) {
            $path = $file->store('rapat/notulensi/dokumentasi', 'public');

            $existingFiles->push([
                'path' => $path,
                'nama' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $notulensi->update([
            'dokumentasi_files' => $existingFiles->values()->all(),
        ]);
    }

    protected function cleanupDocumentationFiles(RapatNotulensi $notulensi, array $removedPaths)
    {
        if (empty($removedPaths)) {
            return;
        }

        $removedPaths = collect($removedPaths)->filter()->values();
        $remainingFiles = collect($notulensi->dokumentasi_files ?: [])->reject(function ($item) use ($removedPaths) {
            return $removedPaths->contains($item['path']);
        })->values();

        foreach ($removedPaths as $path) {
            Storage::disk('public')->delete($path);
        }

        $notulensi->update([
            'dokumentasi_files' => $remainingFiles->all(),
        ]);
    }

    protected function resolveDocumentationImages(RapatNotulensi $notulensi)
    {
        return collect($notulensi->dokumentasi_files ?: [])
            ->map(function ($item) {
                $path = storage_path('app/public/' . $item['path']);
                if (!is_file($path)) {
                    return null;
                }

                $mime = !empty($item['mime']) ? $item['mime'] : mime_content_type($path);

                return [
                    'nama' => $item['nama'] ?? basename($path),
                    'data_uri' => 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path)),
                ];
            })
            ->filter()
            ->values();
    }

    public function resolveDocumentationImagesForExport(RapatNotulensi $notulensi)
    {
        return $this->resolveDocumentationImages($notulensi);
    }

    protected function resolveKopImage()
    {
        foreach (['kop_absen.jpg', 'kop_undangan.png'] as $filename) {
            $path = public_path($filename);
            if (is_file($path)) {
                return 'data:' . mime_content_type($path) . ';base64,' . base64_encode(file_get_contents($path));
            }
        }

        return null;
    }
}
