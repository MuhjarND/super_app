<?php

namespace App\Http\Controllers;

use App\AgendaPimpinan;
use App\Services\WhatsAppNotificationService;
use App\User;
use Illuminate\Http\Request;

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

        $agendas = AgendaPimpinan::with(['creator', 'recipients.jabatan'])
            ->orderByDesc('tanggal_kegiatan')
            ->orderByDesc('waktu')
            ->get();

        $users = User::with(['jabatan', 'unit', 'bidang'])
            ->orderBy('hirarki')
            ->orderBy('name')
            ->get();

        return view('rapat.agenda.index', compact('agendas', 'users'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAccessAgendaPimpinan(), 403);

        $data = $this->validateData($request);

        $agenda = AgendaPimpinan::create([
            'tanggal_kegiatan' => $data['tanggal_kegiatan'],
            'judul_agenda' => $data['judul_agenda'],
            'tempat' => $data['tempat'],
            'waktu' => $data['waktu'],
            'yang_menghadiri' => $data['yang_menghadiri'] ?? null,
            'seragam_pakaian' => $data['seragam_pakaian'] ?? null,
            'nomor_naskah_dinas' => $data['nomor_naskah_dinas'] ?? null,
            'lampiran_link' => $data['lampiran_link'] ?? null,
            'catatan' => $data['catatan'] ?? null,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->syncRecipients($agenda, $data['recipient_ids'] ?? []);

        return back()->with('success', 'Agenda pimpinan berhasil disimpan.');
    }

    public function update(Request $request, AgendaPimpinan $agenda)
    {
        abort_unless(auth()->user()->canAccessAgendaPimpinan(), 403);

        $data = $this->validateData($request);

        $agenda->update([
            'tanggal_kegiatan' => $data['tanggal_kegiatan'],
            'judul_agenda' => $data['judul_agenda'],
            'tempat' => $data['tempat'],
            'waktu' => $data['waktu'],
            'yang_menghadiri' => $data['yang_menghadiri'] ?? null,
            'seragam_pakaian' => $data['seragam_pakaian'] ?? null,
            'nomor_naskah_dinas' => $data['nomor_naskah_dinas'] ?? null,
            'lampiran_link' => $data['lampiran_link'] ?? null,
            'catatan' => $data['catatan'] ?? null,
            'updated_by' => auth()->id(),
        ]);

        $this->syncRecipients($agenda, $data['recipient_ids'] ?? []);

        return back()->with('success', 'Agenda pimpinan berhasil diperbarui.');
    }

    public function destroy(AgendaPimpinan $agenda)
    {
        abort_unless(auth()->user()->canAccessAgendaPimpinan(), 403);

        $agenda->recipients()->detach();
        $agenda->delete();

        return back()->with('success', 'Agenda pimpinan berhasil dihapus.');
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
            'yang_menghadiri' => ['nullable', 'string'],
            'seragam_pakaian' => ['nullable', 'string', 'max:255'],
            'nomor_naskah_dinas' => ['nullable', 'string', 'max:255'],
            'lampiran_link' => ['nullable', 'url', 'max:1000'],
            'catatan' => ['nullable', 'string'],
            'recipient_ids' => ['nullable', 'array'],
            'recipient_ids.*' => ['exists:users,id'],
        ]);
    }

    protected function syncRecipients(AgendaPimpinan $agenda, array $recipientIds)
    {
        $orderedUsers = User::whereIn('id', $recipientIds)
            ->orderBy('hirarki')
            ->orderBy('name')
            ->get();

        $syncData = [];
        foreach ($orderedUsers as $index => $user) {
            $syncData[$user->id] = ['urutan' => $index + 1];
        }

        $agenda->recipients()->sync($syncData);
    }
}
