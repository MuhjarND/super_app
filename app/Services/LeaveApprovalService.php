<?php

namespace App\Services;

use App\LeaveApproval;
use App\LeaveDelegation;
use App\LeaveRequest;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LeaveApprovalService
{
    protected $balanceService;
    protected $numberService;
    protected $documentService;
    protected $auditService;

    public function __construct(LeaveBalanceService $balanceService, LeaveNumberService $numberService, LeaveDocumentService $documentService, ActivityAuditService $auditService)
    {
        $this->balanceService = $balanceService;
        $this->numberService = $numberService;
        $this->documentService = $documentService;
        $this->auditService = $auditService;
    }

    public function buildApprovalSteps(LeaveRequest $leaveRequest)
    {
        $steps = [];
        $leaveRequest->loadMissing(['user.atasanLangsung.atasanLangsung', 'user.unit', 'user.roles']);

        $adminKepegawaian = User::active()->whereHas('roles', function ($q) {
            $q->where('name', 'admin_kepegawaian');
        })->orderBy('id')->first();

        if (!$adminKepegawaian) {
            throw ValidationException::withMessages([
                'approval' => 'Admin Kepegawaian aktif belum ditentukan. Tetapkan role Admin Kepegawaian pada salah satu user sebelum pengajuan cuti disubmit.',
            ]);
        }

        $steps[] = [
            'step_no' => count($steps) + 1,
            'role_name' => 'verifikator_dokumen',
            'approver_id' => $this->resolveApproverId($adminKepegawaian->id, 'document_verification', $leaveRequest->start_date),
        ];

        $directSupervisorId = $this->resolveDirectSupervisorId($leaveRequest);
        if ($directSupervisorId) {
            $steps[] = [
                'step_no' => count($steps) + 1,
                'role_name' => 'atasan_langsung',
                'approver_id' => $this->resolveApproverId($directSupervisorId, 'leave_approval', $leaveRequest->start_date),
            ];
        }

        $chairman = User::active()
            ->withRoleOrDelegatedJabatan(['ketua'])
            ->orderBy('id')
            ->first();
        $sekma = $leaveRequest->is_abroad ? $this->firstRoleUser(['sekretaris_ma', 'sekretaris_mahkamah_agung']) : null;
        $finalApproverId = optional($sekma)->id ?: (optional($leaveRequest->user)->pejabat_berwenang_id ?: optional($chairman)->id);

        // The same official may act in two distinct capacities. Keep both steps so
        // the supervisor and final-authority decisions each receive their own QR.
        if ($finalApproverId) {
            $steps[] = [
                'step_no' => count($steps) + 1,
                'role_name' => $sekma ? 'sekretaris_ma' : 'ppk',
                'approver_id' => $this->resolveApproverId($finalApproverId, 'ppk_approval', $leaveRequest->start_date),
            ];
        }

        return $steps;
    }

    public function submit(LeaveRequest $leaveRequest, $applicantSignatureData = null)
    {
        DB::transaction(function () use ($leaveRequest) {
                $steps = $this->buildApprovalSteps($leaveRequest);
                if (empty($steps)) {
                    throw ValidationException::withMessages(['status' => 'Rantai approval cuti belum terbentuk.']);
                }
                if (empty($leaveRequest->request_number)) {
                    $number = $this->numberService->next('leave_request', optional($leaveRequest->start_date)->year ?: date('Y'), 'CUTI');
                    $leaveRequest->request_number = $number['formatted'];
                }

                if (!empty($steps) && optional($leaveRequest->leaveType)->requires_balance) {
                    $balanceYear = (int) optional($leaveRequest->start_date)->year ?: (int) date('Y');
                    $balanceSnapshot = $this->balanceService->getBalanceSnapshot(
                        $leaveRequest->user,
                        $leaveRequest->leaveType,
                        $balanceYear
                    );
                    $steps[0]['leave_balance_snapshot'] = [
                        'leave_type_id' => (int) $leaveRequest->leave_type_id,
                        'year' => $balanceYear,
                        'remaining_balance' => max(0, (int) ($balanceSnapshot['remaining_balance'] ?? 0)),
                    ];
                }

                $leaveRequest->applicant_signature_path = null;
                $leaveRequest->applicant_signature_mime = null;
                $leaveRequest->applicant_signature_size = null;

                $leaveRequest->status = LeaveRequest::STATUS_SUBMITTED;
                $leaveRequest->submitted_at = Carbon::now();
                $leaveRequest->approver_chain_snapshot = $steps;
                $leaveRequest->save();
                $leaveRequest->approvals()->delete();
                foreach ($steps as $index => $step) {
                    $leaveRequest->approvals()->create([
                        'step_no' => $step['step_no'],
                        'role_name' => $step['role_name'],
                        'approver_id' => $step['approver_id'],
                        'status' => $index === 0 ? 'pending' : 'waiting',
                    ]);
                }
                $this->balanceService->reserve($leaveRequest);
        });
    }

    public function approve(LeaveApproval $approval, User $actor, $note = null, $signatureData = null)
    {
        if ($approval->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Approval cuti ini tidak berada pada status pending.']);
        }
        $previousStatus = $approval->status;

        if ($approval->role_name === 'verifikator_dokumen') {
            $unverifiedCount = $approval->leaveRequest->documents()->where('is_verified', false)->count();
            if ($unverifiedCount > 0) {
                throw ValidationException::withMessages(['documents' => 'Masih ada dokumen yang belum diverifikasi.']);
            }
        }

        DB::transaction(function () use ($approval, $actor, $note) {
            $approval->status = 'approved';
            $approval->action = 'approved';
            $approval->acted_at = Carbon::now();
            $approval->signature_path = null;
            $approval->signature_mime = null;
            $approval->signature_size = null;
            $approval->note = $note;
            $approval->save();
            $leaveRequest = $approval->leaveRequest;
            $this->ensureLegacySamePersonFinalApproval($leaveRequest, $approval);
            $next = $leaveRequest->approvals()->where('step_no', '>', $approval->step_no)->orderBy('step_no')->first();
            if ($next) {
                $next->status = 'pending';
                $next->signature_path = null;
                $next->signature_mime = null;
                $next->signature_size = null;
                $next->save();
                $leaveRequest->status = $approval->role_name === 'verifikator_dokumen'
                    ? LeaveRequest::STATUS_VERIFIED
                    : LeaveRequest::STATUS_UNDER_REVIEW;
                $leaveRequest->updated_by = $actor->id;
                $leaveRequest->save();
                $this->documentService->syncSuratKeluar($leaveRequest->fresh(['leaveType', 'approvals', 'documents', 'user']), false);
            } else {
                $this->documentService->ensureLetterNumber($leaveRequest);
                $leaveRequest->status = LeaveRequest::STATUS_APPROVED;
                $leaveRequest->approved_at = Carbon::now();
                $leaveRequest->locked_at = Carbon::now();
                $this->balanceService->consume($leaveRequest);
                $leaveRequest->updated_by = $actor->id;
                $leaveRequest->save();
                $this->documentService->syncSuratKeluar($leaveRequest->fresh(['leaveType', 'approvals', 'documents', 'user']), true);
            }
        });

        $approval->loadMissing('leaveRequest.user', 'leaveRequest.leaveType', 'approver');
        $this->auditService->log('cuti', 'leave_approval_approved', $approval, [
            'subject_type' => 'leave_request',
            'subject_id' => optional($approval->leaveRequest)->id,
            'subject_title' => optional(optional($approval->leaveRequest)->user)->name . ' - ' . optional(optional($approval->leaveRequest)->leaveType)->name,
            'target_user_id' => optional($approval->leaveRequest)->user_id,
            'target_name' => optional(optional($approval->leaveRequest)->user)->name,
            'old_values_json' => ['status' => $previousStatus],
            'new_values_json' => ['status' => 'approved'],
            'note' => $note,
        ], $actor);
    }

    public function reject(LeaveApproval $approval, User $actor, $note)
    {
        if ($approval->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Approval cuti ini tidak berada pada status pending.']);
        }
        $previousStatus = $approval->status;
        DB::transaction(function () use ($approval, $actor, $note) {
            $approval->status = 'rejected';
            $approval->action = 'rejected';
            $approval->acted_at = Carbon::now();
            $approval->signature_path = null;
            $approval->signature_mime = null;
            $approval->signature_size = null;
            $approval->note = $note;
            $approval->save();
            $leaveRequest = $approval->leaveRequest;
            $this->documentService->ensureLetterNumber($leaveRequest);
            $leaveRequest->status = LeaveRequest::STATUS_REJECTED;
            $leaveRequest->rejected_at = Carbon::now();
            $leaveRequest->revision_note = $note;
            $leaveRequest->updated_by = $actor->id;
            $leaveRequest->save();
            $this->balanceService->restore($leaveRequest);
            $this->documentService->syncSuratKeluar($leaveRequest->fresh(['leaveType', 'approvals', 'documents', 'user']), true);
        });

        $approval->loadMissing('leaveRequest.user', 'leaveRequest.leaveType', 'approver');
        $this->auditService->log('cuti', 'leave_approval_rejected', $approval, [
            'subject_type' => 'leave_request',
            'subject_id' => optional($approval->leaveRequest)->id,
            'subject_title' => optional(optional($approval->leaveRequest)->user)->name . ' - ' . optional(optional($approval->leaveRequest)->leaveType)->name,
            'target_user_id' => optional($approval->leaveRequest)->user_id,
            'target_name' => optional(optional($approval->leaveRequest)->user)->name,
            'old_values_json' => ['status' => $previousStatus],
            'new_values_json' => ['status' => 'rejected'],
            'note' => $note,
        ], $actor);
    }

    public function requestChange(LeaveApproval $approval, User $actor, $note, $signatureData = null)
    {
        $this->finishWithNonApprovalDecision($approval, $actor, $note, $signatureData, 'changed', LeaveRequest::STATUS_CHANGED, 'leave_approval_changed');
    }

    public function defer(LeaveApproval $approval, User $actor, $note, $signatureData = null)
    {
        $this->finishWithNonApprovalDecision($approval, $actor, $note, $signatureData, 'deferred', LeaveRequest::STATUS_DEFERRED, 'leave_approval_deferred');
    }

    protected function finishWithNonApprovalDecision(LeaveApproval $approval, User $actor, $note, $signatureData, $approvalStatus, $requestStatus, $auditEvent)
    {
        if ($approval->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Approval cuti ini tidak berada pada status pending.']);
        }

        $previousStatus = $approval->status;
        DB::transaction(function () use ($approval, $actor, $note, $approvalStatus, $requestStatus) {
            $approval->status = $approvalStatus;
            $approval->action = $approvalStatus;
            $approval->acted_at = Carbon::now();
            $approval->signature_path = null;
            $approval->signature_mime = null;
            $approval->signature_size = null;
            $approval->note = $note;
            $approval->save();

            $leaveRequest = $approval->leaveRequest;
            $this->documentService->ensureLetterNumber($leaveRequest);
            $leaveRequest->status = $requestStatus;
            $leaveRequest->is_deferred = $requestStatus === LeaveRequest::STATUS_DEFERRED;
            $leaveRequest->deferred_reason = $requestStatus === LeaveRequest::STATUS_DEFERRED ? $note : null;
            $leaveRequest->revision_note = $note;
            $leaveRequest->updated_by = $actor->id;
            $leaveRequest->save();
            $this->balanceService->restore($leaveRequest);
            $this->documentService->syncSuratKeluar($leaveRequest->fresh(['leaveType', 'approvals', 'documents', 'user']), true);
        });

        $approval->loadMissing('leaveRequest.user', 'leaveRequest.leaveType', 'approver');
        $this->auditService->log('cuti', $auditEvent, $approval, [
            'subject_type' => 'leave_request',
            'subject_id' => optional($approval->leaveRequest)->id,
            'subject_title' => optional(optional($approval->leaveRequest)->user)->name . ' - ' . optional(optional($approval->leaveRequest)->leaveType)->name,
            'target_user_id' => optional($approval->leaveRequest)->user_id,
            'target_name' => optional(optional($approval->leaveRequest)->user)->name,
            'old_values_json' => ['status' => $previousStatus],
            'new_values_json' => ['status' => $approvalStatus],
            'note' => $note,
        ], $actor);
    }

    protected function ensureLegacySamePersonFinalApproval(LeaveRequest $leaveRequest, LeaveApproval $approval)
    {
        if ($approval->role_name !== 'atasan_langsung') {
            return;
        }

        if ($leaveRequest->approvals()->whereIn('role_name', ['ppk', 'sekretaris_ma'])->exists()) {
            return;
        }

        $user = $leaveRequest->user;
        if (
            !$user
            || (int) $user->atasan_langsung_id <= 0
            || (int) $user->atasan_langsung_id !== (int) $user->pejabat_berwenang_id
        ) {
            return;
        }

        $step = [
            'step_no' => ((int) $leaveRequest->approvals()->max('step_no')) + 1,
            'role_name' => 'ppk',
            'approver_id' => $this->resolveApproverId(
                $user->pejabat_berwenang_id,
                'ppk_approval',
                $leaveRequest->start_date
            ),
        ];

        $leaveRequest->approvals()->create($step + ['status' => 'waiting']);
        $leaveRequest->approver_chain_snapshot = collect($leaveRequest->approver_chain_snapshot ?: [])
            ->push($step)
            ->values()
            ->all();
    }

    protected function resolveApproverId($approverId, $scope, $effectiveDate = null)
    {
        if (!$approverId) {
            return null;
        }

        $effectiveDate = $effectiveDate ?: now()->toDateString();

        $delegation = LeaveDelegation::where('delegator_id', $approverId)
            ->where('scope', $scope)
            ->where('is_active', true)
            ->whereDate('start_date', '<=', $effectiveDate)
            ->whereDate('end_date', '>=', $effectiveDate)
            ->latest('id')
            ->first();

        return $delegation ? $delegation->delegate_id : $approverId;
    }

    protected function resolveDirectSupervisorId(LeaveRequest $leaveRequest)
    {
        $user = $leaveRequest->user;
        if (!$user) {
            return null;
        }

        if ($user->atasan_langsung_id && (int) $user->atasan_langsung_id !== (int) $user->id) {
            return $user->atasan_langsung_id;
        }

        $unitName = Str::lower((string) optional($user->unit)->nama);

        if (Str::contains($unitName, 'kepaniteraan')) {
            $panitera = $this->firstRoleUser(['panitera'], $user->id);
            if ($panitera) {
                return $panitera->id;
            }
        }

        if (Str::contains($unitName, 'kesekretariatan')) {
            $sekretaris = $this->firstRoleUser(['sekretaris'], $user->id);
            if ($sekretaris) {
                return $sekretaris->id;
            }
        }

        $supervisor = User::active()
            ->where('id', '<>', $user->id)
            ->where(function ($query) {
                $query
                    ->withRoleOrDelegatedJabatan(['sekretaris', 'panitera', 'wakil_ketua', 'ketua'])
                    ->orWhereHas('roles', function ($roleQuery) {
                        $roleQuery->where('name', 'atasan_langsung');
                    });
            })
            ->when($user->hirarki, function ($query) use ($user) {
                $query->whereNotNull('hirarki')->where('hirarki', '<', $user->hirarki);
            })
            ->orderByRaw('CASE WHEN hirarki IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('hirarki')
            ->orderBy('name')
            ->first();

        return optional($supervisor)->id;
    }

    protected function firstRoleUser(array $roles, $excludeUserId = null)
    {
        return User::active()
            ->withRoleOrDelegatedJabatan($roles)
            ->when($excludeUserId, function ($query) use ($excludeUserId) {
                $query->where('id', '<>', $excludeUserId);
            })
            ->orderByRaw('CASE WHEN hirarki IS NULL THEN 1 ELSE 0 END')
            ->orderBy('hirarki')
            ->orderBy('name')
            ->first();
    }
}
