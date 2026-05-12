<?php

namespace App\Services;

use App\LeaveBalance;
use App\LeaveRequest;
use App\LeaveType;
use App\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Optional;

class LeaveBalanceService
{
    public function getBalanceSnapshot(User $user, LeaveType $leaveType, $year)
    {
        $year = $year ?: now()->year;
        $balance = LeaveBalance::firstOrNew(['user_id' => $user->id, 'leave_type_id' => $leaveType->id, 'year' => $year]);
        if (!$balance->exists) {
            $balance->opening_balance = 0;
            $balance->entitlement = $leaveType->code === LeaveType::CODE_TAHUNAN ? 12 : 0;
            $balance->carry_forward = 0;
            $balance->adjustment_plus = 0;
            $balance->adjustment_minus = 0;
            $balance->used_days = 0;
            $balance->reserved_days = 0;
            $balance->remaining_balance = $balance->opening_balance + $balance->entitlement;
        }
        return $balance->toArray();
    }

    public function reserve(LeaveRequest $leaveRequest)
    {
        if (!$leaveRequest->leaveType || !$leaveRequest->leaveType->requires_balance) { return; }
        DB::transaction(function () use ($leaveRequest) {
            $year = $this->yearFromLeaveRequest($leaveRequest);
            $balance = LeaveBalance::firstOrCreate(['user_id' => $leaveRequest->user_id, 'leave_type_id' => $leaveRequest->leave_type_id, 'year' => $year], ['opening_balance' => 0, 'entitlement' => $leaveRequest->leaveType->code === LeaveType::CODE_TAHUNAN ? 12 : 0, 'carry_forward' => 0, 'adjustment_plus' => 0, 'adjustment_minus' => 0, 'used_days' => 0, 'reserved_days' => 0, 'remaining_balance' => $leaveRequest->leaveType->code === LeaveType::CODE_TAHUNAN ? 12 : 0]);
            $days = (int) ($leaveRequest->approved_days ?: $leaveRequest->requested_days);
            $balance->reserved_days += $days;
            $balance->remaining_balance -= $days;
            $balance->save();
        });
    }

    public function consume(LeaveRequest $leaveRequest)
    {
        if (!$leaveRequest->leaveType || !$leaveRequest->leaveType->requires_balance) { return; }
        DB::transaction(function () use ($leaveRequest) {
            $balance = LeaveBalance::where('user_id', $leaveRequest->user_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('year', $this->yearFromLeaveRequest($leaveRequest))
                ->first();
            if (!$balance) { return; }
            $days = (int) ($leaveRequest->approved_days ?: $leaveRequest->requested_days);
            $balance->reserved_days = max(0, (int) $balance->reserved_days - $days);
            $balance->used_days += $days;
            $balance->save();
        });
    }

    public function restore(LeaveRequest $leaveRequest)
    {
        if (!$leaveRequest->leaveType || !$leaveRequest->leaveType->requires_balance) { return; }
        DB::transaction(function () use ($leaveRequest) {
            $balance = LeaveBalance::where('user_id', $leaveRequest->user_id)
                ->where('leave_type_id', $leaveRequest->leave_type_id)
                ->where('year', $this->yearFromLeaveRequest($leaveRequest))
                ->first();
            if (!$balance) { return; }
            $days = (int) ($leaveRequest->approved_days ?: $leaveRequest->requested_days);
            $balance->reserved_days = max(0, (int) $balance->reserved_days - $days);
            $balance->remaining_balance += $days;
            $balance->save();
        });
    }

    protected function yearFromLeaveRequest(LeaveRequest $leaveRequest)
    {
        $date = $this->normalizeDate($leaveRequest->start_date);

        return $date ? $date->year : now()->year;
    }

    protected function normalizeDate($value)
    {
        if ($value instanceof Optional) {
            $value = $value->toDateString() ?: $value->toDateTimeString() ?: null;
        }

        if ($value instanceof Carbon) {
            return $value->copy();
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value);
        }

        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }
}
