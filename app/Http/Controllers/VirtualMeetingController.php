<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppNotificationService;
use App\User;
use App\VirtualMeeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class VirtualMeetingController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppNotificationService $whatsAppService)
    {
        $this->middleware('auth');
        $this->whatsAppService = $whatsAppService;
    }

    public function index(Request $request)
    {
        abort_unless($request->user()->canAccessVirtualMeetings(), 403);

        $meetings = VirtualMeeting::visibleTo($request->user())
            ->with(['creator', 'suratMasuk', 'participants.jabatan'])
            ->orderByDesc('tanggal_kegiatan')
            ->orderByDesc('waktu_mulai')
            ->get();

        $users = User::with('jabatan')->active()->ordered()->get();

        return view('rapat.virtual-meetings.index', [
            'meetings' => $meetings,
            'users' => $users,
            'canManage' => $request->user()->canManageVirtualMeetings(),
        ]);
    }

    public function update(Request $request, VirtualMeeting $virtualMeeting)
    {
        abort_unless($request->user()->canManageVirtualMeetings(), 403);

        $data = $this->validateData($request);

        DB::transaction(function () use ($virtualMeeting, $data, $request) {
            $virtualMeeting->update([
                'judul' => $data['judul'],
                'tanggal_kegiatan' => $data['tanggal_kegiatan'],
                'waktu_mulai' => $data['waktu_mulai'],
                'waktu_selesai' => $data['waktu_selesai'] ?? null,
                'zoom_link' => $data['zoom_link'],
                'catatan' => $data['catatan'] ?? null,
                'updated_by' => $request->user()->id,
            ]);

            $this->syncParticipants($virtualMeeting, $data['participant_ids']);
        });

        return back()->with('success', 'Agenda virtual berhasil diperbarui. Gunakan tombol kirim untuk memberitahu peserta kembali.');
    }

    public function sendWhatsapp(Request $request, VirtualMeeting $virtualMeeting)
    {
        abort_unless($request->user()->canManageVirtualMeetings(), 403);

        $result = $this->whatsAppService->notifyVirtualMeetingParticipants(
            $virtualMeeting->load('participants.jabatan')
        );
        $processed = $result['attempted'] > 0 || !$this->whatsAppService->isConfigured();

        return back()->with(
            $processed ? 'success' : 'error',
            $processed
                ? 'Notifikasi agenda virtual diproses untuk ' . $result['attempted'] . ' peserta.'
                : 'Tidak ada peserta yang memiliki nomor WhatsApp.'
        );
    }

    public function destroy(Request $request, VirtualMeeting $virtualMeeting)
    {
        abort_unless($request->user()->canManageVirtualMeetings(), 403);

        $virtualMeeting->delete();

        return back()->with('success', 'Agenda virtual berhasil dihapus.');
    }

    protected function validateData(Request $request)
    {
        return $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'tanggal_kegiatan' => ['required', 'date'],
            'waktu_mulai' => ['required', 'date_format:H:i'],
            'waktu_selesai' => ['nullable', 'date_format:H:i', 'after:waktu_mulai'],
            'zoom_link' => ['required', 'url', 'max:2000'],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'participant_ids' => ['required', 'array', 'min:1'],
            'participant_ids.*' => [Rule::exists('users', 'id')->where('status_aktif_pegawai', true)],
        ]);
    }

    protected function syncParticipants(VirtualMeeting $meeting, array $participantIds)
    {
        $participants = User::whereIn('id', array_unique($participantIds))
            ->active()
            ->ordered()
            ->get();

        $syncData = [];
        foreach ($participants as $index => $participant) {
            $syncData[$participant->id] = ['urutan' => $index + 1];
        }

        $meeting->participants()->sync($syncData);
    }
}
