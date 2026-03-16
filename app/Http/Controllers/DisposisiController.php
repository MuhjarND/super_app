<?php

namespace App\Http\Controllers;

use App\Disposisi;
use App\SuratMasuk;
use App\Jabatan;
use App\User;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DisposisiController extends Controller
{
    protected $waService;

    public function __construct(WhatsAppNotificationService $waService)
    {
        $this->middleware('auth');
        $this->waService = $waService;
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $petunjukRules = $user->requiresPetunjukDisposisi()
            ? ['required', 'string', Rule::in(Disposisi::getPetunjukOptions())]
            : ['nullable', 'string', Rule::in(Disposisi::getPetunjukOptions())];

        $request->validate([
            'surat_masuk_id' => 'required|exists:surat_masuks,id',
            'kepada_user_id' => 'required|exists:users,id',
            'tipe' => 'required|in:disposisi,naikan',
            'petunjuk' => $petunjukRules,
            'catatan' => 'nullable|string',
        ]);

        $suratMasuk = SuratMasuk::findOrFail($request->surat_masuk_id);
        if (!$user->canViewSuratMasuk($suratMasuk) || !$user->canForwardSuratMasuk($suratMasuk)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk memproses surat ini.',
            ], 403);
        }

        $kepadaUser = User::find($request->kepada_user_id);
        $targetIds = $user->jabatan ? $user->jabatan->getTargetDisposisi() : [];

        if (!$kepadaUser || !$kepadaUser->jabatan_id || !in_array($kepadaUser->jabatan_id, $targetIds)) {
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
        ]);

        // Update surat masuk status
        $suratMasuk->update(['status' => 'didisposisi']);

        // Send WA notification to recipient
        $this->waService->notifyDisposisi($disposisi, $kepadaUser);

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
            'status' => 'required|in:dibaca,ditindaklanjuti',
        ];

        if ($request->status === 'ditindaklanjuti') {
            $rules['catatan_tindak_lanjut'] = 'required|string';
        } else {
            $rules['catatan_tindak_lanjut'] = 'nullable|string';
        }

        $request->validate($rules);

        $disposisi->update([
            'status' => $request->status,
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

        return response()->json([
            'success' => true,
            'message' => 'Status disposisi berhasil diperbarui.',
        ]);
    }

    public function getTargets()
    {
        $user = auth()->user();
        $targets = [];

        if ($user->jabatan) {
            $targetIds = $user->jabatan->getTargetDisposisi();
            if (empty($targetIds)) {
                return response()->json([]);
            }
            $targetJabatans = Jabatan::whereIn('id', $targetIds)->with('users')->get();

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
        }

        return response()->json($targets);
    }
}
