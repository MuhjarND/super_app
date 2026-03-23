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

    public function __construct(LeaveBalanceService $balanceService, LeaveNumberService $numberService, LeaveDocumentService $documentService)
    {
        $this->balanceService = $balanceService;
        $this->numberService = $numberService;
        $this->documentService = $documentService;
    }

    public function buildApprovalSteps(LeaveRequest $leaveRequest)
    {
        $steps = [];
        $leaveRequest->loadMissing(['user.atasanLangsung.atasanLangsung']);

        $verifikator = User::whereHas('roles', function ($q) {
            $q->where('name', 'verifikator_dokumen');
        })->orderBy('id')->first();

        if ($verifikator) {
            $steps[] = [
                'step_no' => count($steps) + 1,
                'role_name' => 'verifikator_dokumen',
                'approver_id' => $this->resolveApproverId($verifikator->id, 'document_verification', $leaveRequest->start_date),
            ];
        }

        $directSupervisorId = optional($leaveRequest->user)->atasan_langsung_id;
        if ($directSupervisorId) {
            $steps[] = [
                'step_no' => count($steps) + 1,
                'role_name' => 'atasan_langsung',
                'approver_id' => $this->resolveApproverId($directSupervisorId, 'leave_approval', $leaveRequest->start_date),
            ];
        }

        $chairman = User::whereHas('roles', function ($q) {
            $q->where('name', 'ketua');
        })->orderBy('id')->first();
        $finalApproverId = optional($leaveRequest->user)->pejabat_berwenang_id ?: optional($chairman)->id;
        $existingApproverIds = collect($steps)->pluck('approver_id')->filter()->map(function ($value) {
            return (int) $value;
        })->all();

        if ($finalApproverId && !in_array((int) $finalApproverId, $existingApproverIds, true)) {
            $steps[] = [
                'step_no' => count($steps) + 1,
                'role_name' => 'ppk',
                'approver_id' => $this->resolveApproverId($finalApproverId, 'ppk_approval', $leaveRequest->start_date),
            ];
        }

        return $steps;
    }

    public function submit(LeaveRequest $leaveRequest)
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

    public function approve(LeaveApproval $approval, User $actor, $note = null)
    {
        if ($approval->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Approval cuti ini tidak berada pada status pending.']);
        }

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
            $approval->note = $note;
            $approval->save();
            $leaveRequest = $approval->leaveRequest;
            $next = $leaveRequest->approvals()->where('step_no', '>', $approval->step_no)->orderBy('step_no')->first();
            if ($next) {
                $next->status = 'pending';
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
    }

    public function reject(LeaveApproval $approval, User $actor, $note)
    {
        if ($approval->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Approval cuti ini tidak berada pada status pending.']);
        }
        DB::transaction(function () use ($approval, $actor, $note) {
            $approval->status = 'rejected';
            $approval->action = 'rejected';
            $approval->acted_at = Carbon::now();
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
}
