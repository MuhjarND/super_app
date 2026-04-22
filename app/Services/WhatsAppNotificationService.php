<?php

namespace App\Services;

use App\AgendaPimpinan;
use App\LeaveApproval;
use App\LeaveRequest;
use App\Rapat;
use App\RapatApproval;
use App\SuratKeluar;
use App\SuratKeluarApproval;
use App\User;
use App\Voting;
use App\WhatsAppNotificationLog;
use App\ZiActivity;
use App\ZiActivityApproval;
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

    public function notifyTwoFactorStatusChanged(User $targetUser, $enabled = true, $recoveryCodeCount = null)
    {
        $lines = [
            'Yth. Bapak/Ibu,',
            'Dengan hormat, berikut disampaikan pembaruan pengaturan keamanan akun Anda.',
            '',
            'Informasi: ' . ($enabled ? 'Authenticator 2 faktor telah diaktifkan.' : 'Authenticator 2 faktor telah dinonaktifkan.'),
            'Akun: ' . ($targetUser->username ?: $targetUser->email ?: $targetUser->name),
            'Waktu: ' . $this->formatDateTimeValue(now()),
        ];

        if ($enabled && $recoveryCodeCount !== null) {
            $lines[] = 'Backup recovery code aktif: ' . $recoveryCodeCount . ' kode.';
            $lines[] = 'Mohon simpan recovery code tersebut di tempat yang aman dan jangan dibagikan kepada pihak lain.';
        }

        $lines[] = '';
        $lines[] = 'Silakan meninjau pengaturan keamanan melalui tautan berikut:';
        $lines[] = route('two-factor.edit');
        $lines[] = '';
        $lines[] = 'Apabila perubahan ini bukan dilakukan oleh Anda, mohon segera ubah password akun dan hubungi administrator.';

        return $this->sendToUser($targetUser, $this->wrap($lines), [
            'module' => 'security',
            'event' => $enabled ? 'two_factor_enabled' : 'two_factor_disabled',
            'target_user_id' => $targetUser->id,
        ]);
    }

    public function notifySuratMasuk($suratMasuk, $targetUser)
    {
        $url = url('/surat-masuk/' . $suratMasuk->id);
        $message = $this->wrap([
            'Yth. Bapak/Ibu,',
            'Dengan hormat, disampaikan informasi surat masuk sebagai berikut:',
            '',
            'No. Surat: ' . $suratMasuk->nomor_surat,
            'Pengirim: ' . $suratMasuk->pengirim,
            'Perihal: ' . $suratMasuk->perihal,
            'Tanggal Surat: ' . $this->formatDateValue($suratMasuk->tanggal_surat),
            'Sifat: ' . ucfirst((string) $suratMasuk->sifat),
            '',
            'Silakan meninjau surat melalui tautan berikut:',
            $url,
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
        $tipe = $disposisi->tipe === 'naikan' ? 'Surat dinaikkan' : 'Disposisi surat';

        $lines = [
            'Yth. Bapak/Ibu,',
            'Dengan hormat, terdapat ' . strtolower($tipe) . ' yang memerlukan tindak lanjut.',
            '',
            'Dari: ' . (optional($dari)->name ?: '-'),
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

        if ($disposisi->priority_level) {
            $priorityMap = ['high' => 'Tinggi', 'normal' => 'Normal', 'low' => 'Rendah'];
            $lines[] = 'Prioritas: ' . ($priorityMap[$disposisi->priority_level] ?? 'Normal');
        }

        if ($disposisi->target_tindak_lanjut_at) {
            $lines[] = 'Target tindak lanjut: ' . $this->formatDateTimeValue($disposisi->target_tindak_lanjut_at);
        }

        $lines[] = '';
        $lines[] = 'Mohon menindaklanjuti disposisi melalui tautan berikut:';
        $lines[] = $url;

        return $this->sendToUser($targetUser, $this->wrap($lines), [
            'module' => 'persuratan',
            'event' => 'disposisi',
            'notifiable_type' => get_class($disposisi),
            'notifiable_id' => $disposisi->id,
        ]);
    }

    public function notifyDisposisiReminder($disposisi, $targetUser)
    {
        $suratMasuk = $disposisi->suratMasuk;
        $message = $this->wrap([
            'Yth. Bapak/Ibu,',
            'Dengan hormat, ini adalah pengingat tindak lanjut disposisi surat yang masih berstatus pending.',
            '',
            'No. Surat: ' . optional($suratMasuk)->nomor_surat,
            'Perihal: ' . optional($suratMasuk)->perihal,
            'Prioritas: ' . ($disposisi->priority_level === 'high' ? 'Tinggi' : ($disposisi->priority_level === 'low' ? 'Rendah' : 'Normal')),
            'Target tindak lanjut: ' . ($disposisi->target_tindak_lanjut_at ? $this->formatDateTimeValue($disposisi->target_tindak_lanjut_at) : '-'),
            '',
            'Mohon segera menindaklanjuti pada aplikasi:',
            url('/surat-masuk/' . optional($suratMasuk)->id),
        ]);

        return $this->sendToUser($targetUser, $message, [
            'module' => 'persuratan',
            'event' => 'disposisi_reminder',
            'notifiable_type' => get_class($disposisi),
            'notifiable_id' => $disposisi->id,
        ]);
    }

    public function notifyLeaveRequestSubmitted(LeaveRequest $leaveRequest, User $targetUser)
    {
        $leaveRequest->loadMissing(['leaveType', 'user']);

        $message = $this->wrap([
            'Yth. Bapak/Ibu,',
            'Dengan hormat, pengajuan cuti Bapak/Ibu telah berhasil diajukan dan saat ini menunggu proses verifikasi atau persetujuan.',
            '',
            'Nomor Pengajuan: ' . ($leaveRequest->request_number ?: $leaveRequest->display_number),
            'Jenis Cuti: ' . optional($leaveRequest->leaveType)->name,
            'Periode: ' . $leaveRequest->period_label,
            'Keperluan: ' . trim(preg_replace('/\s+/', ' ', (string) $leaveRequest->purpose)),
            'Status: ' . $leaveRequest->status_label,
            '',
            'Silakan memantau perkembangan pengajuan melalui tautan berikut:',
            route('cuti.show', $leaveRequest),
        ]);

        return $this->sendToUser($targetUser, $message, [
            'module' => 'cuti',
            'event' => 'leave_submitted',
            'notifiable_type' => get_class($leaveRequest),
            'notifiable_id' => $leaveRequest->id,
        ]);
    }

    public function notifyLeaveApprovalPending(LeaveApproval $approval)
    {
        $approval->loadMissing(['leaveRequest.user', 'leaveRequest.leaveType', 'approver']);

        if (!$approval->approver) {
            return false;
        }

        $leaveRequest = $approval->leaveRequest;
        $message = $this->wrap([
            'Yth. Bapak/Ibu,',
            'Dengan hormat, terdapat pengajuan cuti yang memerlukan verifikasi atau persetujuan Bapak/Ibu.',
            '',
            'Tahap: ' . $approval->role_label,
            'Pemohon: ' . optional($leaveRequest->user)->name,
            'Nomor Pengajuan: ' . ($leaveRequest->request_number ?: $leaveRequest->display_number),
            'Jenis Cuti: ' . optional($leaveRequest->leaveType)->name,
            'Periode: ' . $leaveRequest->period_label,
            'Status Saat Ini: ' . $leaveRequest->status_label,
            '',
            'Silakan memproses pengajuan melalui tautan berikut:',
            route('cuti.approval.show', $approval),
        ]);

        return $this->sendToUser($approval->approver, $message, [
            'module' => 'cuti',
            'event' => 'leave_approval_pending',
            'notifiable_type' => get_class($approval),
            'notifiable_id' => $approval->id,
        ]);
    }

    public function notifyLeaveRequestStatus(LeaveRequest $leaveRequest, User $targetUser, $title, $messageBody, $actorName = null, $note = null)
    {
        $leaveRequest->loadMissing(['leaveType', 'user']);

        $lines = [
            'Yth. Bapak/Ibu,',
            'Dengan hormat, berikut disampaikan pembaruan status pengajuan cuti.',
            '',
            'Informasi: ' . $title,
            'Nomor Pengajuan: ' . ($leaveRequest->request_number ?: $leaveRequest->display_number),
            'Jenis Cuti: ' . optional($leaveRequest->leaveType)->name,
            'Periode: ' . $leaveRequest->period_label,
            'Status: ' . $leaveRequest->status_label,
            'Keterangan: ' . $messageBody,
        ];

        if ($actorName) {
            $lines[] = 'Diproses Oleh: ' . $actorName;
        }

        if ($note) {
            $lines[] = 'Catatan: ' . trim(preg_replace('/\s+/', ' ', (string) $note));
        }

        $lines[] = '';
        $lines[] = 'Silakan meninjau detail pengajuan pada tautan berikut:';
        $lines[] = route('cuti.show', $leaveRequest);

        return $this->sendToUser($targetUser, $this->wrap($lines), [
            'module' => 'cuti',
            'event' => 'leave_status_updated',
            'notifiable_type' => get_class($leaveRequest),
            'notifiable_id' => $leaveRequest->id,
        ]);
    }

    public function notifySuratTugasRecipient(SuratKeluar $suratKeluar, User $targetUser, array $fieldValues = [])
    {
        $message = $this->wrap([
            'Yth. Bapak/Ibu,',
            'Dengan hormat, berikut disampaikan informasi Surat Tugas yang telah ditetapkan.',
            '',
            'Nomor Surat: ' . $suratKeluar->nomor_surat_formatted,
            'Perihal: ' . ($suratKeluar->perihal ?: 'Surat Tugas'),
            'Petugas: ' . $targetUser->name,
            'Periode Tugas: ' . $this->formatTaskLetterPeriod($fieldValues),
            'Uraian Tugas: ' . ($fieldValues['untuk_tugas'] ?? 'Sesuai Surat Tugas yang diterbitkan.'),
            '',
            'Silakan meninjau dokumen melalui tautan berikut:',
            route('surat-keluar.signature.verify', optional($suratKeluar->templateApproval)->id),
        ]);

        return $this->sendToUser($targetUser, $message, [
            'module' => 'surat_tugas',
            'event' => 'surat_tugas_recipient',
            'notifiable_type' => get_class($suratKeluar),
            'notifiable_id' => $suratKeluar->id,
        ]);
    }

    public function notifySuratTugasRequester(SuratKeluarApproval $approval, User $targetUser, $approved = true, $note = null)
    {
        $approval->loadMissing('suratKeluar');
        $suratKeluar = $approval->suratKeluar;
        $title = $approved ? 'Surat Tugas telah disetujui' : 'Surat Tugas perlu diperbaiki';
        $lines = [
            'Yth. Bapak/Ibu,',
            'Dengan hormat, berikut disampaikan pembaruan proses Surat Tugas.',
            '',
            'Informasi: ' . $title,
            'Nomor Surat: ' . optional($suratKeluar)->nomor_surat_formatted,
            'Perihal: ' . (optional($suratKeluar)->perihal ?: 'Surat Tugas'),
            'Status: ' . $approval->status_label,
        ];

        if ($note) {
            $lines[] = 'Catatan: ' . trim(preg_replace('/\s+/', ' ', (string) $note));
        }

        $lines[] = '';
        $lines[] = 'Silakan membuka modul Surat Keluar untuk tindak lanjut lebih lanjut:';
        $lines[] = route('surat-keluar.index');

        return $this->sendToUser($targetUser, $this->wrap($lines), [
            'module' => 'surat_tugas',
            'event' => $approved ? 'surat_tugas_approved' : 'surat_tugas_rejected',
            'notifiable_type' => get_class($approval),
            'notifiable_id' => $approval->id,
        ]);
    }

    public function notifyProgressZiApprovalPending(ZiActivityApproval $approval)
    {
        $approval->loadMissing(['activity.area', 'activity.pic', 'requester', 'approver']);

        if (!$approval->approver) {
            return false;
        }

        $activity = $approval->activity;
        $message = $this->wrap([
            'Yth. Bapak/Ibu,',
            'Dengan hormat, terdapat kegiatan Progress ZI yang memerlukan review pimpinan.',
            '',
            'Aktivitas: ' . optional($activity)->name,
            'Area: ' . optional(optional($activity)->area)->name,
            'PIC: ' . (optional(optional($activity)->pic)->name ?: optional($approval->requester)->name ?: '-'),
            'Status: Pending Review',
            'Catatan Pengajuan: ' . ($approval->request_notes ?: '-'),
            '',
            'Silakan meninjau melalui tautan berikut:',
            route('progress-zi.approvals.show', $approval),
        ]);

        return $this->sendToUser($approval->approver, $message, [
            'module' => 'progress_zi',
            'event' => 'zi_review_pending',
            'notifiable_type' => get_class($approval),
            'notifiable_id' => $approval->id,
        ]);
    }

    public function notifyProgressZiStatus(ZiActivity $activity, User $targetUser, $title, $messageBody, $note = null)
    {
        $activity->loadMissing(['area', 'period', 'pic']);

        $lines = [
            'Yth. Bapak/Ibu,',
            'Dengan hormat, berikut disampaikan pembaruan tindak lanjut Progress ZI.',
            '',
            'Informasi: ' . $title,
            'Aktivitas: ' . $activity->name,
            'Area: ' . optional($activity->area)->name,
            'Periode: ' . optional($activity->period)->name,
            'Status: ' . $activity->status_label,
            'Keterangan: ' . $messageBody,
        ];

        if ($note) {
            $lines[] = 'Catatan Review: ' . trim(preg_replace('/\s+/', ' ', (string) $note));
        }

        $lines[] = '';
        $lines[] = 'Silakan membuka detail kegiatan pada tautan berikut:';
        $lines[] = route('progress-zi.activities.show', $activity);

        return $this->sendToUser($targetUser, $this->wrap($lines), [
            'module' => 'progress_zi',
            'event' => 'zi_status_updated',
            'notifiable_type' => get_class($activity),
            'notifiable_id' => $activity->id,
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
            'Yth. Bapak/Ibu,',
            'Dengan hormat, terdapat dokumen rapat yang menunggu persetujuan.',
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
            'Silakan meninjau dokumen melalui tautan berikut:',
            route('approval.index', ['category' => 'undangan']),
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
            'Yth. Bapak/Ibu,',
            'Dengan hormat, dokumen rapat berikut belum dapat disetujui.',
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
        $lines[] = 'Mohon melakukan perbaikan dokumen pada aplikasi.';

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
            'Yth. Bapak/Ibu,',
            'Dengan hormat, berikut disampaikan informasi rapat.',
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
        $lines[] = 'Mohon kehadiran tepat waktu sesuai jadwal yang telah ditetapkan.';

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
            'Yth. Bapak/Ibu,',
            'Dengan hormat, berikut pengingat absensi rapat.',
            '',
            'Judul: ' . $rapat->judul,
            'Tanggal: ' . $this->formatDateValue($rapat->tanggal),
            'Waktu: ' . $rapat->waktu_mulai_formatted . ' WIT',
            '',
            'Silakan melakukan absensi melalui tautan berikut:',
            $attendanceUrl,
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
            'Yth. Bapak/Ibu,',
            'Dengan hormat, berikut disampaikan informasi e-voting.',
            '',
            'Judul Voting: ' . $voting->judul,
            'Jumlah Item: ' . $voting->items->count(),
            'Status: ' . ucfirst((string) $voting->status),
            '',
            'Silakan mengakses e-voting melalui tautan berikut:',
            route('rapat.voting.public.show', $voting->public_code),
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
            'Yth. Bapak/Ibu,',
            'Dengan hormat, berikut informasi akses aplikasi.',
            '',
            'Nama: ' . $user->name,
            'Email: ' . $user->email,
            'Username: ' . ($user->username ?: '-'),
            'Password standar: ptapabar',
            '',
            'Apabila password pernah diubah sebelumnya, gunakan password terakhir yang masih berlaku.',
            'Halaman login:',
            url('/login'),
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
        return implode("\n", $lines) . "\n\nHormat kami,\nSistem Informasi PTA Papua Barat";
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

    protected function formatDateTimeValue($value)
    {
        if (!$value) {
            return '-';
        }

        if ($value instanceof Carbon) {
            return $value->copy()->timezone('Asia/Jayapura')->translatedFormat('d M Y H:i') . ' WIT';
        }

        try {
            return Carbon::parse($value, 'Asia/Jayapura')->translatedFormat('d M Y H:i') . ' WIT';
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    protected function formatTaskLetterPeriod(array $fieldValues)
    {
        $start = $fieldValues['tanggal_mulai'] ?? null;
        $end = $fieldValues['tanggal_selesai'] ?? null;

        if ($start && $end) {
            return $this->formatDateValue($start) . ' s.d. ' . $this->formatDateValue($end);
        }

        if ($start) {
            return $this->formatDateValue($start);
        }

        return '-';
    }
}
