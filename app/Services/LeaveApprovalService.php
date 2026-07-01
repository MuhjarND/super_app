<?php

namespace App\Services;

use App\LeaveApproval;
use App\LeaveDelegation;
use App\LeaveRequest;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LeaveApprovalService
{
    protected $balanceService;
    protected $numberService;
    protected $documentService;
    protected $auditService;
    protected $signaturePadService;

    public function __construct(LeaveBalanceService $balanceService, LeaveNumberService $numberService, LeaveDocumentService $documentService, ActivityAuditService $auditService, SignaturePadService $signaturePadService)
    {
        $this->balanceService = $balanceService;
        $this->numberService = $numberService;
        $this->documentService = $documentService;
        $this->auditService = $auditService;
        $this->signaturePadService = $signaturePadService;
    }

    public function buildApprovalSteps(LeaveRequest $leaveRequest)
    {
        $steps = [];
        $leaveRequest->loadMissing(['user.atasanLangsung.atasanLangsung', 'user.unit', 'user.roles']);

        $verifikator = User::active()->whereHas('roles', function ($q) {
            $q->where('name', 'verifikator_dokumen');
        })->orderBy('id')->first();

        if ($verifikator) {
            $steps[] = [
                'step_no' => count($steps) + 1,
                'role_name' => 'verifikator_dokumen',
                'approver_id' => $this->resolveApproverId($verifikator->id, 'document_verification', $leaveRequest->start_date),
            ];
        }

        $directSupervisorId = $this->resolveDirectSupervisorId($leaveRequest);
        if ($directSupervisorId) {
            $steps[] = [
                'step_no' => count($steps) + 1,
                'role_name' => 'atasan_langsung',
                'approver_id' => $this->resolveApproverId($directSupervisorId, 'leave_approval', $leaveRequest->start_date),
            ];
        }

        $chairman = User::active()->whereHas('roles', function ($q) {
            $q->where('name', 'ketua');
        })->orderBy('id')->first();
        $sekma = $leaveRequest->is_abroad ? $this->firstRoleUser(['sekretaris_ma', 'sekretaris_mahkamah_agung']) : null;
        $finalApproverId = optional($sekma)->id ?: (optional($leaveRequest->user)->pejabat_berwenang_id ?: optional($chairman)->id);
        $existingApproverIds = collect($steps)->pluck('approver_id')->filter()->map(function ($value) {
            return (int) $value;
        })->all();

        if ($finalApproverId && !in_array((int) $finalApproverId, $existingApproverIds, true)) {
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
        $oldSignaturePath = $leaveRequest->applicant_signature_path;
        $newSignaturePath = null;

        try {
            DB::transaction(function () use ($leaveRequest, $applicantSignatureData, &$newSignaturePath) {
                $steps = $this->buildApprovalSteps($leaveRequest);
                if (empty($steps)) {
                    throw ValidationException::withMessages(['status' => 'Rantai approval cuti belum terbentuk.']);
                }
                if (empty($leaveRequest->request_number)) {
                    $number = $this->numberService->next('leave_request', optional($leaveRequest->start_date)->year ?: date('Y'), 'CUTI');
                    $leaveRequest->request_number = $number['formatted'];
                }

                if ($applicantSignatureData) {
                    $signature = $this->signaturePadService->storeDataUri($applicantSignatureData, 'cuti/applicant-signatures');
                    $newSignaturePath = $signature['path'];
                    $leaveRequest->applicant_signature_path = $signature['path'];
                    $leaveRequest->applicant_signature_mime = $signature['mime'];
                    $leaveRequest->applicant_signature_size = $signature['size'];
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
        } catch (\Throwable $exception) {
            if ($newSignaturePath) {
                Storage::disk('public')->delete($newSignaturePath);
            }

            throw $exception;
        }

        if ($newSignaturePath && $oldSignaturePath && $oldSignaturePath !== $newSignaturePath) {
            Storage::disk('public')->delete($oldSignaturePath);
        }
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

        DB::transaction(function () use ($approval, $actor, $note, $signatureData) {
            $signature = $this->signaturePadService->storeDataUri($signatureData, 'cuti/approval-signatures');
            $approval->status = 'approved';
            $approval->action = 'approved';
            $approval->acted_at = Carbon::now();
            $approval->signature_path = $signature['path'];
            $approval->signature_mime = $signature['mime'];
            $approval->signature_size = $signature['size'];
            $approval->note = $note;
            $approval->save();
            $leaveRequest = $approval->leaveRequest;
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
        DB::transaction(function () use ($approval, $actor, $note, $signatureData, $approvalStatus, $requestStatus) {
            $signature = $this->signaturePadService->storeDataUri($signatureData, 'cuti/approval-signatures');
            $approval->status = $approvalStatus;
            $approval->action = $approvalStatus;
            $approval->acted_at = Carbon::now();
            $approval->signature_path = $signature['path'];
            $approval->signature_mime = $signature['mime'];
            $approval->signature_size = $signature['size'];
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
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['atasan_langsung', 'sekretaris', 'panitera', 'wakil_ketua', 'ketua']);
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
            ->whereHas('roles', function ($query) use ($roles) {
                $query->whereIn('name', $roles);
            })
            ->when($excludeUserId, function ($query) use ($excludeUserId) {
                $query->where('id', '<>', $excludeUserId);
            })
            ->orderByRaw('CASE WHEN hirarki IS NULL THEN 1 ELSE 0 END')
            ->orderBy('hirarki')
            ->orderBy('name')
            ->first();
    }
}
