<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRapatRequest;
use App\Http\Requests\UpdateRapatRequest;
use App\Rapat;
use App\Services\RapatApprovalService;
use App\Services\RapatDocumentService;
use App\Services\WhatsAppNotificationService;
use App\User;
use App\ZiActivity;
use App\ZiGuidelineSubPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RapatController extends Controller
{
    protected $approvalService;
    protected $whatsAppService;
    protected $documentService;

    public function __construct(
        RapatApprovalService $approvalService,
        WhatsAppNotificationService $whatsAppService,
        RapatDocumentService $documentService
    )
    {
        $this->middleware('auth');
        $this->approvalService = $approvalService;
        $this->whatsAppService = $whatsAppService;
        $this->documentService = $documentService;
    }

    public function index()
    {
        abort_unless(auth()->user()->canAccessMeetingModule(), 403);

        $user = auth()->user();
        $rapats = Rapat::visibleTo($user)
            ->with([
                'kategoriRapat',
                'kategoriSuratKode.parent.parent.parent',
                'creator',
                'approver1.jabatan',
                'approver2.jabatan',
                'pesertas',
                'suratKeluar',
                'approvals.approver',
                'approvalHistories.approver',
            ])
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu_mulai')
            ->get();

        $kategoriSuratOptions = $this->documentService->getKategoriSuratLeafOptions();
        $participants = User::with(['unit', 'bidang', 'jabatan', 'roles'])
            ->active()
            ->ordered()
            ->get();
        $approvers = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'approval', 'super_admin']);
        })->active()->with('jabatan')->ordered()->get();

        return view('rapat.index', compact('rapats', 'kategoriSuratOptions', 'participants', 'approvers'));
    }

    public function store(StoreRapatRequest $request)
    {
        $data = $request->validated();
        $rapat = null;

        DB::transaction(function () use ($request, $data, &$rapat) {
            $rapat = Rapat::create(array_merge($this->payloadFromRequest($request, $data), [
                'nomor_undangan' => '',
                'token_qr' => (string) Str::uuid(),
                'public_code' => strtoupper(Str::random(10)),
                'created_by' => auth()->id(),
            ]));

            $this->syncPeserta($rapat, $data['peserta_ids']);
            $this->approvalService->syncWorkflow($rapat, $rapat->status);
            $this->documentService->syncSuratKeluar($rapat, false);
            $this->syncProgressZiContext($rapat, $request);
        });

        $this->dispatchCreateOrUpdateNotifications($rapat->fresh(['pesertas', 'approvals', 'kategoriSuratKode', 'creator']));

        return response()->json([
            'success' => true,
            'message' => 'Rapat berhasil disimpan dan undangan rapat otomatis dibuat.',
        ]);
    }

    public function update(UpdateRapatRequest $request, Rapat $rapat)
    {
        abort_unless(auth()->user()->canManageRapat(), 403);

        $data = $request->validated();
        $wasApproved = $rapat->status === 'disetujui' || !is_null($rapat->participant_notified_at);

        DB::transaction(function () use ($request, $rapat, $data) {
            $rapat->update($this->payloadFromRequest($request, $data, $rapat));
            $this->syncPeserta($rapat, $data['peserta_ids']);
            $this->approvalService->syncWorkflow(
                $rapat,
                $rapat->status,
                $rapat->approvals()->where('status', 'rejected')->exists()
            );
            $this->documentService->syncSuratKeluar($rapat, false);
        });

        $this->dispatchCreateOrUpdateNotifications(
            $rapat->fresh(['pesertas', 'approvals', 'kategoriSuratKode', 'creator']),
            $wasApproved
        );

        return response()->json([
            'success' => true,
            'message' => 'Rapat berhasil diperbarui.',
        ]);
    }

    public function destroy(Rapat $rapat)
    {
        abort_unless(auth()->user()->canManageRapat(), 403);

        $rapat->load('suratKeluar');

        if ($rapat->lampiran_tambahan_path) {
            Storage::disk('public')->delete($rapat->lampiran_tambahan_path);
        }

        if ($rapat->suratKeluar) {
            if ($rapat->suratKeluar->file_path) {
                Storage::disk('public')->delete($rapat->suratKeluar->file_path);
            }
            $rapat->suratKeluar->penerimaInternal()->detach();
            $rapat->suratKeluar->delete();
        }

        $rapat->pesertas()->detach();
        $rapat->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rapat berhasil dihapus.',
        ]);
    }

    public function lampiran(Rapat $rapat)
    {
        abort_unless(auth()->user()->canViewRapat($rapat), 403);
        abort_unless($rapat->lampiran_tambahan_path, 404);

        return response()->file(storage_path('app/public/' . $rapat->lampiran_tambahan_path));
    }

    public function previewNomorUndangan(\Illuminate\Http\Request $request)
    {
        abort_unless(auth()->user()->canManageRapat(), 403);

        $request->validate([
            'kategori_surat_kode_id' => 'nullable|exists:klasifikasi_kodes,id',
            'nomenklatur_jabatan' => 'nullable|in:ketua,wakil_ketua,sekretaris,panitera',
            'tanggal' => 'nullable|date',
            'approver_1_id' => ['nullable', Rule::exists('users', 'id')->where('status_aktif_pegawai', true)],
            'approver_2_id' => ['nullable', Rule::exists('users', 'id')->where('status_aktif_pegawai', true)],
        ]);

        return response()->json([
            'nomor' => $this->documentService->previewNomorSurat(
                $request->input('kategori_surat_kode_id'),
                $request->input('tanggal'),
                $request->input('nomenklatur_jabatan')
            ),
        ]);
    }

    public function previewUndangan(Rapat $rapat)
    {
        abort_unless(auth()->user()->canViewRapat($rapat) || auth()->user()->canAccessMeetingApproval(), 403);

        return $this->documentService->streamUndanganPdf($rapat);
    }

    protected function payloadFromRequest($request, array $data, Rapat $rapat = null)
    {
        $payload = [
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'kategori_rapat_id' => $this->resolveFallbackKategoriRapatId($rapat),
            'kategori_surat_kode_id' => $data['kategori_surat_kode_id'],
            'nomenklatur_jabatan' => $data['nomenklatur_jabatan'],
            'tanggal' => $data['tanggal'],
            'waktu_mulai' => $data['waktu_mulai'],
            'tempat' => $data['tempat'],
            'approver_1_id' => $data['approver_1_id'] ?? null,
            'approver_2_id' => $data['approver_2_id'] ?? null,
            'approval1_jabatan_manual' => $data['approval1_jabatan_manual'] ?? null,
            'detail_tambahan' => !empty($data['include_detail_tambahan']) ? ($data['detail_tambahan'] ?? null) : null,
            'tujuan_surat' => $data['tujuan_surat'] ?? null,
            'jenis_pakaian' => !empty($data['include_pakaian']) ? ($data['jenis_pakaian'] ?? null) : null,
            'is_virtual' => (bool) ($data['is_virtual'] ?? false),
            'meeting_id' => $data['meeting_id'] ?? null,
            'meeting_passcode' => $data['meeting_passcode'] ?? null,
            'status' => $data['status'] ?? ($rapat ? $rapat->status : 'terjadwal'),
            'is_recurring' => (bool) ($data['is_recurring'] ?? false),
            'recurring_pattern' => $data['recurring_pattern'] ?? null,
            'recurring_until' => $data['recurring_until'] ?? null,
        ];

        $removeLampiran = (bool) ($data['hapus_lampiran_tambahan'] ?? false);
        $useLampiran = (bool) ($data['gunakan_lampiran_tambahan'] ?? false);

        if ($rapat && ($removeLampiran || (!$useLampiran && !$request->hasFile('lampiran_tambahan')))) {
            if ($rapat->lampiran_tambahan_path) {
                Storage::disk('public')->delete($rapat->lampiran_tambahan_path);
            }
            $payload['lampiran_tambahan_path'] = null;
            $payload['lampiran_tambahan_nama'] = null;
            $payload['lampiran_tambahan_mime'] = null;
            $payload['lampiran_tambahan_size'] = null;
        }

        if ($request->hasFile('lampiran_tambahan')) {
            if ($rapat && $rapat->lampiran_tambahan_path) {
                Storage::disk('public')->delete($rapat->lampiran_tambahan_path);
            }

            $file = $request->file('lampiran_tambahan');
            $payload['lampiran_tambahan_path'] = $file->store('rapat/lampiran', 'public');
            $payload['lampiran_tambahan_nama'] = $file->getClientOriginalName();
            $payload['lampiran_tambahan_mime'] = $file->getClientMimeType();
            $payload['lampiran_tambahan_size'] = $file->getSize();
        }

        if (!$payload['is_virtual']) {
            $payload['meeting_id'] = null;
            $payload['meeting_passcode'] = null;
        }

        if (!$payload['is_recurring']) {
            $payload['recurring_pattern'] = null;
            $payload['recurring_until'] = null;
        }

        return $payload;
    }

    protected function resolveFallbackKategoriRapatId(Rapat $rapat = null)
    {
        if ($rapat && $rapat->kategori_rapat_id) {
            return $rapat->kategori_rapat_id;
        }

        $existing = \App\KategoriRapat::where('aktif', true)->orderBy('id')->first();
        if ($existing) {
            return $existing->id;
        }

        $created = \App\KategoriRapat::create([
            'kode' => 'AUTO-RAPAT',
            'nama' => 'Auto Generated',
            'keterangan' => 'Kategori sistem untuk undangan rapat yang menggunakan kategori surat.',
            'butuh_pakaian' => false,
            'aktif' => true,
        ]);

        return $created->id;
    }

    protected function syncPeserta(Rapat $rapat, array $participantIds)
    {
        $orderedUsers = User::whereIn('id', $participantIds)
            ->active()
            ->ordered()
            ->get();

        $syncData = [];
        foreach ($orderedUsers as $index => $participant) {
            $syncData[$participant->id] = ['urutan' => $index + 1];
        }

        $rapat->pesertas()->sync($syncData);
    }

    protected function dispatchCreateOrUpdateNotifications(Rapat $rapat, $skipApprovalAndParticipantNotification = false)
    {
        if ($skipApprovalAndParticipantNotification) {
            return;
        }

        if ($rapat->status === 'pending_approval') {
            $this->approvalService->notifyCurrentPendingApprover($rapat);
            return;
        }

        if (in_array($rapat->status, ['terjadwal', 'disetujui'], true)) {
            $this->whatsAppService->notifyRapatParticipants($rapat);
        }
    }

    protected function syncProgressZiContext(Rapat $rapat, $request)
    {
        if ($request->input('zi_source_context') !== 'progress_zi') {
            return;
        }

        $periodId = $request->input('zi_period_id');
        $areaId = $request->input('zi_area_id');
        $subPointId = $request->input('zi_guideline_sub_point_id');

        if (!$periodId || !$areaId || !$subPointId) {
            return;
        }

        $subPoint = ZiGuidelineSubPoint::with('point')->find($subPointId);
        if (!$subPoint || !$subPoint->point || (int) $subPoint->point->zi_area_id !== (int) $areaId) {
            return;
        }

        ZiActivity::updateOrCreate(
            [
                'source_reference_type' => 'rapat',
                'source_reference_id' => $rapat->id,
            ],
            [
                'zi_period_id' => $periodId,
                'zi_area_id' => $areaId,
                'zi_guideline_sub_point_id' => $subPointId,
                'name' => $rapat->judul,
                'description' => $rapat->deskripsi ?: 'Rapat monitoring dan evaluasi Zona Integritas.',
                'target_start_date' => $rapat->tanggal,
                'target_end_date' => $rapat->tanggal,
                'pic_user_id' => auth()->id(),
                'status' => 'dijadwalkan',
                'source_type' => 'rapat',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );
    }
}
