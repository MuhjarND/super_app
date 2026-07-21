<?php

namespace App\Services;

use App\AppSetting;
use App\AgendaPimpinan;
use App\LeaveApproval;
use App\LeaveRequest;
use App\InventoryMaintenanceSchedule;
use App\Rapat;
use App\RapatApproval;
use App\SupplyRequest;
use App\SuratKeluar;
use App\SuratKeluarApproval;
use App\User;
use App\Voting;
use App\VirtualMeeting;
use App\WhatsAppNotificationLog;
use App\ZiActivity;
use App\ZiActivityApproval;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class WhatsAppNotificationService
{
    protected $apiUrl;
    protected $apiKey;
    protected $magicLinkService;

    public function __construct(WhatsAppMagicLinkService $magicLinkService)
    {
        $this->apiUrl = config('services.whatsapp.api_url');
        $this->apiKey = config('services.whatsapp.api_key');
        $this->magicLinkService = $magicLinkService;
    }

    public function send($phoneNumber, $message, array $context = [])
    {
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);
        $message = $this->withNotificationHeader($message, $context);
        $fingerprint = $this->fingerprint($normalizedPhone, $message, $context);

        if ($this->isDuplicate($fingerprint)) {
            Log::info('[WA Notification] Duplicate notification suppressed.', [
                'module' => $context['module'] ?? 'general',
                'event' => $context['event'] ?? 'message',
                'phone_number' => $normalizedPhone,
            ]);

            return true;
        }

        $log = $this->createLog($normalizedPhone, $message, array_merge($context, [
            'fingerprint' => $fingerprint,
        ]));

        if (!$this->isEnabled()) {
            $log->update([
                'status' => 'skipped',
                'response_body' => 'Notifikasi WhatsApp sedang dinonaktifkan dari pengaturan aplikasi.',
            ]);

            return false;
        }

        if (!$normalizedPhone) {
            $log->update([
                'status' => 'skipped',
                'response_body' => 'Nomor WhatsApp kosong atau tidak valid.',
            ]);

            return false;
        }

        if (!$this->isValidPhoneNumber($normalizedPhone)) {
            $log->update([
                'status' => 'skipped',
                'response_body' => 'Nomor WhatsApp tidak memenuhi format nomor internasional yang didukung.',
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

        $now = Carbon::now('Asia/Jayapura');
        if ($this->applyWorkingTime($now)->greaterThan($now)) {
            $log->update([
                'status' => 'skipped',
                'response_body' => 'Pesan tidak dikirim karena berada di luar jam operasional WhatsApp.',
            ]);

            return false;
        }

        if ($limitReason = $this->rateLimitReason($normalizedPhone, $now)) {
            $log->update([
                'status' => 'skipped',
                'response_body' => $limitReason,
            ]);

            return false;
        }

        $log->update([
            'status' => 'queued',
            'scheduled_at' => $now,
            'response_body' => 'Pesan disiapkan untuk pengiriman langsung.',
        ]);

        $this->waitForMinimumInterval();

        return $this->deliver($log->fresh());
    }

    public function deliver(WhatsAppNotificationLog $log)
    {
        if ($log->status !== 'queued') {
            return $log->status === 'sent';
        }

        $normalizedMessage = $this->normalizeNotificationBrandAndTitle($log->message, $log->module);
        if ($normalizedMessage !== $log->message) {
            $log->update(['message' => $normalizedMessage]);
        }

        if (!$this->isEnabled() || !$this->isConfigured() || !$this->isValidPhoneNumber($log->phone_number)) {
            $log->update([
                'status' => 'skipped',
                'response_body' => 'Pengiriman dibatalkan karena layanan nonaktif, konfigurasi tidak lengkap, atau nomor tidak valid.',
            ]);

            return false;
        }

        $now = Carbon::now('Asia/Jayapura');
        $allowedAt = $this->applyWorkingTime($now);
        if ($allowedAt->greaterThan($now)) {
            $log->update(['scheduled_at' => $allowedAt]);
            return true;
        }

        $log->update([
            'status' => 'sending',
            'attempted_at' => $now,
            'attempt_count' => $log->attempt_count + 1,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])->timeout(max(5, (int) config('services.whatsapp.http_timeout', 15)))
            ->post($this->apiUrl, [
                'target' => $log->phone_number,
                'message' => $log->message,
            ]);

            $accepted = $this->providerAccepted($response);

            $log->update([
                'status' => $accepted ? 'sent' : 'failed',
                'response_body' => (string) $response->body(),
                'sent_at' => $accepted ? Carbon::now('Asia/Jayapura') : null,
            ]);

            Log::info('[WA Notification] Delivery to ' . $log->phone_number . ': ' . ($accepted ? 'Success' : 'Failed'));

            return $accepted;
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

        $message = $this->magicLinkService->replaceApplicationUrls($targetUser, $message);

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
            'File Surat Tugas:',
            $this->directSuratKeluarFileUrl($suratKeluar),
        ]);

        return $this->sendToUser($targetUser, $message, [
            'module' => 'surat_tugas',
            'event' => 'surat_tugas_recipient',
            'notifiable_type' => get_class($suratKeluar),
            'notifiable_id' => $suratKeluar->id,
        ]);
    }

    public function notifySuratTugasParafPending(SuratKeluarApproval $approval, User $targetUser)
    {
        $approval->loadMissing('suratKeluar');
        $suratKeluar = $approval->suratKeluar;
        $message = $this->wrap([
            'Yth. Bapak/Ibu ' . $targetUser->name . ',',
            'Terdapat Surat Tugas yang memerlukan pemeriksaan dan paraf Anda.',
            '',
            'Nomor Surat: ' . optional($suratKeluar)->nomor_surat_formatted,
            'Perihal: ' . (optional($suratKeluar)->perihal ?: 'Surat Tugas'),
            'Status: Menunggu Paraf',
            '',
            'Silakan periksa dan proses melalui tautan berikut:',
            route('surat-keluar.approval.show', $approval),
        ]);

        return $this->sendToUser($targetUser, $message, [
            'module' => 'surat_tugas',
            'event' => 'surat_tugas_paraf_pending',
            'notifiable_type' => get_class($approval),
            'notifiable_id' => $approval->id,
        ]);
    }

    public function notifySuratTugasSignerPending(SuratKeluarApproval $approval, User $targetUser)
    {
        $approval->loadMissing('suratKeluar');
        $suratKeluar = $approval->suratKeluar;
        $message = $this->wrap([
            'Yth. Bapak/Ibu ' . $targetUser->name . ',',
            'Surat Tugas telah diparaf dan memerlukan persetujuan serta tanda tangan Anda.',
            '',
            'Nomor Surat: ' . optional($suratKeluar)->nomor_surat_formatted,
            'Perihal: ' . (optional($suratKeluar)->perihal ?: 'Surat Tugas'),
            'Status: Menunggu Persetujuan',
            '',
            'Silakan periksa dan proses melalui tautan berikut:',
            route('surat-keluar.approval.show', $approval),
        ]);

        return $this->sendToUser($targetUser, $message, [
            'module' => 'surat_tugas',
            'event' => 'surat_tugas_approval_pending',
            'notifiable_type' => get_class($approval),
            'notifiable_id' => $approval->id,
        ]);
    }

    public function notifySuratKeluarRecipient(SuratKeluar $suratKeluar, User $targetUser)
    {
        $suratKeluar->loadMissing('creator');

        $message = $this->wrap([
            'Yth. Bapak/Ibu,',
            'Dengan hormat, surat keluar yang menandai Bapak/Ibu sebagai penerima internal telah dinyatakan lengkap dan dokumennya telah tersedia.',
            '',
            'Nomor Surat: ' . $suratKeluar->nomor_surat_formatted,
            'Perihal: ' . ($suratKeluar->perihal ?: '-'),
            'Tanggal Surat: ' . $this->formatDateValue($suratKeluar->tanggal_surat),
            'Dibuat Oleh: ' . (optional($suratKeluar->creator)->name ?: '-'),
            '',
            'File Surat Keluar:',
            $this->directSuratKeluarFileUrl($suratKeluar),
        ]);

        return $this->sendToUser($targetUser, $message, [
            'module' => 'persuratan',
            'event' => 'surat_keluar_ready',
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
        if ($approved && $suratKeluar) {
            $lines[] = 'File Surat Tugas:';
            $lines[] = $this->directSuratKeluarFileUrl($suratKeluar);
        } else {
            $lines[] = 'Silakan membuka modul Surat Keluar untuk melakukan perbaikan:';
            $lines[] = route('surat-keluar.index');
        }

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
            route('rapat.approval.show', $approval),
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
        $lines[] = 'Mohon melakukan perbaikan dokumen melalui tautan berikut:';
        $lines[] = route('rapat.index');

        return $this->sendToUser($rapat->creator, $this->wrap($lines), [
            'module' => 'rapat',
            'event' => 'approval_rejected',
            'notifiable_type' => get_class($rapat),
            'notifiable_id' => $rapat->id,
        ]);
    }

    public function notifyRapatParticipants(Rapat $rapat)
    {
        $rapat->loadMissing(['kategoriSuratKode', 'pesertas.jabatan', 'pesertas.roles', 'suratKeluar']);

        if ($rapat->participant_notified_at) {
            return false;
        }

        $lines = [
            'Yth. Bapak/Ibu,',
            'Dengan hormat, berikut disampaikan undangan rapat yang telah disetujui.',
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

        $users = $rapat->pesertas->filter(function ($user) {
            return !empty($user->no_hp);
        });

        $fileUrl = $rapat->suratKeluar
            ? $this->directSuratKeluarFileUrl($rapat->suratKeluar)
            : route('rapat.undangan.preview', $rapat);
        $lines[] = '';
        $lines[] = 'Mohon kehadiran tepat waktu sesuai jadwal yang telah ditetapkan.';
        $lines[] = 'File Undangan:';
        $lines[] = $fileUrl;

        $result = $this->sendBulk($users, $this->wrap($lines), [
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

        $message = $agenda->whatsapp_preview
            . "\n\nTinjau agenda melalui tautan berikut:\n"
            . route('rapat.agenda.index');
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

    public function notifyAgendaPimpinanCreatedForProtokoler(AgendaPimpinan $agenda, User $targetUser)
    {
        $agenda->loadMissing('suratMasuk');

        $suratMasuk = $agenda->suratMasuk;
        $lines = [
            'Yth. Bapak/Ibu Protokoler,',
            'Dengan hormat, terdapat agenda pimpinan baru yang dibuat dari Surat Masuk dan memerlukan pengisian daftar peserta kegiatan.',
            '',
            'Judul Agenda: ' . $agenda->judul_agenda,
            'Tanggal Kegiatan: ' . $agenda->tanggal_formatted,
            'Waktu: ' . $agenda->waktu_formatted . ' WIT',
            'Tempat: ' . $agenda->tempat,
        ];

        if ($agenda->seragam_pakaian) {
            $lines[] = 'Seragam/Pakaian: ' . $agenda->seragam_pakaian;
        }

        if ($suratMasuk) {
            $lines[] = '';
            $lines[] = 'Sumber Surat Masuk:';
            $lines[] = 'No. Surat: ' . $suratMasuk->nomor_surat;
            $lines[] = 'Pengirim: ' . $suratMasuk->pengirim;
            $lines[] = 'Perihal: ' . $suratMasuk->perihal;
        }

        $lines[] = '';
        $lines[] = 'Mohon menginput daftar peserta kegiatan melalui tautan berikut:';
        $lines[] = route('rapat.agenda.index');

        return $this->sendToUser($targetUser, $this->wrap($lines), [
            'module' => 'agenda_pimpinan',
            'event' => 'agenda_created_for_protokoler',
            'notifiable_type' => get_class($agenda),
            'notifiable_id' => $agenda->id,
        ]);
    }

    public function notifyVirtualMeetingParticipants(VirtualMeeting $meeting)
    {
        $meeting->loadMissing(['participants.jabatan', 'suratMasuk']);

        $lines = [
            'Yth. Bapak/Ibu Peserta,',
            'Dengan hormat, Anda diundang untuk mengikuti agenda virtual berikut.',
            '',
            'Agenda: ' . $meeting->judul,
            'Tanggal: ' . $meeting->tanggal_formatted,
            'Waktu: ' . $meeting->waktu_mulai_formatted . ' WIT' . ($meeting->waktu_selesai ? ' - ' . $meeting->waktu_selesai_formatted . ' WIT' : ''),
            'Tautan Zoom: ' . $meeting->zoom_link,
        ];

        if ($meeting->catatan) {
            $lines[] = 'Catatan: ' . $meeting->catatan;
        }

        if ($meeting->suratMasuk) {
            $lines[] = 'Nomor Surat: ' . $meeting->suratMasuk->nomor_surat;
        }

        $lines[] = '';
        $lines[] = 'Mohon hadir tepat waktu dan memastikan perangkat serta koneksi internet telah siap sebelum kegiatan dimulai.';
        $lines[] = 'Informasi agenda dapat ditinjau melalui tautan berikut:';
        $lines[] = route('rapat.virtual-meeting.index');

        $result = $this->sendBulk($meeting->participants, $this->wrap($lines), [
            'module' => 'virtual_meeting',
            'event' => 'participant_invitation',
            'notifiable_type' => get_class($meeting),
            'notifiable_id' => $meeting->id,
        ]);

        $meeting->forceFill(['last_notified_at' => Carbon::now('Asia/Jayapura')])->save();

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

    public function notifySupplyRequestSubmitted(SupplyRequest $supplyRequest, User $targetUser)
    {
        $supplyRequest->loadMissing(['requester', 'items']);

        $message = $this->wrap([
            'Yth. Bapak/Ibu Operator Persediaan,',
            'Dengan hormat, terdapat pengajuan persediaan baru yang memerlukan tindak lanjut.',
            '',
            'Nomor Pengajuan: ' . $supplyRequest->request_number,
            'Pemohon: ' . (optional($supplyRequest->requester)->name ?: '-'),
            'Barang: ' . ($supplyRequest->items_summary ?: '-'),
            'Jumlah: ' . $supplyRequest->quantity_summary,
            'Keperluan: ' . trim(preg_replace('/\s+/', ' ', (string) $supplyRequest->purpose)),
            'Status: ' . $supplyRequest->status_label,
            '',
            'Silakan memproses pengajuan melalui tautan berikut:',
            route('persediaan.requests.show', $supplyRequest),
        ]);

        return $this->sendToUser($targetUser, $message, [
            'module' => 'persediaan',
            'event' => 'supply_request_submitted',
            'notifiable_type' => get_class($supplyRequest),
            'notifiable_id' => $supplyRequest->id,
        ]);
    }

    public function notifyInventoryMaintenanceDue(InventoryMaintenanceSchedule $schedule, User $targetUser)
    {
        $schedule->loadMissing(['item', 'detail.room']);
        $item = $schedule->item;
        $detail = $schedule->detail;

        $message = $this->wrap([
            'Yth. ' . ($targetUser->display_jabatan ?: 'Bapak/Ibu') . ',',
            'Dengan hormat, disampaikan bahwa jadwal perawatan alat dan mesin berikut telah jatuh tempo.',
            '',
            'Barang: ' . ($item ? trim(($item->code ?: '') . ' - ' . $item->name, ' -') : '-'),
            'Sub Barang: ' . ($detail ? trim(($detail->sub_code ?: $detail->nup ?: '') . ' - ' . $detail->name, ' -') : '-'),
            'Lokasi: ' . (optional(optional($detail)->room)->name ?: '-'),
            'Waktu Perawatan: ' . $this->formatDateTimeValue($schedule->scheduled_at),
            'Keterangan: ' . trim(preg_replace('/\s+/', ' ', (string) $schedule->description)),
            '',
            'Mohon dilakukan monitoring dan koordinasi pelaksanaan perawatan sesuai jadwal tersebut.',
            'Detail jadwal dapat ditinjau melalui tautan berikut:',
            route('perawatan-alat-mesin.schedules.index'),
        ]);

        return $this->sendToUser($targetUser, $message, [
            'module' => 'perawatan',
            'event' => 'maintenance_schedule_due',
            'notifiable_type' => get_class($schedule),
            'notifiable_id' => $schedule->id,
        ]);
    }

    public function notifySupplyRequestStatus(SupplyRequest $supplyRequest, User $targetUser, $title, $messageBody)
    {
        $supplyRequest->loadMissing(['requester', 'items']);

        $message = $this->wrap([
            'Yth. Bapak/Ibu,',
            'Dengan hormat, berikut disampaikan pembaruan pengajuan persediaan.',
            '',
            'Informasi: ' . $title,
            'Nomor Pengajuan: ' . $supplyRequest->request_number,
            'Barang: ' . ($supplyRequest->items_summary ?: '-'),
            'Jumlah: ' . $supplyRequest->quantity_summary,
            'Keperluan: ' . trim(preg_replace('/\s+/', ' ', (string) $supplyRequest->purpose)),
            'Status: ' . $supplyRequest->status_label,
            'Keterangan: ' . $messageBody,
            '',
            'Silakan meninjau detail pengajuan melalui tautan berikut:',
            route('persediaan.requests.show', $supplyRequest),
        ]);

        return $this->sendToUser($targetUser, $message, [
            'module' => 'persediaan',
            'event' => 'supply_request_status',
            'notifiable_type' => get_class($supplyRequest),
            'notifiable_id' => $supplyRequest->id,
        ]);
    }

    public function notifyLoginInfo(User $user)
    {
        $message = $this->wrap([
            'Akses akun PAPEDA telah tersedia.',
            'Nama: ' . $user->name,
            'NIP: ' . ($user->nip ?: $user->username ?: '-'),
            '',
            'Buka akun melalui tautan berikut:',
            route('dashboard'),
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

    public function isEnabled()
    {
        return AppSetting::boolean('whatsapp_notifications_enabled', true);
    }

    protected function sendBulk($users, $message, array $context = [])
    {
        $users = $users instanceof Collection ? $users : collect($users);

        $result = [
            'attempted' => 0,
            'success' => 0,
        ];

        if (!$this->isEnabled()) {
            $users->each(function ($user) use ($message, $context) {
                if ($user instanceof User && !empty($user->no_hp)) {
                    $this->sendToUser($user, $message, $context);
                }
            });

            return $result;
        }

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
            'fingerprint' => $context['fingerprint'] ?? null,
            'status' => 'queued',
            'scheduled_at' => $context['scheduled_at'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    protected function fingerprint($phoneNumber, $message, array $context)
    {
        $stableMessage = preg_replace('/https?:\/\/[^\s]+/i', '[url]', (string) $message);
        $stableMessage = trim(preg_replace('/\s+/', ' ', $stableMessage));

        return hash('sha256', implode('|', [
            (string) $phoneNumber,
            (string) ($context['module'] ?? 'general'),
            (string) ($context['event'] ?? 'message'),
            (string) ($context['notifiable_type'] ?? ''),
            (string) ($context['notifiable_id'] ?? ''),
            (string) ($context['target_user_id'] ?? ''),
            $stableMessage,
        ]));
    }

    protected function isDuplicate($fingerprint)
    {
        $minutes = max(1, (int) config('services.whatsapp.deduplicate_minutes', 10));

        return WhatsAppNotificationLog::where('fingerprint', $fingerprint)
            ->whereIn('status', ['queued', 'sending', 'sent'])
            ->where('created_at', '>=', Carbon::now('Asia/Jayapura')->subMinutes($minutes))
            ->exists();
    }

    protected function rateLimitReason($phoneNumber, Carbon $now)
    {
        $activeStatuses = ['sending', 'sent'];

        $dailyCount = WhatsAppNotificationLog::whereIn('status', $activeStatuses)
            ->where('created_at', '>=', $now->copy()->startOfDay())
            ->count();
        if ($dailyCount >= max(1, (int) config('services.whatsapp.max_per_day', 150))) {
            return 'Pesan tidak dikirim karena batas pengiriman WhatsApp harian telah tercapai.';
        }

        $hourlyCount = WhatsAppNotificationLog::whereIn('status', $activeStatuses)
            ->where('created_at', '>=', $now->copy()->subHour())
            ->count();
        if ($hourlyCount >= max(1, (int) config('services.whatsapp.max_per_hour', 30))) {
            return 'Pesan tidak dikirim karena batas pengiriman WhatsApp per jam telah tercapai.';
        }

        $recipientHourlyCount = WhatsAppNotificationLog::where('phone_number', $phoneNumber)
            ->whereIn('status', $activeStatuses)
            ->where('created_at', '>=', $now->copy()->subHour())
            ->count();
        if ($recipientHourlyCount >= max(1, (int) config('services.whatsapp.max_per_phone_hour', 5))) {
            return 'Pesan tidak dikirim karena batas pengiriman ke nomor tujuan per jam telah tercapai.';
        }

        return null;
    }

    protected function waitForMinimumInterval()
    {
        $lastAttempt = WhatsAppNotificationLog::whereNotNull('attempted_at')
            ->orderByDesc('attempted_at')
            ->value('attempted_at');

        if (!$lastAttempt) {
            return;
        }

        $interval = max(1, (int) config('services.whatsapp.minimum_interval_seconds', 20));
        $elapsed = Carbon::now('Asia/Jayapura')->timestamp
            - Carbon::parse($lastAttempt, 'Asia/Jayapura')->timestamp;
        $remaining = $interval - max(0, $elapsed);

        if ($remaining > 0) {
            sleep($remaining);
        }
    }

    protected function applyWorkingTime(Carbon $time)
    {
        $candidate = $time->copy()->timezone('Asia/Jayapura');
        $startHour = max(0, min(23, (int) config('services.whatsapp.work_start_hour', 0)));
        $endHour = max($startHour + 1, min(24, (int) config('services.whatsapp.work_end_hour', 24)));
        $workDays = collect(explode(',', (string) config('services.whatsapp.work_days', '1,2,3,4,5,6,7')))
            ->map(function ($day) { return (int) trim($day); })
            ->filter(function ($day) { return $day >= 1 && $day <= 7; })
            ->values()
            ->all();

        if (empty($workDays)) {
            $workDays = [1, 2, 3, 4, 5, 6, 7];
        }

        if ($candidate->hour < $startHour) {
            $candidate->setTime($startHour, 0, 0);
        } elseif ($candidate->hour >= $endHour) {
            $candidate->addDay()->setTime($startHour, 0, 0);
        }

        while (!in_array($candidate->dayOfWeekIso, $workDays, true)) {
            $candidate->addDay()->setTime($startHour, 0, 0);
        }

        return $candidate;
    }

    protected function isValidPhoneNumber($phoneNumber)
    {
        return (bool) preg_match('/^62[0-9]{8,13}$/', (string) $phoneNumber);
    }

    protected function providerAccepted($response)
    {
        if (!$response->successful()) {
            return false;
        }

        $payload = $response->json();
        if (!is_array($payload) || !array_key_exists('status', $payload)) {
            return true;
        }

        return filter_var($payload['status'], FILTER_VALIDATE_BOOLEAN);
    }

    protected function wrap(array $lines)
    {
        return implode("\n", $lines);
    }

    protected function withNotificationHeader($message, array $context = [])
    {
        $message = $this->compactMessage($message);

        return '*PAPEDA | ' . $this->notificationModuleTitle($context['module'] ?? null) . '*'
            . "\n"
            . $message;
    }

    protected function compactMessage($message)
    {
        $message = str_replace(["\r\n", "\r"], "\n", trim((string) $message));
        $lines = explode("\n", $message);
        $result = [];
        $hasMagicLink = strpos($message, '/masuk/whatsapp/') !== false;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                if (!empty($result) && end($result) !== '') {
                    $result[] = '';
                }
                continue;
            }

            if (preg_match('/^\*?(?:\[[^\]]+ NOTIF\]|PAPEDA\s*\|[^*]+)\*?$/i', $line)
                || preg_match('/^Yth\..+,$/i', $line)
                || preg_match('/^(?:Hormat kami,?|PAPEDA)$/i', $line)) {
                continue;
            }

            if (preg_match('/^Dengan hormat,\s*(.+)$/i', $line, $matches)) {
                $line = ucfirst(trim($matches[1]));
            }

            if (preg_match('/^(?:Berikut disampaikan (?:informasi|pembaruan).+|Disampaikan informasi .+ sebagai berikut)\.?$/i', $line)) {
                continue;
            }

            if ($this->isActionPrompt($line)) {
                $line = $hasMagicLink
                    ? '*Buka di PAPEDA (login otomatis):*'
                    : '*Buka tautan:*';
            } elseif (!preg_match('/^https?:\/\//i', $line)
                && preg_match('/^([^:*]{2,45}):\s*(.+)$/u', $line, $matches)) {
                $line = '*' . trim($matches[1]) . ':* ' . trim($matches[2]);
            }

            if (empty($result) || end($result) !== $line) {
                $result[] = $line;
            }
        }

        while (!empty($result) && end($result) === '') {
            array_pop($result);
        }

        if ($hasMagicLink) {
            $result[] = '';
            $result[] = '_Tautan khusus penerima dan hanya dapat digunakan satu kali._';
        }

        return implode("\n", $result);
    }

    protected function isActionPrompt($line)
    {
        if (!preg_match('/(?:tautan|aplikasi|modul|halaman login)/i', (string) $line)) {
            return false;
        }

        return (bool) preg_match('/^(?:Silakan|Mohon|Tinjau|Buka|Informasi agenda|Detail jadwal|Halaman login)/i', (string) $line);
    }

    protected function notificationModuleTitle($module)
    {
        $map = [
            'security' => 'KEAMANAN AKUN',
            'persuratan' => 'PERSURATAN',
            'surat_tugas' => 'SURAT TUGAS',
            'cuti' => 'CUTI',
            'progress_zi' => 'PROGRESS ZI',
            'rapat' => 'RAPAT',
            'agenda_pimpinan' => 'AGENDA PIMPINAN',
            'virtual_meeting' => 'VIRTUAL MEETING',
            'voting' => 'E-VOTING',
            'persediaan' => 'PERSEDIAAN',
            'perawatan' => 'PERAWATAN ALAT DAN MESIN',
            'master_data' => 'AKUN PENGGUNA',
            'general' => 'UMUM',
        ];

        $module = trim((string) $module);

        if ($module === '') {
            return $map['general'];
        }

        return $map[$module] ?? strtoupper(str_replace('_', ' ', $module));
    }

    protected function normalizeNotificationBrandAndTitle($message, $module)
    {
        $message = str_replace(
            ['Buka di SIMANTAP', 'Akses akun SIMANTAP'],
            ['Buka di PAPEDA', 'Akses akun PAPEDA'],
            (string) $message
        );
        $header = '*PAPEDA | ' . $this->notificationModuleTitle($module) . '*';

        if (preg_match('/^\*(?:SIMANTAP|PAPEDA)\s*\|[^*]+\*/u', $message)) {
            return preg_replace('/^\*(?:SIMANTAP|PAPEDA)\s*\|[^*]+\*/u', $header, $message, 1);
        }

        return $header . "\n" . ltrim($message);
    }

    protected function directSuratKeluarFileUrl(SuratKeluar $suratKeluar)
    {
        $ttlDays = max(1, (int) config('services.whatsapp.document_link_ttl_days', 30));

        return URL::temporarySignedRoute(
            'surat-keluar.file',
            now()->addDays($ttlDays),
            ['suratKeluar' => $suratKeluar->getKey()]
        );
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
