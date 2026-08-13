<?php

namespace App\Services;

use App\LeaveBalance;
use App\LeaveRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

            $oldAccountedDays = (int) ($request->approved_days
                ?: $request->requested_days
                ?: $request->workday_count);
            $newDays = $this->dateService->countEffectiveDates(
                $request->start_date,
                $request->end_date,
                $request->leaveType
            );

            $requestChanged = (int) $request->requested_days !== $newDays
                || (int) $request->workday_count !== $newDays
                || ($this->shouldStoreApprovedDays($request) && (int) $request->approved_days !== $newDays);

            if (!$requestChanged) {
                return false;
            }

            $request->requested_days = $newDays;
            $request->workday_count = $newDays;
            if ($this->shouldStoreApprovedDays($request)) {
                $request->approved_days = $newDays;
            }
            $request->save();

            $this->adjustBalance($request, $newDays - $oldAccountedDays);

            return true;
        });
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
