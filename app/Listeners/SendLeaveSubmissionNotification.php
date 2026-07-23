<?php

namespace App\Listeners;

use App\Events\LeaveRequestSubmitted;
use App\Notifications\LeaveWorkflowNotification;
use App\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;

class SendLeaveSubmissionNotification
{
    protected $whatsAppService;

    public function __construct(WhatsAppNotificationService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function handle(LeaveRequestSubmitted $event)
    {
        $leaveRequest = $event->leaveRequest->fresh(['user', 'leaveType', 'approvals.approver']);
        $requester = $leaveRequest->user;
        $pendingApproval = $leaveRequest->approvals->firstWhere('status', 'pending');

        if ($requester) {
            $title = 'Pengajuan cuti berhasil diajukan';
            $message = 'Pengajuan cuti Bapak/Ibu telah diterima sistem dan sedang menunggu verifikasi Admin Kepegawaian.';

            try {
                $requester->notify(new LeaveWorkflowNotification(
                    $leaveRequest,
                    $title,
                    $message,
                    route('cuti.show', $leaveRequest),
                    $leaveRequest->status,
                    'requester'
                ));
            } catch (\Throwable $e) {
                Log::warning('Leave submission database notification skipped for requester', [
                    'leave_request_id' => $leaveRequest->id,
                    'user_id' => $requester->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->whatsAppService->notifyLeaveRequestSubmitted($leaveRequest, $requester);
        }

        if ($pendingApproval && $pendingApproval->approver) {
            $isPersonnelVerification = $pendingApproval->role_name === 'verifikator_dokumen';
            $approvalTitle = $isPersonnelVerification
                ? 'Verifikasi pengajuan cuti menunggu tindakan Anda'
                : 'Persetujuan cuti menunggu tindakan Anda';
            $approvalMessage = $isPersonnelVerification
                ? 'Terdapat pengajuan cuti pegawai yang wajib diverifikasi oleh Admin Kepegawaian sebelum diteruskan kepada pejabat berikutnya.'
                : 'Terdapat pengajuan cuti yang memerlukan persetujuan Bapak/Ibu.';

            try {
                $pendingApproval->approver->notify(new LeaveWorkflowNotification(
                    $leaveRequest,
                    $approvalTitle,
                    $approvalMessage,
                    route('cuti.approval.show', $pendingApproval),
                    'pending',
                    'approver'
                ));
            } catch (\Throwable $e) {
                Log::warning('Leave submission database notification skipped for approver', [
                    'leave_request_id' => $leaveRequest->id,
                    'approval_id' => $pendingApproval->id,
                    'approver_id' => $pendingApproval->approver_id,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->whatsAppService->notifyLeaveApprovalPending($pendingApproval);
        }
    }
}
