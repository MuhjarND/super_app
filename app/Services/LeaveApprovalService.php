<?php

namespace App\Services;

use App\LeaveApproval;
use App\LeaveDelegation;
use App\LeaveRequest;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

        $personnelVerifierId = $this->resolvePersonnelVerifierId();
        if (!$personnelVerifierId) {
            throw ValidationException::withMessages([
                'approval' => 'Kasubag Kepegawaian atau PLT Kasubag Kepegawaian aktif belum ditentukan.',
            ]);
        }

        $steps[] = [
            'step_no' => count($steps) + 1,
            'role_name' => 'verifikator_dokumen',
            'approver_id' => $personnelVerifierId,
        ];

        $directSupervisorId = $this->resolveDirectSupervisorId($leaveRequest);
        if (!$directSupervisorId) {
            throw ValidationException::withMessages([
                'approval' => 'Atasan langsung pegawai belum ditentukan pada data user.',
            ]);
        }

        $steps[] = [
            'step_no' => count($steps) + 1,
            'role_name' => 'atasan_langsung',
            'approver_id' => $this->resolveApproverId($directSupervisorId, 'leave_approval', $leaveRequest->start_date),
        ];

        $sekma = $leaveRequest->is_abroad ? $this->firstRoleUser(['sekretaris_ma', 'sekretaris_mahkamah_agung']) : null;
        $finalApproverId = optional($sekma)->id ?: optional($leaveRequest->user)->pejabat_berwenang_id;

        if (!$finalApproverId) {
            throw ValidationException::withMessages([
                'approval' => 'Pejabat yang berwenang memberikan cuti belum ditentukan pada data user.',
            ]);
        }

        // Keep both records even when the official is the same. One action will
        // approve both records later, while each record still produces its own QR.
        $steps[] = [
            'step_no' => count($steps) + 1,
            'role_name' => $sekma ? 'sekretaris_ma' : 'ppk',
            'approver_id' => $this->resolveApproverId($finalApproverId, 'ppk_approval', $leaveRequest->start_date),
        ];

        return $steps;
    }

    public function submit(LeaveRequest $leaveRequest, $applicantSignatureData = null)
    {
        DB::transaction(function () use ($leaveRequest) {
                $steps = $this->buildApprovalSteps($leaveRequest);
                if (empty($steps)) {
                    throw ValidationException::withMessages(['status' => 'Rantai approval cuti belum terbentuk.']);
                }
                $isSatkerRequest = $leaveRequest->user && $leaveRequest->user->isSatker();
                if (empty($leaveRequest->request_number) && !$isSatkerRequest) {
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
                $leaveRequest->approved_days = $leaveRequest->requestedTotalDays();
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

    public function approve(LeaveApproval $approval, User $actor, $note = null, $signatureData = null, $grantTravelLeave = false)
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

        $linkedApproval = null;

        DB::transaction(function () use ($approval, $actor, $note, $grantTravelLeave, &$linkedApproval) {
            $actedAt = Carbon::now();
            $approval->status = 'approved';
            $approval->action = 'approved';
            $approval->acted_at = $actedAt;
            $approval->signature_path = null;
            $approval->signature_mime = null;
            $approval->signature_size = null;
            $approval->note = $note;
            $approval->save();
            $leaveRequest = $approval->leaveRequest;
            $this->ensureLegacySamePersonFinalApproval($leaveRequest, $approval);
            $linkedApproval = $this->approveMatchingFinalStage(
                $leaveRequest,
                $approval,
                $actor,
                $note,
                $actedAt
            );
            $next = $leaveRequest->approvals()
                ->where('step_no', '>', $approval->step_no)
                ->whereIn('status', ['waiting', 'pending'])
                ->orderBy('step_no')
                ->first();
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
            } else {
                $travelLeaveGranted = $leaveRequest->travel_leave_requested && (bool) $grantTravelLeave;
                $leaveRequest->travel_leave_granted = $travelLeaveGranted;
                $leaveRequest->approved_days = $leaveRequest->approvedTotalDays();
                $this->balanceService->validateFinalBalance($leaveRequest);

                $decisionApproval = $linkedApproval ?: $approval;
                $decisionApproval->meta_json = array_merge($decisionApproval->meta_json ?: [], [
                    'travel_leave_requested' => (bool) $leaveRequest->travel_leave_requested,
                    'travel_leave_granted' => $travelLeaveGranted,
                ]);
                $decisionApproval->save();

                $leaveRequest->status = LeaveRequest::STATUS_APPROVED;
                $leaveRequest->approved_at = Carbon::now();
                $leaveRequest->locked_at = Carbon::now();
                $this->balanceService->consume($leaveRequest);
                $leaveRequest->updated_by = $actor->id;
                $leaveRequest->save();
                $this->documentService->ensureLetterNumber($leaveRequest);
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

        if ($linkedApproval) {
            $linkedApproval->loadMissing('leaveRequest.user', 'leaveRequest.leaveType', 'approver');
            $this->auditService->log('cuti', 'leave_approval_approved_automatically', $linkedApproval, [
                'subject_type' => 'leave_request',
                'subject_id' => optional($linkedApproval->leaveRequest)->id,
                'subject_title' => optional(optional($linkedApproval->leaveRequest)->user)->name . ' - ' . optional(optional($linkedApproval->leaveRequest)->leaveType)->name,
                'target_user_id' => optional($linkedApproval->leaveRequest)->user_id,
                'target_name' => optional(optional($linkedApproval->leaveRequest)->user)->name,
                'old_values_json' => ['status' => 'waiting'],
                'new_values_json' => [
                    'status' => 'approved',
                    'approved_with_step_id' => $approval->id,
                ],
                'note' => $note,
            ], $actor);
        }
    }

    public function reject(LeaveApproval $approval, User $actor, $note)
    {
        if ($approval->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Approval cuti ini tidak berada pada status pending.']);
        }
        $previousStatus = $approval->status;
        DB::transaction(function () use ($approval, $actor, $note) {
            $actedAt = Carbon::now();
            $approval->status = 'rejected';
            $approval->action = 'rejected';
            $approval->acted_at = $actedAt;
            $approval->signature_path = null;
            $approval->signature_mime = null;
            $approval->signature_size = null;
            $approval->note = $note;
            $approval->save();
            $leaveRequest = $approval->leaveRequest;

            $leaveRequest->approvals()
                ->where('step_no', '>', $approval->step_no)
                ->whereIn('status', ['waiting', 'pending'])
                ->get()
                ->each(function (LeaveApproval $nextApproval) use ($approval, $actedAt) {
                    $nextApproval->status = 'cancelled';
                    $nextApproval->action = 'stopped_after_rejection';
                    $nextApproval->acted_at = $actedAt->copy();
                    $nextApproval->note = 'Alur dihentikan karena pengajuan ditolak pada tahap ' . $approval->role_label . '.';
                    $nextApproval->meta_json = array_merge($nextApproval->meta_json ?: [], [
                        'stopped_by_approval_id' => $approval->id,
                    ]);
                    $nextApproval->save();
                });

            $leaveRequest->status = LeaveRequest::STATUS_REJECTED;
            $leaveRequest->rejected_at = $actedAt;
            $leaveRequest->locked_at = $actedAt;
            $leaveRequest->travel_leave_granted = false;
            $leaveRequest->revision_note = $note;
            $leaveRequest->updated_by = $actor->id;
            $leaveRequest->save();
            $this->balanceService->restore($leaveRequest);
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
            $leaveRequest->status = $requestStatus;
            $leaveRequest->is_deferred = $requestStatus === LeaveRequest::STATUS_DEFERRED;
            $leaveRequest->deferred_reason = $requestStatus === LeaveRequest::STATUS_DEFERRED ? $note : null;
            $leaveRequest->revision_note = $note;
            $leaveRequest->updated_by = $actor->id;
            $leaveRequest->save();
            $this->balanceService->restore($leaveRequest);
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

    protected function approveMatchingFinalStage(
        LeaveRequest $leaveRequest,
        LeaveApproval $approval,
        User $actor,
        $note,
        Carbon $actedAt
    ) {
        if ($approval->role_name !== 'atasan_langsung') {
            return null;
        }

        $next = $leaveRequest->approvals()
            ->where('step_no', '>', $approval->step_no)
            ->orderBy('step_no')
            ->lockForUpdate()
            ->first();

        if (
            !$next
            || $next->status !== 'waiting'
            || !in_array($next->role_name, ['ppk', 'sekretaris_ma'], true)
            || (int) $next->approver_id !== (int) $approval->approver_id
        ) {
            return null;
        }

        $next->status = 'approved';
        $next->action = 'approved';
        $next->acted_at = $actedAt->copy();
        $next->signature_path = null;
        $next->signature_mime = null;
        $next->signature_size = null;
        $next->note = $note;
        $next->meta_json = array_merge($next->meta_json ?: [], [
            'approved_automatically' => true,
            'approved_with_step_id' => $approval->id,
            'decision_actor_id' => $actor->id,
        ]);
        $next->save();

        return $next;
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

    protected function resolvePersonnelVerifierId()
    {
        $actingOfficial = User::active()
            ->whereHas('activeJabatanDelegations', function ($delegationQuery) {
                $delegationQuery
                    ->where('delegation_type', 'plt')
                    ->whereHas('jabatan', function ($positionQuery) {
                        $positionQuery->where('kode', 'KASUBAG_KEPEG');
                    });
            })
            ->ordered()
            ->first();

        if ($actingOfficial) {
            return $actingOfficial->id;
        }

        return optional(
            User::active()
                ->whereHas('jabatan', function ($positionQuery) {
                    $positionQuery->where('kode', 'KASUBAG_KEPEG');
                })
                ->ordered()
                ->first()
        )->id;
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

        return null;
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
