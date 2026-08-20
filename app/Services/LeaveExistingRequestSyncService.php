<?php

namespace App\Services;

use App\LeaveBalance;
use App\LeaveAuditTrail;
use App\LeaveRequest;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class LeaveExistingRequestSyncService
{
    protected $dateService;

    public function __construct(LeaveDateService $dateService)
    {
        $this->dateService = $dateService;
    }

    public function syncAll()
    {
        if (!$this->tablesReady()) {
            return 0;
        }

        return $this->syncQuery(LeaveRequest::query());
    }

    public function syncOverlappingDates(array $dates)
    {
        if (!$this->tablesReady()) {
            return 0;
        }

        $dates = collect($dates)
            ->filter()
            ->map(function ($date) {
                return $date instanceof \DateTimeInterface
                    ? $date->format('Y-m-d')
                    : date('Y-m-d', strtotime((string) $date));
            })
            ->filter()
            ->unique()
            ->values();

        if ($dates->isEmpty()) {
            return 0;
        }

        $query = LeaveRequest::query()->where(function (Builder $query) use ($dates) {
            foreach ($dates as $date) {
                $query->orWhere(function (Builder $dateQuery) use ($date) {
                    $dateQuery->whereDate('start_date', '<=', $date)
                        ->whereDate('end_date', '>=', $date);
                });
            }
        });

        return $this->syncQuery($query);
    }

    public function syncRequest(LeaveRequest $leaveRequest)
    {
        return DB::transaction(function () use ($leaveRequest) {
            $request = LeaveRequest::with('leaveType')->lockForUpdate()->find($leaveRequest->id);
            if (!$request || !$request->leaveType) {
                return false;
            }

            $oldAccountedDays = $request->balanceDaysForCurrentStatus();
            $newDays = $this->dateService->countEffectiveDates(
                $request->start_date,
                $request->end_date,
                $request->leaveType
            );

            $newApprovedDays = $newDays;
            $newAccountedDays = max(0, $newDays - (
                in_array($request->status, $this->reservedStatuses(), true) && $request->travel_leave_requested ? 1 : 0
            ) - (
                in_array($request->status, $this->usedStatuses(), true) && $request->travel_leave_granted ? 1 : 0
            ));

            $requestChanged = (int) $request->requested_days !== $newDays
                || (int) $request->workday_count !== $newDays
                || ($this->shouldStoreApprovedDays($request) && (int) $request->approved_days !== $newApprovedDays);

            if (!$requestChanged) {
                return false;
            }

            $request->requested_days = $newDays;
            $request->workday_count = $newDays;
            if ($this->shouldStoreApprovedDays($request)) {
                $request->approved_days = $newApprovedDays;
            }
            $request->save();

            $this->adjustBalance($request, $newAccountedDays - $oldAccountedDays);

            return true;
        });
    }

    public function updateRequestDates(LeaveRequest $leaveRequest, $startDate, $endDate, User $actor, $note)
    {
        return DB::transaction(function () use ($leaveRequest, $startDate, $endDate, $actor, $note) {
            $request = LeaveRequest::with('leaveType')->lockForUpdate()->findOrFail($leaveRequest->id);
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->startOfDay();
            $oldStart = optional($request->start_date)->toDateString();
            $oldEnd = optional($request->end_date)->toDateString();
            $oldYear = optional($request->start_date)->year;
            $oldDays = $request->regularLeaveDays();

            if ($oldYear && (int) $oldYear !== (int) $start->year) {
                throw ValidationException::withMessages([
                    'start_date' => 'Tanggal mulai baru harus berada pada tahun yang sama dengan pengajuan semula agar saldo tahunan tidak berpindah tahun.',
                ]);
            }

            $newDays = $this->dateService->countEffectiveDates($start, $end, $request->leaveType);
            if ($newDays < 1) {
                throw ValidationException::withMessages([
                    'end_date' => 'Rentang tanggal baru tidak memiliki hari cuti efektif setelah hari libur dan cuti bersama dikeluarkan.',
                ]);
            }

            $overlaps = LeaveRequest::where('user_id', $request->user_id)
                ->where('id', '!=', $request->id)
                ->whereIn('status', array_merge($this->reservedStatuses(), $this->usedStatuses()))
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                        ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                        ->orWhere(function ($nested) use ($start, $end) {
                            $nested->whereDate('start_date', '<=', $start->toDateString())
                                ->whereDate('end_date', '>=', $end->toDateString());
                        });
                })
                ->exists();

            if ($overlaps) {
                throw ValidationException::withMessages(['start_date' => 'Tanggal cuti baru bentrok dengan pengajuan lain milik pegawai tersebut.']);
            }

            $oldAccountedDays = $this->accountedDays($request, $request->regularLeaveDays());
            $newAccountedDays = $this->accountedDays($request, $newDays);
            $difference = $newAccountedDays - $oldAccountedDays;

            if ($difference > 0 && $request->leaveType && $request->leaveType->requires_balance) {
                $balance = LeaveBalance::where('user_id', $request->user_id)
                    ->where('leave_type_id', $request->leave_type_id)
                    ->where('year', $start->year)
                    ->lockForUpdate()
                    ->first();
                $availableForRequest = (int) optional($balance)->remaining_balance + $oldAccountedDays;

                if (!$balance || $newAccountedDays > $availableForRequest) {
                    throw ValidationException::withMessages([
                        'end_date' => sprintf(
                            'Saldo cuti tidak mencukupi untuk perubahan tanggal. Dibutuhkan %d hari, sedangkan saldo yang tersedia untuk pengajuan ini %d hari.',
                            $newAccountedDays,
                            max(0, $availableForRequest)
                        ),
                    ]);
                }
            }

            $request->start_date = $start;
            $request->end_date = $end;
            $request->requested_days = $newDays;
            $request->workday_count = $newDays;
            if ($this->shouldStoreApprovedDays($request)) {
                $request->approved_days = $newDays;
            }
            $request->updated_by = $actor->id;
            $request->save();

            $this->adjustBalance($request, $difference);

            if (Schema::hasTable('leave_audit_trails')) {
                LeaveAuditTrail::create([
                    'leave_request_id' => $request->id,
                    'actor_id' => $actor->id,
                    'event' => 'dates_updated_by_superadmin',
                    'old_values_json' => ['start_date' => $oldStart, 'end_date' => $oldEnd, 'days' => $oldDays],
                    'new_values_json' => ['start_date' => $start->toDateString(), 'end_date' => $end->toDateString(), 'days' => $newDays],
                    'note' => trim((string) $note),
                    'ip_address' => request() ? request()->ip() : null,
                    'user_agent' => request() ? request()->userAgent() : null,
                    'created_at' => now('Asia/Jayapura'),
                ]);
            }

            return $request->fresh('leaveType');
        });
    }

    protected function accountedDays(LeaveRequest $request, $effectiveDays)
    {
        if (in_array($request->status, $this->reservedStatuses(), true)) {
            return max(0, (int) $effectiveDays - ($request->travel_leave_requested ? 1 : 0));
        }

        if (in_array($request->status, $this->usedStatuses(), true)) {
            return max(0, (int) $effectiveDays - ($request->travel_leave_granted ? 1 : 0));
        }

        return 0;
    }

    protected function syncQuery(Builder $query)
    {
        $updated = 0;

        $query->select('id')->orderBy('id')->chunkById(100, function ($requests) use (&$updated) {
            foreach ($requests as $request) {
                if ($this->syncRequest($request)) {
                    $updated++;
                }
            }
        });

        return $updated;
    }

    protected function adjustBalance(LeaveRequest $request, $difference)
    {
        if ($difference === 0 || !$request->leaveType->requires_balance) {
            return;
        }

        $balance = LeaveBalance::where('user_id', $request->user_id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', optional($request->start_date)->year)
            ->lockForUpdate()
            ->first();

        if (!$balance) {
            return;
        }

        if (in_array($request->status, $this->reservedStatuses(), true)) {
            $balance->reserved_days = max(0, (int) $balance->reserved_days + $difference);
        } elseif (in_array($request->status, $this->usedStatuses(), true)) {
            $balance->used_days = max(0, (int) $balance->used_days + $difference);
        } else {
            return;
        }

        $balance->remaining_balance = (int) $balance->opening_balance
            + (int) $balance->entitlement
            + (int) $balance->carry_forward
            + (int) $balance->adjustment_plus
            - (int) $balance->adjustment_minus
            - (int) $balance->used_days
            - (int) $balance->reserved_days;
        $balance->save();
    }

    protected function shouldStoreApprovedDays(LeaveRequest $request)
    {
        return (int) $request->approved_days > 0 || $request->status !== LeaveRequest::STATUS_DRAFT;
    }

    protected function reservedStatuses()
    {
        return [
            LeaveRequest::STATUS_SUBMITTED,
            LeaveRequest::STATUS_UNDER_REVIEW,
            LeaveRequest::STATUS_VERIFIED,
        ];
    }

    protected function usedStatuses()
    {
        return [
            LeaveRequest::STATUS_APPROVED,
            LeaveRequest::STATUS_COMPLETED,
        ];
    }

    protected function tablesReady()
    {
        return Schema::hasTable('leave_requests')
            && Schema::hasTable('leave_types')
            && Schema::hasTable('leave_holidays');
    }
}
