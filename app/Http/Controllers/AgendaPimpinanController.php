<?php

namespace App\Http\Controllers;

use App\AgendaPimpinan;
use App\Services\WhatsAppNotificationService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgendaPimpinanController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppNotificationService $whatsAppService)
    {
        $this->middleware('auth');
        $this->whatsAppService = $whatsAppService;
    }

    public function index()
    {
        abort_unless(auth()->user()->canAccessAgendaPimpinan(), 403);

        $agendas = AgendaPimpinan::with(['creator', 'suratMasuk', 'recipients.jabatan'])
            ->orderByDesc('tanggal_kegiatan')
            ->orderByDesc('waktu')
            ->get();

        $users = User::with(['jabatan', 'unit'])
            ->active()
            ->ordered()
            ->get();

        return view('rapat.agenda.index', compact('agendas', 'users'));
    }

    public function store(Request $request)
    {
        abort_unless($this->canManageAgendaDetails(auth()->user()), 403);

        $data = $this->validateData($request);

        $agenda = AgendaPimpinan::create([
            'tanggal_kegiatan' => $data['tanggal_kegiatan'],
            'judul_agenda' => $data['judul_agenda'],
            'tempat' => $data['tempat'],
            'waktu' => $data['waktu'],
            'nomor_naskah_dinas' => $data['nomor_naskah_dinas'] ?? null,
            'lampiran_link' => $data['lampiran_link'] ?? null,
            'catatan' => $data['catatan'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $orderedRecipients = $this->syncRecipients($agenda, $data['recipient_ids'] ?? []);
        $agenda->update([
            'yang_menghadiri' => $this->formatAttendeeNames($orderedRecipients),
        ]);

        return back()->with('success', 'Agenda pimpinan berhasil disimpan.');
    }

    public function update(Request $request, AgendaPimpinan $agenda)
    {
        abort_unless($this->canManageAgendaDetails(auth()->user()), 403);

        $data = $this->validateData($request);

        $agenda->update([
            'tanggal_kegiatan' => $data['tanggal_kegiatan'],
            'judul_agenda' => $data['judul_agenda'],
            'tempat' => $data['tempat'],
            'waktu' => $data['waktu'],
            'nomor_naskah_dinas' => $data['nomor_naskah_dinas'] ?? null,
            'lampiran_link' => $data['lampiran_link'] ?? null,
            'catatan' => $data['catatan'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        $orderedRecipients = $this->syncRecipients($agenda, $data['recipient_ids'] ?? []);
        $agenda->update([
            'yang_menghadiri' => $this->formatAttendeeNames($orderedRecipients),
        ]);

        return back()->with('success', 'Agenda pimpinan berhasil diperbarui.');
    }

    public function destroy(AgendaPimpinan $agenda)
    {
        abort_unless($this->canManageAgendaDetails(auth()->user()), 403);

        $agenda->recipients()->detach();
        $agenda->delete();

        return back()->with('success', 'Agenda pimpinan berhasil dihapus.');
    }

    public function updateParticipants(Request $request, AgendaPimpinan $agenda)
    {
        abort_unless(auth()->user()->canAccessAgendaPimpinan(), 403);

        $data = $request->validate([
            'seragam_pakaian' => ['nullable', 'string', 'max:255'],
            'recipient_ids' => ['nullable', 'array'],
            'recipient_ids.*' => [Rule::exists('users', 'id')->where('status_aktif_pegawai', true)],
        ]);

        $orderedRecipients = $this->syncRecipients($agenda, $data['recipient_ids'] ?? []);

        $agenda->update([
            'yang_menghadiri' => $this->formatAttendeeNames($orderedRecipients),
            'seragam_pakaian' => $data['seragam_pakaian'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Peserta agenda pimpinan berhasil diperbarui.');
    }

    public function sendWhatsapp(AgendaPimpinan $agenda)
    {
        abort_unless(auth()->user()->canAccessAgendaPimpinan(), 403);

        $result = $this->whatsAppService->notifyAgendaPimpinan($agenda->load('recipients.jabatan'));
        $processed = $result['attempted'] > 0 || !$this->whatsAppService->isConfigured();

        return back()->with(
            $processed ? 'success' : 'error',
            $processed
                ? 'Notifikasi agenda pimpinan diproses untuk ' . $result['attempted'] . ' penerima.'
                : 'Tidak ada penerima agenda yang memiliki nomor WhatsApp.'
        );
    }

    protected function validateData(Request $request)
    {
        return $request->validate([
            'tanggal_kegiatan' => ['required', 'date'],
            'judul_agenda' => ['required', 'string', 'max:255'],
            'tempat' => ['required', 'string', 'max:255'],
            'waktu' => ['required', 'date_format:H:i'],
            'nomor_naskah_dinas' => ['nullable', 'string', 'max:255'],
            'lampiran_link' => ['nullable', 'url', 'max:1000'],
            'catatan' => ['nullable', 'string'],
            'recipient_ids' => ['nullable', 'array'],
            'recipient_ids.*' => [Rule::exists('users', 'id')->where('status_aktif_pegawai', true)],
        ]);
    }

    protected function syncRecipients(AgendaPimpinan $agenda, array $recipientIds)
    {
        $orderedUsers = User::whereIn('id', $recipientIds)
            ->active()
            ->ordered()
            ->get();

        $syncData = [];
        foreach ($orderedUsers as $index => $user) {
            $syncData[$user->id] = ['urutan' => $index + 1];
        }

        $agenda->recipients()->sync($syncData);

        return $orderedUsers;
    }

    protected function formatAttendeeNames($users)
    {
        if ($users->isEmpty()) {
            return null;
        }

        return $users->pluck('name')->implode(', ');
    }

    protected function canManageAgendaDetails($user)
    {
        return $user && ($user->isSuperAdmin() || $user->isMeetingAdmin());
    }
}
