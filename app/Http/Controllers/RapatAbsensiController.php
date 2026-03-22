<?php

namespace App\Http\Controllers;

use App\Rapat;
use App\RapatAttendance;
use App\Services\WhatsAppNotificationService;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RapatAbsensiController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppNotificationService $whatsAppService)
    {
        $this->middleware('auth')->except(['publicShow', 'publicStore', 'publicStoreGuest']);
        $this->whatsAppService = $whatsAppService;
    }

    public function index()
    {
        abort_unless(auth()->user()->canAccessMeetingModule(), 403);

        $rapats = Rapat::visibleTo(auth()->user())
            ->with(['kategoriSuratKode', 'creator', 'pesertas', 'internalAttendances', 'guestAttendances'])
            ->orderByDesc('tanggal')
            ->orderByDesc('waktu_mulai')
            ->get();

        return view('rapat.absensi.index', compact('rapats'));
    }

    public function show(Rapat $rapat)
    {
        abort_unless(auth()->user()->canViewRapat($rapat), 403);

        $rapat->load([
            'kategoriSuratKode',
            'creator',
            'pesertas' => function ($query) {
                $query->orderBy('rapat_peserta.urutan');
            },
            'attendances.user',
            'internalAttendances',
            'guestAttendances',
        ]);

        $attendanceByUser = $rapat->internalAttendances->keyBy('user_id');
        $internalParticipants = $rapat->pesertas->map(function ($participant) use ($attendanceByUser) {
            return [
                'user' => $participant,
                'attendance' => $attendanceByUser->get($participant->id),
            ];
        });

        $guestAttendances = $rapat->guestAttendances;
        $publicAttendanceUrl = route('rapat.absensi.public.show', $rapat->public_code);

        return view('rapat.absensi.show', compact('rapat', 'internalParticipants', 'guestAttendances', 'publicAttendanceUrl'));
    }

    public function signature(RapatAttendance $attendance)
    {
        abort_unless(auth()->user()->canViewRapat($attendance->rapat), 403);

        return response()->file(storage_path('app/public/' . $attendance->signature_path));
    }

    public function pdf(Rapat $rapat)
    {
        abort_unless(auth()->user()->canViewRapat($rapat), 403);

        $rapat->load([
            'creator',
            'kategoriSuratKode',
            'pesertas' => function ($query) {
                $query->orderBy('rapat_peserta.urutan');
            },
            'internalAttendances',
            'guestAttendances',
        ]);

        $attendanceByUser = $rapat->internalAttendances->keyBy('user_id');
        $internalParticipants = $rapat->pesertas->map(function ($participant) use ($attendanceByUser) {
            $attendance = $attendanceByUser->get($participant->id);

            return [
                'user' => $participant,
                'attendance' => $attendance,
                'signature_data_uri' => $attendance ? $this->resolveStorageFileDataUri($attendance->signature_path) : null,
            ];
        });

        $guestAttendances = $rapat->guestAttendances->sortBy('attended_at')->values()->map(function ($attendance) {
            $attendance->signature_data_uri = $this->resolveStorageFileDataUri($attendance->signature_path);
            return $attendance;
        });
        $kopImage = $this->resolveKopAbsenImage();

        $pdf = PDF::loadView('rapat.absensi.pdf', compact(
            'rapat',
            'internalParticipants',
            'guestAttendances',
            'kopImage'
        ))->setPaper('a4', 'portrait');

        return $pdf->stream('laporan-absensi-rapat-' . $rapat->id . '.pdf');
    }

    public function remindPending(Rapat $rapat)
    {
        abort_unless(auth()->user()->canViewRapat($rapat), 403);

        $result = $this->whatsAppService->notifyAttendanceReminder($rapat->fresh(['pesertas', 'internalAttendances']));
        $processed = $result['attempted'] > 0 || !$this->whatsAppService->isConfigured();

        return back()->with(
            $processed ? 'success' : 'error',
            $processed
                ? 'Pengingat absensi diproses untuk ' . $result['attempted'] . ' peserta yang belum absen.'
                : 'Tidak ada peserta yang belum absen atau memiliki nomor WhatsApp.'
        );
    }

    public function publicShow($publicCode)
    {
        $rapat = $this->findPublicRapat($publicCode);
        $rapat->load([
            'kategoriSuratKode',
            'pesertas' => function ($query) {
                $query->orderBy('rapat_peserta.urutan');
            },
            'internalAttendances',
            'guestAttendances',
        ]);

        return view('rapat.absensi.public', compact('rapat'));
    }

    public function publicStore(Request $request, $publicCode)
    {
        $rapat = $this->findPublicRapat($publicCode);

        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'signature_data' => ['required', 'string'],
        ], [
            'user_id.required' => 'Nama peserta wajib dipilih.',
            'signature_data.required' => 'Tanda tangan wajib diisi.',
        ]);

        $participant = $rapat->pesertas()->where('users.id', $data['user_id'])->first();
        if (!$participant) {
            return response()->json([
                'message' => 'Peserta yang dipilih tidak terdaftar pada rapat ini.',
            ], 422);
        }

        if ($rapat->internalAttendances()->where('user_id', $participant->id)->exists()) {
            return response()->json([
                'message' => 'Peserta tersebut sudah tercatat hadir.',
            ], 422);
        }

        $signature = $this->storeSignature($data['signature_data'], 'internal');

        RapatAttendance::create([
            'rapat_id' => $rapat->id,
            'user_id' => $participant->id,
            'attendance_type' => 'internal',
            'participant_name_snapshot' => $participant->name,
            'participant_jabatan_snapshot' => $participant->jabatan_keterangan ?: optional($participant->jabatan)->nama,
            'source' => 'public',
            'signature_path' => $signature['path'],
            'signature_mime' => $signature['mime'],
            'signature_size' => $signature['size'],
            'attended_at' => Carbon::now('Asia/Jayapura'),
            'created_ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Absensi peserta berhasil direkam.',
        ]);
    }

    public function publicStoreGuest(Request $request, $publicCode)
    {
        $rapat = $this->findPublicRapat($publicCode);

        $data = $request->validate([
            'guest_name' => ['required', 'string', 'max:255'],
            'guest_instansi' => ['nullable', 'string', 'max:255'],
            'signature_data' => ['required', 'string'],
        ], [
            'guest_name.required' => 'Nama tamu wajib diisi.',
            'signature_data.required' => 'Tanda tangan wajib diisi.',
        ]);

        $signature = $this->storeSignature($data['signature_data'], 'guest');

        RapatAttendance::create([
            'rapat_id' => $rapat->id,
            'attendance_type' => 'guest',
            'participant_name_snapshot' => $data['guest_name'],
            'participant_jabatan_snapshot' => $data['guest_instansi'] ?? null,
            'guest_instansi' => $data['guest_instansi'] ?? null,
            'source' => 'guest',
            'signature_path' => $signature['path'],
            'signature_mime' => $signature['mime'],
            'signature_size' => $signature['size'],
            'attended_at' => Carbon::now('Asia/Jayapura'),
            'created_ip' => $request->ip(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Absensi tamu berhasil direkam.',
        ]);
    }

    protected function findPublicRapat($publicCode)
    {
        return Rapat::where('public_code', $publicCode)->firstOrFail();
    }

    protected function storeSignature($dataUri, $prefix)
    {
        if (!preg_match('/^data:image\/png;base64,/', $dataUri)) {
            abort(422, 'Format tanda tangan tidak valid.');
        }

        $binary = base64_decode(preg_replace('/^data:image\/png;base64,/', '', $dataUri), true);
        if ($binary === false) {
            abort(422, 'Data tanda tangan tidak valid.');
        }

        $path = 'rapat/signatures/' . $prefix . '-' . Str::uuid() . '.png';
        Storage::disk('public')->put($path, $binary);

        return [
            'path' => $path,
            'mime' => 'image/png',
            'size' => strlen($binary),
        ];
    }

    protected function resolveKopAbsenImage()
    {
        $candidates = [
            public_path('kop_absen.jpg'),
            public_path('kop_absen.jpeg'),
            public_path('kop_absen.png'),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate && file_exists($candidate)) {
                $mime = mime_content_type($candidate) ?: 'image/jpeg';
                return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($candidate));
            }
        }

        return null;
    }

    protected function resolveStorageFileDataUri($relativePath)
    {
        if (!$relativePath) {
            return null;
        }

        $absolutePath = storage_path('app/public/' . ltrim($relativePath, '/'));
        if (!file_exists($absolutePath)) {
            return null;
        }

        $mime = mime_content_type($absolutePath) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absolutePath));
    }
}
