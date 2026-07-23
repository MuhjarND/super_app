<?php

namespace App\Http\Controllers;

use App\Rapat;
use App\RapatAttendance;
use App\Services\RapatDocumentService;
use App\Services\WhatsAppNotificationService;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class RapatAbsensiController extends Controller
{
    protected $whatsAppService;
    protected $documentService;

    public function __construct(WhatsAppNotificationService $whatsAppService, RapatDocumentService $documentService)
    {
        $this->middleware('auth')->except(['publicShow', 'publicStore', 'publicStoreGuest', 'verifyAttendance']);
        $this->whatsAppService = $whatsAppService;
        $this->documentService = $documentService;
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

        return redirect()->away($this->attendanceVerificationUrl($attendance));
    }

    public function verifyAttendance(RapatAttendance $attendance)
    {
        $attendance->loadMissing(['rapat', 'user.jabatan']);

        return view('rapat.verification.attendance', compact('attendance'));
    }

    public function pdf(Rapat $rapat)
    {
        abort_unless(auth()->user()->canViewRapat($rapat), 403);

        $rapat->load([
            'creator',
            'kategoriSuratKode',
            'approvals.approver.jabatan',
            'approver1.jabatan',
            'approver2.jabatan',
            'pesertas' => function ($query) {
                $query->orderBy('rapat_peserta.urutan');
            },
            'pesertas.jabatan',
            'internalAttendances',
            'guestAttendances',
        ]);

        $attendanceByUser = $rapat->internalAttendances->keyBy('user_id');
        $internalParticipants = $rapat->pesertas->map(function ($participant) use ($attendanceByUser) {
            $attendance = $attendanceByUser->get($participant->id);

            return [
                'user' => $participant,
                'attendance' => $attendance,
            ];
        });

        $guestAttendances = $rapat->guestAttendances->sortBy('attended_at')->values();

        $attendanceRows = $internalParticipants->map(function ($item) {
            $attendance = $item['attendance'];
            $user = $item['user'];

            return [
                'name' => $user->name,
                'description' => $user->jabatan_keterangan ?: optional($user->jabatan)->nama ?: '-',
                'attended_at' => optional($attendance)->attended_at,
            ];
        })->concat($guestAttendances->map(function ($attendance) {
            return [
                'name' => $attendance->participant_name_snapshot,
                'description' => $attendance->guest_instansi ?: ($attendance->participant_jabatan_snapshot ?: '-'),
                'attended_at' => $attendance->attended_at,
            ];
        }))->values();

        $attendanceApproved = $this->documentService->shouldUseSignedDocument($rapat);
        $pimpinanSignature = $this->documentService->buildApprovalSignatureData($rapat, $attendanceApproved);
        $hasApprovalSignature = $attendanceApproved
            && !empty($pimpinanSignature['image'])
            && !empty($pimpinanSignature['name'])
            && $pimpinanSignature['name'] !== '-';
        $signers = $hasApprovalSignature
            ? [[
                'name' => $pimpinanSignature['name'],
                'role' => 'Penanda Tangan Absensi',
                'title' => trim(($pimpinanSignature['line1'] ?? '') . ' ' . ($pimpinanSignature['line2'] ?? '')),
                'signed_at' => !empty($pimpinanSignature['signed_at'])
                    ? $pimpinanSignature['signed_at']->translatedFormat('d F Y H:i') . ' WIT'
                    : '-',
            ]]
            : [];

        $kopImage = $this->resolveKopAbsenImage();
        $verifier = app(\App\Services\PdfVerificationService::class);
        $verification = $verifier->begin('rapat', 'laporan_absensi', $rapat->id, 'Laporan Absensi Rapat - ' . ($rapat->judul ?: $rapat->id), $signers, [
            'tanggal' => optional($rapat->tanggal)->toDateString(),
            'status_rapat' => $rapat->status,
        ]);
        $pdfVerification = $verifier->viewData($verification);

        $pdf = PDF::loadView('rapat.absensi.pdf', compact(
            'rapat',
            'attendanceRows',
            'hasApprovalSignature',
            'pimpinanSignature',
            'kopImage',
            'pdfVerification'
        ))->setPaper('a4', 'portrait');

        return $verifier->response($pdf->output(), $verification, 'laporan-absensi-rapat-' . $rapat->id . '.pdf');
    }

    public function remindPending(Rapat $rapat)
    {
        abort_unless(
            (auth()->user()->canManageRapat() || auth()->user()->canManageMeetingMinutes())
                && auth()->user()->canViewRapat($rapat),
            403
        );

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
        ], [
            'user_id.required' => 'Nama peserta wajib dipilih.',
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

        RapatAttendance::create([
            'rapat_id' => $rapat->id,
            'user_id' => $participant->id,
            'attendance_type' => 'internal',
            'participant_name_snapshot' => $participant->name,
            'participant_jabatan_snapshot' => $participant->jabatan_keterangan ?: optional($participant->jabatan)->nama,
            'source' => 'public',
            'signature_path' => null,
            'signature_mime' => null,
            'signature_size' => null,
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
        ], [
            'guest_name.required' => 'Nama tamu wajib diisi.',
        ]);

        RapatAttendance::create([
            'rapat_id' => $rapat->id,
            'attendance_type' => 'guest',
            'participant_name_snapshot' => $data['guest_name'],
            'participant_jabatan_snapshot' => $data['guest_instansi'] ?? null,
            'guest_instansi' => $data['guest_instansi'] ?? null,
            'source' => 'guest',
            'signature_path' => null,
            'signature_mime' => null,
            'signature_size' => null,
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

    protected function attendanceVerificationUrl(RapatAttendance $attendance)
    {
        return URL::signedRoute('rapat.attendance.verify', ['attendance' => $attendance->id]);
    }
}
