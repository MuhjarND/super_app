<?php

namespace App\Http\Controllers;

use App\Disposisi;
use App\SuratMasuk;
use App\Jabatan;
use App\User;
use App\Services\ActivityAuditService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DisposisiController extends Controller
{
    protected $waService;
    protected $auditService;

    public function __construct(WhatsAppNotificationService $waService, ActivityAuditService $auditService)
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
        if (!$user->canViewSuratMasuk($suratMasuk) || !$user->canForwardSuratMasuk($suratMasuk)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk memproses surat ini.',
            ], 403);
        }

        $kepadaUser = User::with('jabatan')->find($request->kepada_user_id);
        $targetIds = $this->targetJabatanIdsFor($user);

        if (!$kepadaUser || !$kepadaUser->jabatan_id || (!$user->isSuperAdmin() && !in_array($kepadaUser->jabatan_id, $targetIds))) {
            return response()->json([
                'success' => false,
                'message' => 'Tujuan disposisi tidak valid untuk jabatan Anda.',
            ], 422);
        }

        if ($suratMasuk->status === 'didisposisi') {
            $suratMasuk->disposisis()
                ->where('kepada_user_id', $user->id)
                ->where('status', 'pending')
                ->update(['status' => 'ditindaklanjuti']);
        }

        $priorityLevel = $request->priority_level ?: 'normal';
        $defaultTarget = $priorityLevel === 'high'
            ? now('Asia/Jayapura')->addDay()
            : ($priorityLevel === 'low' ? now('Asia/Jayapura')->addDays(5) : now('Asia/Jayapura')->addDays(3));

        $disposisi = Disposisi::create([
            'surat_masuk_id' => $request->surat_masuk_id,
            'dari_user_id' => $user->id,
            'kepada_user_id' => $request->kepada_user_id,
            'dari_jabatan_id' => $user->jabatan_id,
            'kepada_jabatan_id' => $kepadaUser->jabatan_id,
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
        abort_unless(auth()->user()->canFollowUpDisposisi($disposisi), 403);

        $rules = [
            'status' => 'required|in:dibaca,diproses,ditindaklanjuti',
        ];

        if ($request->status === 'ditindaklanjuti') {
            $rules['catatan_tindak_lanjut'] = 'required|string';
        } else {
            $rules['catatan_tindak_lanjut'] = 'nullable|string';
        }

        $request->validate($rules);

        $oldStatus = $disposisi->status;

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
        ]);

        if ($request->status == 'ditindaklanjuti') {
            // Check if all dispositions are resolved
            $suratMasuk = $disposisi->suratMasuk;
            $allResolved = !$suratMasuk->disposisis()
                ->where('status', '!=', 'ditindaklanjuti')
                ->exists();

            if ($allResolved) {
                $suratMasuk->update(['status' => 'selesai']);
            }
        }

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
            ],
            'note' => $request->catatan_tindak_lanjut,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status disposisi berhasil diperbarui.',
        ]);
    }

    public function remind(Disposisi $disposisi)
    {
        $user = auth()->user();
        abort_unless(
            $user->isSuperAdmin() || (int) $disposisi->dari_user_id === (int) $user->id || (int) $disposisi->kepada_user_id === (int) $user->id,
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

        $targetIds = $this->targetJabatanIdsFor($user);
        if (empty($targetIds)) {
            return response()->json([]);
        }

        $targetJabatans = Jabatan::whereIn('id', $targetIds)
            ->with(['users' => function ($query) use ($user) {
                $query->active()->where('id', '!=', $user->id)->orderBy('name');
            }])
            ->orderBy('level')
            ->orderBy('nama')
            ->get();

        foreach ($targetJabatans as $jabatan) {
            foreach ($jabatan->users as $u) {
                $targets[] = [
                    'id' => $u->id,
                    'name' => $u->name,
                    'user_id' => $u->id,
                    'user_name' => $u->name,
                    'jabatan' => $jabatan->nama,
                    'jabatan_kode' => $jabatan->kode,
                    'is_naikan' => in_array($jabatan->kode, ['KPTA', 'WKPTA']),
                ];
            }
        }

        return response()->json($targets);
    }

    protected function targetJabatanIdsFor(User $user)
    {
        if ($user->isSuperAdmin()) {
            return Jabatan::whereIn('kode', [
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
            ])->pluck('id')->toArray();
        }

        return $user->jabatan ? $user->jabatan->getTargetDisposisi() : [];
    }
}
