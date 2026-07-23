<?php

namespace App\Listeners;

use App\Events\LeaveRequestStatusChanged;
use App\Notifications\LeaveWorkflowNotification;
use App\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;

class SendLeaveStatusNotification
{
    protected $whatsAppService;

    public function __construct(WhatsAppNotificationService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function handle(LeaveRequestStatusChanged $event)
    {
        $leaveRequest = $event->leaveRequest->fresh(['user', 'leaveType', 'approvals.approver']);
        $requester = $leaveRequest->user;
        $pendingApproval = $leaveRequest->approvals->firstWhere('status', 'pending');

        $requesterPayload = $this->buildRequesterPayload($leaveRequest, $event->eventName);
        if ($requester && $requesterPayload) {
            try {
                $requester->notify(new LeaveWorkflowNotification(
                    $leaveRequest,
                    $requesterPayload['title'],
                    $requesterPayload['message'],
                    route('cuti.show', $leaveRequest),
                    $leaveRequest->status,
                    'requester'
                ));
            } catch (\Throwable $e) {
                Log::warning('Leave status notification skipped for requester', [
                    'leave_request_id' => $leaveRequest->id,
                    'user_id' => $requester->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->whatsAppService->notifyLeaveRequestStatus(
                $leaveRequest,
                $requester,
                $requesterPayload['title'],
                $requesterPayload['message'],
                optional($event->actor)->name,
                optional($leaveRequest->approvals->where('status', 'rejected')->sortByDesc('id')->first())->note ?: $leaveRequest->revision_note
            );
        }

        if ($pendingApproval && $pendingApproval->approver && in_array($leaveRequest->status, ['verified', 'under_review'], true)) {
            try {
                $pendingApproval->approver->notify(new LeaveWorkflowNotification(
                    $leaveRequest,
                    'Persetujuan cuti menunggu tindakan Anda',
                    'Terdapat pengajuan cuti yang memerlukan verifikasi atau persetujuan Bapak/Ibu pada tahap ' . $pendingApproval->role_label . '.',
                    route('cuti.approval.show', $pendingApproval),
                    'pending',
                    'approver'
                ));
            } catch (\Throwable $e) {
                Log::warning('Leave next approver notification skipped', [
                    'leave_request_id' => $leaveRequest->id,
                    'approval_id' => $pendingApproval->id,
                    'approver_id' => $pendingApproval->approver_id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->whatsAppService->notifyLeaveApprovalPending($pendingApproval);
        }
    }

    protected function buildRequesterPayload($leaveRequest, $eventName)
    {
        if ($eventName === 'approved' && $leaveRequest->status === 'verified') {
            return [
                'title' => 'Pengajuan cuti telah diverifikasi',
                'message' => 'Pengajuan cuti Bapak/Ibu telah diverifikasi oleh Admin Kepegawaian dan diteruskan ke tahap persetujuan berikutnya.',
            ];
        }

        if ($eventName === 'approved' && $leaveRequest->status === 'under_review') {
            return [
                'title' => 'Pengajuan cuti diteruskan ke tahap berikutnya',
                'message' => 'Pengajuan cuti Bapak/Ibu telah diproses pada tahap sebelumnya dan saat ini menunggu persetujuan pejabat berikutnya.',
            ];
        }

        if ($eventName === 'approved' && $leaveRequest->status === 'approved') {
            return [
                'title' => 'Pengajuan cuti disetujui',
                'message' => 'Pengajuan cuti Bapak/Ibu telah disetujui. Silakan meninjau surat keputusan cuti pada aplikasi.',
            ];
        }

        if ($eventName === 'rejected' || $leaveRequest->status === 'rejected') {
            return [
                'title' => 'Pengajuan cuti ditolak',
                'message' => 'Pengajuan cuti Bapak/Ibu belum dapat disetujui. Mohon meninjau catatan penolakan pada aplikasi.',
            ];
        }

        if ($eventName === 'changed' || $leaveRequest->status === 'changed') {
            return [
                'title' => 'Pengajuan cuti perlu perubahan',
                'message' => 'Pengajuan cuti Bapak/Ibu perlu disesuaikan. Mohon meninjau catatan perubahan pada aplikasi.',
            ];
        }

        if ($eventName === 'deferred' || $leaveRequest->status === 'deferred') {
            return [
                'title' => 'Pengajuan cuti ditangguhkan',
                'message' => 'Pengajuan cuti Bapak/Ibu ditangguhkan. Mohon meninjau alasan penangguhan pada aplikasi.',
            ];
        }

        if ($eventName === 'cancelled' || $leaveRequest->status === 'cancelled') {
            return [
                'title' => 'Pengajuan cuti dibatalkan',
                'message' => 'Pengajuan cuti Bapak/Ibu telah dibatalkan dan tidak lagi diproses.',
            ];
        }

        return [
            'title' => 'Status pengajuan cuti diperbarui',
            'message' => 'Status pengajuan cuti Bapak/Ibu berubah menjadi ' . $leaveRequest->status_label . '.',
        ];
    }
}
