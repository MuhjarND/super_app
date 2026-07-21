<?php

namespace App\Http\Controllers;

use App\Disposisi;
use App\DisposisiDokumentasi;
use App\SuratMasuk;
use App\Jabatan;
use App\User;
use App\Services\ActivityAuditService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DisposisiController extends Controller
{
    protected $waService;
    protected $auditService;

    public function __construct(
        WhatsAppNotificationService $waService,
        ActivityAuditService $auditService
    )
    {
        $this->middleware('auth');
        $this->waService = $waService;
        $this->auditService = $auditService;
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $petunjukRules = $user->requiresPetunjukDisposisi()
            ? ['required', 'string', Rule::in(Disposisi::getPetunjukOptions())]
            : ['nullable', 'string', Rule::in(Disposisi::getPetunjukOptions())];

        $request->validate([
            'surat_masuk_id' => 'required|exists:surat_masuks,id',
            'kepada_user_id' => ['required', Rule::exists('users', 'id')->where('status_aktif_pegawai', true)],
            'tipe' => 'required|in:disposisi,naikan',
            'petunjuk' => $petunjukRules,
            'catatan' => 'nullable|string',
            'priority_level' => 'required|in:low,normal,high',
            'target_tindak_lanjut_at' => 'nullable|date',
        ]);

        $suratMasuk = SuratMasuk::findOrFail($request->surat_masuk_id);
        if ($suratMasuk->status === 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Surat ini sudah selesai ditindaklanjuti dan tidak perlu didisposisi lagi.',
            ], 422);
        }

        if (!$user->canViewSuratMasuk($suratMasuk) || !$user->canForwardSuratMasuk($suratMasuk)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk memproses surat ini.',
            ], 403);
        }

        $kepadaUser = User::with('jabatan', 'activeJabatanDelegations.jabatan')->find($request->kepada_user_id);
        $targetIds = $this->targetJabatanIdsFor($user, $request->tipe);
        $kepadaJabatanId = $this->resolveTargetJabatanId($kepadaUser, $targetIds);
        $kepadaJabatan = $kepadaJabatanId ? Jabatan::find($kepadaJabatanId) : null;

        if (!$kepadaUser || !$kepadaJabatanId || (!$user->isSuperAdmin() && !in_array($kepadaJabatanId, $targetIds))) {
            return response()->json([
                'success' => false,
                'message' => 'Tujuan disposisi tidak valid untuk jabatan Anda.',
            ], 422);
        }

        if ($request->tipe === 'naikan') {
            if (!$user->canNaikanSuratMasuk()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Opsi naikkan hanya tersedia untuk Panitera, Sekretaris, dan Kasubag TURT.',
                ], 403);
            }

            if (!$kepadaJabatan || !in_array($kepadaJabatan->kode, ['KPTA', 'WKPTA'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tujuan naikkan surat harus Ketua atau Wakil Ketua.',
                ], 422);
            }
        }

        if ($suratMasuk->status === 'didisposisi') {
            $suratMasuk->disposisis()
                ->addressedToUser($user)
                ->where('status', 'pending')
                ->update(['status' => 'ditindaklanjuti']);
        }

        $priorityLevel = $request->priority_level ?: 'normal';
        $defaultTarget = $priorityLevel === 'high'
            ? now('Asia/Jayapura')->addDay()
            : ($priorityLevel === 'low' ? now('Asia/Jayapura')->addDays(5) : now('Asia/Jayapura')->addDays(3));

        $sourceJabatanId = $user->sourceJabatanIdForTarget($kepadaJabatanId);
        $actingDelegation = $user->activeDelegationForJabatan($sourceJabatanId);

        $disposisi = Disposisi::create([
            'surat_masuk_id' => $request->surat_masuk_id,
            'dari_user_id' => $user->id,
            'kepada_user_id' => $request->kepada_user_id,
            'dari_jabatan_id' => $sourceJabatanId,
            'kepada_jabatan_id' => $kepadaJabatanId,
            'petunjuk' => $user->requiresPetunjukDisposisi() ? $request->petunjuk : null,
            'catatan' => $request->catatan,
            'tipe' => $request->tipe,
            'status' => 'pending',
            'priority_level' => $priorityLevel,
            'target_tindak_lanjut_at' => $request->target_tindak_lanjut_at ?: $defaultTarget,
        ]);

        // Update surat masuk status
        $suratMasuk->update(['status' => 'didisposisi']);

        // Send WA notification to recipient
        if ($this->waService->notifyDisposisi($disposisi, $kepadaUser)) {
            $disposisi->forceFill([
                'notification_sent_at' => now('Asia/Jayapura'),
            ])->save();
        }

        $this->auditService->log('persuratan', 'disposisi_created', $disposisi, [
            'subject_type' => 'surat_masuk',
            'subject_id' => $suratMasuk->id,
            'subject_title' => $suratMasuk->nomor_surat . ' - ' . $suratMasuk->perihal,
            'target_user_id' => $kepadaUser->id,
            'target_name' => $kepadaUser->name,
            'new_values_json' => [
                'tipe' => $disposisi->tipe,
                'priority_level' => $disposisi->priority_level,
                'status' => $disposisi->status,
                'target_tindak_lanjut_at' => optional($disposisi->target_tindak_lanjut_at)->toDateTimeString(),
            ],
            'note' => $request->catatan,
            'metadata_json' => [
                'petunjuk' => $disposisi->petunjuk,
                'acting_as_delegation' => $actingDelegation ? [
                    'type' => strtoupper((string) $actingDelegation->delegation_type),
                    'jabatan_id' => $actingDelegation->jabatan_id,
                    'jabatan' => optional($actingDelegation->jabatan)->nama,
                ] : null,
            ],
        ], $user);

        $tipeLabel = $request->tipe == 'naikan' ? 'dinaikkan' : 'didisposisi';

        return response()->json([
            'success' => true,
            'message' => "Surat berhasil {$tipeLabel} ke {$kepadaUser->name}.",
        ]);
    }

    public function updateStatus(Request $request, Disposisi $disposisi)
    {
        $user = auth()->user();
        abort_unless($user->canFollowUpDisposisi($disposisi), 403);

        $rules = [
            'status' => 'required|in:dibaca,diproses,ditindaklanjuti',
        ];

        if ($request->status === 'ditindaklanjuti') {
            $rules['catatan_tindak_lanjut'] = 'required|string';
            $rules['tautan_tindak_lanjut'] = [
                'nullable',
                'url',
                'max:2048',
                function ($attribute, $value, $fail) {
                    if ($value && !preg_match('/^https?:\/\//i', $value)) {
                        $fail('Link dokumentasi harus menggunakan http atau https.');
                    }
                },
            ];
            $rules['dokumentasi'] = 'nullable|array|max:5';
            $rules['dokumentasi.*'] = 'file|mimes:jpg,jpeg,png,webp,pdf,docx|max:10240';
        } else {
            $rules['catatan_tindak_lanjut'] = 'nullable|string';
        }

        $request->validate($rules);

        $oldStatus = $disposisi->status;
        $storedPaths = [];

        DB::beginTransaction();

        try {
            $disposisi->update([
                'status' => $request->status,
                'read_at' => in_array($request->status, ['dibaca', 'diproses', 'ditindaklanjuti'], true)
                    ? ($disposisi->read_at ?: now('Asia/Jayapura'))
                    : $disposisi->read_at,
                'completed_at' => $request->status === 'ditindaklanjuti'
                    ? now('Asia/Jayapura')
                    : null,
                'catatan_tindak_lanjut' => $request->status === 'ditindaklanjuti'
                    ? $request->catatan_tindak_lanjut
                    : $disposisi->catatan_tindak_lanjut,
                'tautan_tindak_lanjut' => $request->status === 'ditindaklanjuti'
                    ? $request->tautan_tindak_lanjut
                    : $disposisi->tautan_tindak_lanjut,
            ]);

            if ($request->status === 'ditindaklanjuti') {
                foreach ($request->file('dokumentasi', []) as $file) {
                    $path = $file->store('surat-masuk/tindak-lanjut/' . $disposisi->id, 'local');
                    $storedPaths[] = $path;

                    $disposisi->dokumentasis()->create([
                        'uploaded_by' => $user->id,
                        'file_path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }

                $suratMasuk = $disposisi->suratMasuk;
                $allResolved = !$suratMasuk->disposisis()
                    ->where('status', '!=', 'ditindaklanjuti')
                    ->exists();

                if ($allResolved) {
                    $suratMasuk->update(['status' => 'selesai']);
                }
            }

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }

        $disposisi->load('dokumentasis');

        $actingDelegation = $user->activeDelegationForJabatan($disposisi->kepada_jabatan_id);

        $this->auditService->log('persuratan', 'disposisi_status_updated', $disposisi, [
            'subject_type' => 'surat_masuk',
            'subject_id' => optional($disposisi->suratMasuk)->id,
            'subject_title' => optional($disposisi->suratMasuk)->nomor_surat . ' - ' . optional($disposisi->suratMasuk)->perihal,
            'target_user_id' => $disposisi->kepada_user_id,
            'target_name' => optional($disposisi->kepadaUser)->name,
            'old_values_json' => ['status' => $oldStatus],
            'new_values_json' => [
                'status' => $disposisi->status,
                'completed_at' => optional($disposisi->completed_at)->toDateTimeString(),
                'tautan_tindak_lanjut' => $disposisi->tautan_tindak_lanjut,
                'dokumentasi' => $disposisi->dokumentasis->pluck('original_name')->values()->all(),
            ],
            'note' => $request->catatan_tindak_lanjut,
            'metadata_json' => [
                'acting_as_delegation' => $actingDelegation ? [
                    'type' => strtoupper((string) $actingDelegation->delegation_type),
                    'jabatan_id' => $actingDelegation->jabatan_id,
                    'jabatan' => optional($actingDelegation->jabatan)->nama,
                ] : null,
            ],
        ], $user);

        return response()->json([
            'success' => true,
            'message' => $request->status === 'ditindaklanjuti'
                ? 'Tindak lanjut dan dokumentasi berhasil disimpan.'
                : 'Status disposisi berhasil diperbarui.',
        ]);
    }

    public function previewDokumentasi(DisposisiDokumentasi $dokumentasi)
    {
        $this->authorizeDokumentasi($dokumentasi);

        abort_unless(Storage::disk('local')->exists($dokumentasi->file_path), 404, 'Dokumentasi tidak ditemukan.');

        $safeName = str_replace(['"', "\r", "\n"], '', $dokumentasi->original_name);

        return response()->file(Storage::disk('local')->path($dokumentasi->file_path), [
            'Content-Type' => $dokumentasi->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $safeName . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadDokumentasi(DisposisiDokumentasi $dokumentasi)
    {
        $this->authorizeDokumentasi($dokumentasi);
        abort_unless(Storage::disk('local')->exists($dokumentasi->file_path), 404, 'Dokumentasi tidak ditemukan.');

        return Storage::disk('local')->download($dokumentasi->file_path, $dokumentasi->original_name);
    }

    protected function authorizeDokumentasi(DisposisiDokumentasi $dokumentasi)
    {
        $dokumentasi->loadMissing('disposisi.suratMasuk');
        $suratMasuk = optional($dokumentasi->disposisi)->suratMasuk;

        abort_unless($suratMasuk && auth()->user()->canViewSuratMasuk($suratMasuk), 403);
    }

    public function remind(Disposisi $disposisi)
    {
        $user = auth()->user();
        abort_unless(
            $user->isSuperAdmin() || (int) $disposisi->dari_user_id === (int) $user->id || $user->canFollowUpDisposisi($disposisi),
            403
        );
        abort_if($disposisi->status === 'ditindaklanjuti', 422, 'Disposisi sudah selesai ditindaklanjuti.');

        $targetUser = $disposisi->kepadaUser;
        abort_unless($targetUser, 422, 'Tujuan disposisi tidak ditemukan.');

        $sent = $this->waService->notifyDisposisiReminder($disposisi->loadMissing('suratMasuk', 'kepadaUser'), $targetUser);

        if ($sent) {
            $disposisi->forceFill([
                'reminder_whatsapp_sent_at' => now('Asia/Jayapura'),
            ])->save();
        }

        $this->auditService->log('persuratan', 'disposisi_reminder_sent', $disposisi, [
            'subject_type' => 'surat_masuk',
            'subject_id' => optional($disposisi->suratMasuk)->id,
            'subject_title' => optional($disposisi->suratMasuk)->nomor_surat . ' - ' . optional($disposisi->suratMasuk)->perihal,
            'target_user_id' => $targetUser->id,
            'target_name' => $targetUser->name,
            'note' => 'Pengingat WhatsApp dikirim untuk disposisi pending.',
        ], $user);

        return response()->json([
            'success' => true,
            'message' => $sent
                ? 'Pengingat WhatsApp berhasil dikirim.'
                : 'Pengingat dicatat, namun pengiriman WhatsApp belum berhasil atau belum dikonfigurasi.',
        ]);
    }

    public function getTargets(Request $request)
    {
        $user = auth()->user();
        $targets = [];

        $tipe = $request->input('tipe') === 'naikan' ? 'naikan' : 'disposisi';
        $targetIds = $this->targetJabatanIdsFor($user, $tipe);
        if (empty($targetIds)) {
            return response()->json([]);
        }

        $targetJabatans = Jabatan::whereIn('id', $targetIds)
            ->orderBy('level')
            ->orderBy('nama')
            ->get();

        foreach ($targetJabatans as $jabatan) {
            $users = User::with('jabatan', 'activeJabatanDelegations.jabatan')
                ->active()
                ->where('id', '!=', $user->id)
                ->where(function ($query) use ($jabatan) {
                    $query->where('jabatan_id', $jabatan->id)
                        ->orWhereHas('activeJabatanDelegations', function ($delegationQuery) use ($jabatan) {
                            $delegationQuery->where('jabatan_id', $jabatan->id);
                        });
                })
                ->orderBy('name')
                ->get();

            foreach ($users as $u) {
                $targetDelegation = $u->activeJabatanDelegations->first(function ($delegation) use ($jabatan) {
                    return (int) $delegation->jabatan_id === (int) $jabatan->id;
                });
                $assignmentLabel = $targetDelegation
                    ? strtoupper((string) $targetDelegation->delegation_type) . ' ' . $jabatan->nama
                    : $jabatan->nama;

                $targets[] = [
                    'id' => $u->id,
                    'name' => $u->name,
                    'user_id' => $u->id,
                    'user_name' => $u->name,
                    'jabatan' => $assignmentLabel,
                    'jabatan_kode' => $jabatan->kode,
                    'is_delegated' => (bool) $targetDelegation,
                    'delegation_type' => $targetDelegation ? strtoupper((string) $targetDelegation->delegation_type) : null,
                    'is_naikan' => in_array($jabatan->kode, ['KPTA', 'WKPTA']),
                ];
            }
        }

        return response()->json($targets);
    }

    protected function targetJabatanIdsFor(User $user, $tipe = null)
    {
        if ($tipe === 'naikan') {
            if (!$user->canNaikanSuratMasuk() && !$user->isSuperAdmin()) {
                return [];
            }

            return Jabatan::whereIn('kode', ['KPTA', 'WKPTA'])->pluck('id')->toArray();
        }

        if ($user->isSuperAdmin()) {
            $query = Jabatan::whereIn('kode', [
                'KPTA',
                'WKPTA',
                'SEK',
                'PAN',
                'KABAG_KEPEG',
                'KABAG_UMUM',
                'KASUBAG_KEPEG',
                'KASUBAG_RENPRO',
                'KASUBAG_LAPKEU',
                'KASUBAG_TURT',
                'PANMUD_BANDING',
                'PANMUD_HUKUM',
            ]);

            if ($tipe === 'disposisi') {
                $query->whereNotIn('kode', ['KPTA', 'WKPTA']);
            }

            return $query->pluck('id')->toArray();
        }

        $targetIds = $user->targetDisposisiJabatanIds();

        if ($tipe === 'disposisi' && !empty($targetIds)) {
            $pimpinanIds = Jabatan::whereIn('kode', ['KPTA', 'WKPTA'])->pluck('id')->map(function ($id) {
                return (int) $id;
            })->all();

            $targetIds = array_values(array_filter($targetIds, function ($id) use ($pimpinanIds) {
                return !in_array((int) $id, $pimpinanIds, true);
            }));
        }

        return $targetIds;
    }

    protected function resolveTargetJabatanId($targetUser, array $allowedTargetIds)
    {
        if (!$targetUser) {
            return null;
        }

        $allowedTargetIds = array_map('intval', $allowedTargetIds);

        if ($targetUser->jabatan_id && in_array((int) $targetUser->jabatan_id, $allowedTargetIds, true)) {
            return (int) $targetUser->jabatan_id;
        }

        foreach ($targetUser->activeJabatanDelegations as $delegation) {
            if ($delegation->jabatan_id && in_array((int) $delegation->jabatan_id, $allowedTargetIds, true)) {
                return (int) $delegation->jabatan_id;
            }
        }

        return null;
    }
}
