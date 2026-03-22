<?php

namespace App\Services;

use App\AgendaPimpinan;
use App\Rapat;
use App\RapatApproval;
use App\User;
use App\Voting;
use App\WhatsAppNotificationLog;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    protected $apiUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url');
        $this->apiKey = config('services.whatsapp.api_key');
    }

    public function send($phoneNumber, $message, array $context = [])
    {
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);
        $log = $this->createLog($normalizedPhone, $message, $context);

        if (!$normalizedPhone) {
            $log->update([
                'status' => 'skipped',
                'response_body' => 'Nomor WhatsApp kosong atau tidak valid.',
            ]);

            return false;
        }

        if (empty($this->apiUrl) || empty($this->apiKey)) {
            Log::info('[WA Notification] API not configured. Message to ' . $normalizedPhone . ': ' . $message);
            $log->update([
                'status' => 'skipped',
                'response_body' => 'API WhatsApp belum dikonfigurasi.',
            ]);

            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->post($this->apiUrl, [
                'target' => $normalizedPhone,
                'message' => $message,
            ]);

            $log->update([
                'status' => $response->successful() ? 'sent' : 'failed',
                'response_body' => (string) $response->body(),
                'sent_at' => Carbon::now('Asia/Jayapura'),
            ]);

            Log::info('[WA Notification] Sent to ' . $normalizedPhone . ': ' . ($response->successful() ? 'Success' : 'Failed'));

            return $response->successful();
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'response_body' => $e->getMessage(),
            ]);

            Log::error('[WA Notification] Error: ' . $e->getMessage());

            return false;
        }
    }

    public function sendToUser($targetUser, $message, array $context = [])
    {
        if (!$targetUser instanceof User) {
            return false;
        }

        $context = array_merge($context, [
            'target_user_id' => $targetUser->id,
            'target_name' => $targetUser->name,
        ]);

        return $this->send($targetUser->no_hp, $message, $context);
    }

    public function notifySuratMasuk($suratMasuk, $targetUser)
    {
        $url = url('/surat-masuk/' . $suratMasuk->id);
        $message = $this->wrap([
            'Informasi Surat Masuk',
            '',
            'No. Surat: ' . $suratMasuk->nomor_surat,
            'Pengirim: ' . $suratMasuk->pengirim,
            'Perihal: ' . $suratMasuk->perihal,
            'Tanggal Surat: ' . $this->formatDateValue($suratMasuk->tanggal_surat),
            'Sifat: ' . ucfirst((string) $suratMasuk->sifat),
            '',
            'Tindak lanjut: ' . $url,
        ]);

        return $this->sendToUser($targetUser, $message, [
            'module' => 'persuratan',
            'event' => 'surat_masuk',
            'notifiable_type' => get_class($suratMasuk),
            'notifiable_id' => $suratMasuk->id,
        ]);
    }

    public function notifyDisposisi($disposisi, $targetUser)
    {
        $suratMasuk = $disposisi->suratMasuk;
        $dari = $disposisi->dariUser;
        $url = url('/surat-masuk/' . $suratMasuk->id);
        $tipe = $disposisi->tipe === 'naikan' ? 'Surat Dinaikkan' : 'Disposisi Surat';

        $lines = [
            $tipe,
            '',
            'Dari: ' . optional($dari)->name,
            'No. Surat: ' . $suratMasuk->nomor_surat,
            'Pengirim: ' . $suratMasuk->pengirim,
            'Perihal: ' . $suratMasuk->perihal,
        ];

        if ($disposisi->petunjuk) {
            $lines[] = 'Petunjuk: ' . $disposisi->petunjuk;
        }

        if ($disposisi->catatan) {
            $lines[] = 'Catatan: ' . $disposisi->catatan;
        }

        $lines[] = '';
        $lines[] = 'Tindak lanjut: ' . $url;

        return $this->sendToUser($targetUser, $this->wrap($lines), [
            'module' => 'persuratan',
            'event' => 'disposisi',
            'notifiable_type' => get_class($disposisi),
            'notifiable_id' => $disposisi->id,
        ]);
    }

    public function notifyRapatApprovalPending(RapatApproval $approval)
    {
        $approval->loadMissing(['rapat.creator', 'rapat.kategoriSuratKode', 'approver.jabatan']);

        if ($approval->notified_at) {
            return false;
        }

        $rapat = $approval->rapat;
        $message = $this->wrap([
            'Dokumen Rapat Menunggu Approval',
            '',
            'Step Approval: ' . $approval->step_order,
            'Judul Rapat: ' . $rapat->judul,
            'Nomor Undangan: ' . $rapat->nomor_undangan,
            'Kategori Surat: ' . $rapat->kategori_surat_label,
            'Tanggal: ' . $this->formatDateValue($rapat->tanggal),
            'Waktu: ' . $rapat->waktu_mulai_formatted . ' WIT',
            'Tempat: ' . $rapat->tempat,
            'Pengusul: ' . optional($rapat->creator)->name,
            '',
            'Preview dokumen: ' . route('approval.index', ['category' => 'undangan']),
        ]);

        $sent = $this->sendToUser($approval->approver, $message, [
            'module' => 'rapat',
            'event' => 'approval_pending',
            'notifiable_type' => get_class($approval),
            'notifiable_id' => $approval->id,
        ]);

        if ($sent || !$this->isConfigured()) {
            $approval->forceFill([
                'notified_at' => Carbon::now('Asia/Jayapura'),
            ])->save();
        }

        return $sent;
    }

    public function notifyRapatRejected(Rapat $rapat, $catatan = null)
    {
        $rapat->loadMissing(['creator', 'approver1', 'approver2']);

        $lines = [
            'Approval Rapat Ditolak',
            '',
            'Judul Rapat: ' . $rapat->judul,
            'Nomor Undangan: ' . $rapat->nomor_undangan,
            'Tanggal: ' . $this->formatDateValue($rapat->tanggal),
            'Waktu: ' . $rapat->waktu_mulai_formatted . ' WIT',
            'Tempat: ' . $rapat->tempat,
        ];

        if ($catatan) {
            $lines[] = 'Catatan Reject: ' . $catatan;
        }

        $lines[] = '';
        $lines[] = 'Silakan perbarui dokumen rapat pada aplikasi.';

        return $this->sendToUser($rapat->creator, $this->wrap($lines), [
            'module' => 'rapat',
            'event' => 'approval_rejected',
            'notifiable_type' => get_class($rapat),
            'notifiable_id' => $rapat->id,
        ]);
    }

    public function notifyRapatParticipants(Rapat $rapat)
    {
        $rapat->loadMissing(['kategoriSuratKode', 'pesertas.jabatan']);

        if ($rapat->participant_notified_at) {
            return false;
        }

        $lines = [
            'Informasi Rapat',
            '',
            'Judul: ' . $rapat->judul,
            'Nomor Undangan: ' . $rapat->nomor_undangan,
            'Kategori Surat: ' . $rapat->kategori_surat_label,
            'Tanggal: ' . $this->formatDateValue($rapat->tanggal),
            'Waktu: ' . $rapat->waktu_mulai_formatted . ' WIT',
            'Tempat: ' . $rapat->tempat,
        ];

        if ($rapat->jenis_pakaian) {
            $lines[] = 'Pakaian: ' . $rapat->jenis_pakaian;
        }

        if ($rapat->is_virtual) {
            $lines[] = 'Meeting ID: ' . $rapat->meeting_id;
            $lines[] = 'Passcode: ' . $rapat->meeting_passcode;
        }

        if ($rapat->deskripsi) {
            $lines[] = 'Deskripsi: ' . trim(preg_replace('/\s+/', ' ', $rapat->deskripsi));
        }

        $lines[] = '';
        $lines[] = 'Mohon hadir tepat waktu sesuai jadwal.';

        $message = $this->wrap($lines);
        $users = $rapat->pesertas->filter(function ($user) {
            return !empty($user->no_hp);
        });

        $result = $this->sendBulk($users, $message, [
            'module' => 'rapat',
            'event' => 'participant_invitation',
            'notifiable_type' => get_class($rapat),
            'notifiable_id' => $rapat->id,
        ]);

        if ($result['attempted'] > 0 || !$this->isConfigured()) {
            $rapat->forceFill([
                'participant_notified_at' => Carbon::now('Asia/Jayapura'),
            ])->save();
        }

        return $result['success'] > 0;
    }

    public function notifyAttendanceReminder(Rapat $rapat, $users = null)
    {
        $rapat->loadMissing(['pesertas', 'internalAttendances']);

        $attendanceUrl = route('rapat.absensi.public.show', $rapat->public_code);
        $message = $this->wrap([
            'Pengingat Absensi Rapat',
            '',
            'Judul: ' . $rapat->judul,
            'Tanggal: ' . $this->formatDateValue($rapat->tanggal),
            'Waktu: ' . $rapat->waktu_mulai_formatted . ' WIT',
            '',
            'Link absensi: ' . $attendanceUrl,
        ]);

        if ($users === null) {
            $presentIds = $rapat->internalAttendances->pluck('user_id')->filter()->all();
            $users = $rapat->pesertas->reject(function ($user) use ($presentIds) {
                return in_array($user->id, $presentIds, true);
            });
        }

        $users = $users instanceof Collection ? $users : collect($users);
        $result = $this->sendBulk($users->filter(function ($user) {
            return !empty($user->no_hp);
        }), $message, [
            'module' => 'rapat',
            'event' => 'attendance_reminder',
            'notifiable_type' => get_class($rapat),
            'notifiable_id' => $rapat->id,
        ]);

        $rapat->forceFill([
            'last_attendance_reminder_at' => Carbon::now('Asia/Jayapura'),
        ])->save();

        return $result;
    }

    public function notifyAgendaPimpinan(AgendaPimpinan $agenda)
    {
        $agenda->loadMissing(['recipients.jabatan']);

        $message = $agenda->whatsapp_preview;
        $result = $this->sendBulk($agenda->recipients->filter(function ($user) {
            return !empty($user->no_hp);
        }), $message, [
            'module' => 'agenda_pimpinan',
            'event' => 'agenda_notification',
            'notifiable_type' => get_class($agenda),
            'notifiable_id' => $agenda->id,
        ]);

        $agenda->forceFill([
            'last_notified_at' => Carbon::now('Asia/Jayapura'),
        ])->save();

        return $result;
    }

    public function notifyVotingParticipants(Voting $voting)
    {
        $voting->loadMissing(['participantPivots.user', 'items']);

        if ($voting->participant_notified_at) {
            return false;
        }

        $message = $this->wrap([
            'Informasi E-Voting',
            '',
            'Judul Voting: ' . $voting->judul,
            'Jumlah Item: ' . $voting->items->count(),
            'Status: ' . ucfirst((string) $voting->status),
            '',
            'Link voting: ' . route('rapat.voting.public.show', $voting->public_code),
        ]);

        $users = $voting->participantPivots
            ->map(function ($pivot) {
                return $pivot->user;
            })
            ->filter(function ($user) {
                return $user && !empty($user->no_hp);
            });

        $result = $this->sendBulk($users, $message, [
            'module' => 'voting',
            'event' => 'participant_invitation',
            'notifiable_type' => get_class($voting),
            'notifiable_id' => $voting->id,
        ]);

        if ($result['attempted'] > 0 || !$this->isConfigured()) {
            $voting->forceFill([
                'participant_notified_at' => Carbon::now('Asia/Jayapura'),
            ])->save();
        }

        return $result['success'] > 0;
    }

    public function notifyLoginInfo(User $user)
    {
        $message = $this->wrap([
            'Informasi Login Aplikasi',
            '',
            'Nama: ' . $user->name,
            'Email/Username: ' . $user->email,
            'Password standar: ptapabar',
            '',
            'Jika password Anda pernah diubah sebelumnya, gunakan password terakhir yang berlaku.',
            'Halaman login: ' . url('/login'),
        ]);

        return $this->sendToUser($user, $message, [
            'module' => 'master_data',
            'event' => 'login_info',
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
        ]);
    }

    public function isConfigured()
    {
        return !empty($this->apiUrl) && !empty($this->apiKey);
    }

    protected function sendBulk($users, $message, array $context = [])
    {
        $users = $users instanceof Collection ? $users : collect($users);

        $result = [
            'attempted' => 0,
            'success' => 0,
        ];

        foreach ($users as $user) {
            if (!$user instanceof User || empty($user->no_hp)) {
                continue;
            }

            $result['attempted']++;
            if ($this->sendToUser($user, $message, $context)) {
                $result['success']++;
            }
        }

        return $result;
    }

    protected function createLog($phoneNumber, $message, array $context = [])
    {
        return WhatsAppNotificationLog::create([
            'module' => $context['module'] ?? 'general',
            'event' => $context['event'] ?? 'message',
            'notifiable_type' => $context['notifiable_type'] ?? null,
            'notifiable_id' => $context['notifiable_id'] ?? null,
            'target_user_id' => $context['target_user_id'] ?? null,
            'target_name' => $context['target_name'] ?? null,
            'phone_number' => $phoneNumber,
            'message' => $message,
            'status' => 'queued',
            'created_by' => auth()->id(),
        ]);
    }

    protected function wrap(array $lines)
    {
        return "*[SMART NOTIF]*\n" . implode("\n", $lines) . "\n\n*- SMART PTA Papua Barat*";
    }

    protected function normalizePhoneNumber($phoneNumber)
    {
        $phoneNumber = preg_replace('/[^0-9]/', '', (string) $phoneNumber);

        if ($phoneNumber === '') {
            return null;
        }

        if (strpos($phoneNumber, '0') === 0) {
            return '62' . substr($phoneNumber, 1);
        }

        if (strpos($phoneNumber, '62') === 0) {
            return $phoneNumber;
        }

        return $phoneNumber;
    }

    protected function formatDateValue($value)
    {
        if (!$value) {
            return '-';
        }

        if ($value instanceof Carbon) {
            return $value->copy()->timezone('Asia/Jayapura')->translatedFormat('d M Y');
        }

        try {
            return Carbon::parse($value, 'Asia/Jayapura')->translatedFormat('d M Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}
