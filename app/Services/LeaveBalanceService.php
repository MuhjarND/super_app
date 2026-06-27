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
use Illuminate\Validation\ValidationException;

class LeaveBalanceService
{
    protected const ANNUAL_ENTITLEMENT_DAYS = 12;
    protected const ANNUAL_CARRY_FORWARD_MAX_DAYS = 6;
    protected const ANNUAL_LONG_CARRY_FORWARD_MAX_DAYS = 12;

    public function getBalanceSnapshot(User $user, LeaveType $leaveType, $year)
    {
        $year = $year ?: now()->year;

        $balance = $this->ensureBalance($user, $leaveType, $year);

        $snapshot = $balance->toArray();
        $snapshot['carry_forward_by_year'] = $this->annualCarryForwardBreakdown($snapshot, $year);

        return $snapshot;
    }

    public function recordAnnualRecap(User $user, $year, $previousYearCarryForward, $twoYearsAgoCarryForward, $usedDays, User $actor, $unusedTwoConsecutiveYears = false)
    {
        return DB::transaction(function () use ($user, $year, $previousYearCarryForward, $twoYearsAgoCarryForward, $usedDays, $actor, $unusedTwoConsecutiveYears) {
            $year = (int) $year;
            $previousYear = $year - 1;
            $twoYearsAgo = $year - 2;
            $unusedTwoConsecutiveYears = (bool) $unusedTwoConsecutiveYears;

            if ($unusedTwoConsecutiveYears
                && ((int) $previousYearCarryForward < self::ANNUAL_CARRY_FORWARD_MAX_DAYS
                    || (int) $twoYearsAgoCarryForward < self::ANNUAL_CARRY_FORWARD_MAX_DAYS)) {
                throw ValidationException::withMessages([
                    'unused_two_consecutive_years' => 'Hak 24 hari hanya berlaku jika cuti tahunan tidak digunakan sama sekali pada dua tahun sebelumnya.',
                ]);
            }

            $carryForwardByYear = $this->normalizeAnnualCarryForwardByYear(
                $year,
                $previousYearCarryForward,
                $twoYearsAgoCarryForward,
                $unusedTwoConsecutiveYears
            );
            $carryForward = array_sum($carryForwardByYear);
            $leaveType = LeaveType::where('code', LeaveType::CODE_TAHUNAN)
                ->where('requires_balance', true)
                ->firstOrFail();
            $balance = $this->ensureBalance($user, $leaveType, $year);
            $totalBalance = self::ANNUAL_ENTITLEMENT_DAYS + $carryForward;

            if ($carryForward > self::ANNUAL_LONG_CARRY_FORWARD_MAX_DAYS) {
                throw ValidationException::withMessages([
                    'carry_forward_previous_year' => 'Total sisa cuti dari dua tahun sebelumnya maksimal 12 hari.',
                ]);
            }

            if ((int) $usedDays > $totalBalance) {
                throw ValidationException::withMessages([
                    'used_days' => 'Jumlah terpakai tidak boleh melebihi total hak ' . $totalBalance . ' hari.',
                ]);
            }

            $meta = (array) ($balance->meta_json ?: []);
            $meta['annual_recap'] = [
                'manual_carry_forward' => true,
                'carry_forward_by_year' => $carryForwardByYear,
                'unused_two_consecutive_years' => $unusedTwoConsecutiveYears,
                'input_by' => $actor->id,
                'input_at' => now()->toIso8601String(),
            ];

            $balance->entitlement = self::ANNUAL_ENTITLEMENT_DAYS;
            $balance->carry_forward = $carryForward;
            $balance->used_days = (int) $usedDays;
            $balance->meta_json = $meta;

            return $this->recalculateRemaining($balance);
        });
    }

    public function annualCarryForwardBreakdown($balanceData, $year)
    {
        $year = (int) ($year ?: now()->year);
        $previousYear = $year - 1;
        $twoYearsAgo = $year - 2;
        $totalCarryForward = (int) data_get($balanceData, 'carry_forward', 0);
        $meta = data_get($balanceData, 'meta_json', []);

        if (is_string($meta)) {
            $meta = json_decode($meta, true) ?: [];
        }

        $storedBreakdown = (array) data_get($meta, 'annual_recap.carry_forward_by_year', []);
        $unusedTwoConsecutiveYears = (bool) data_get($meta, 'annual_recap.unused_two_consecutive_years', false);
        $breakdown = $this->normalizeAnnualCarryForwardByYear(
            $year,
            $storedBreakdown[(string) $previousYear] ?? $storedBreakdown[$previousYear] ?? 0,
            $storedBreakdown[(string) $twoYearsAgo] ?? $storedBreakdown[$twoYearsAgo] ?? 0,
            $unusedTwoConsecutiveYears
        );

        if (!empty($storedBreakdown)) {
            return $breakdown;
        }

        $recordedTotal = array_sum($breakdown);
        if ($recordedTotal > $totalCarryForward) {
            $overflow = $recordedTotal - $totalCarryForward;
            $fromTwoYearsAgo = min($overflow, $breakdown[$twoYearsAgo]);
            $breakdown[$twoYearsAgo] -= $fromTwoYearsAgo;
            $overflow -= $fromTwoYearsAgo;
            $breakdown[$previousYear] = max(0, $breakdown[$previousYear] - $overflow);
            $recordedTotal = array_sum($breakdown);
        }

        $remaining = max(0, $totalCarryForward - $recordedTotal);

        if ($remaining > 0) {
            $previousCapacity = max(0, self::ANNUAL_CARRY_FORWARD_MAX_DAYS - $breakdown[$previousYear]);
            $fromPreviousYear = min($remaining, $previousCapacity);
            $breakdown[$previousYear] += $fromPreviousYear;
            $remaining -= $fromPreviousYear;
            $breakdown[$twoYearsAgo] += $remaining;
        }

        return $breakdown;
    }

    protected function normalizeAnnualCarryForwardByYear($year, $previousYearCarryForward, $twoYearsAgoCarryForward, $unusedTwoConsecutiveYears = false)
    {
        $year = (int) ($year ?: now()->year);
        $previousYear = $year - 1;
        $twoYearsAgo = $year - 2;
        $previous = min(max((int) $previousYearCarryForward, 0), self::ANNUAL_CARRY_FORWARD_MAX_DAYS);
        $twoYears = min(max((int) $twoYearsAgoCarryForward, 0), self::ANNUAL_CARRY_FORWARD_MAX_DAYS);

        if (!$unusedTwoConsecutiveYears || $previous < self::ANNUAL_CARRY_FORWARD_MAX_DAYS) {
            $twoYears = 0;
        }

        return [
            $previousYear => $previous,
            $twoYearsAgo => $twoYears,
        ];
    }

    public function reserve(LeaveRequest $leaveRequest)
    {
        if (!$leaveRequest->leaveType || !$leaveRequest->leaveType->requires_balance) { return; }
        DB::transaction(function () use ($leaveRequest) {
            $year = $this->yearFromLeaveRequest($leaveRequest);
            $balance = $this->ensureBalance($leaveRequest->user, $leaveRequest->leaveType, $year);
            $days = (int) ($leaveRequest->approved_days ?: $leaveRequest->requested_days);
            $balance->reserved_days += $days;
            $this->recalculateRemaining($balance);
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
            $this->recalculateRemaining($balance);
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
            $this->recalculateRemaining($balance);
        });
    }

    protected function ensureBalance(User $user = null, LeaveType $leaveType, $year)
    {
        if (!$user) {
            throw new \InvalidArgumentException('User wajib tersedia untuk menghitung saldo cuti.');
        }

        $year = (int) ($year ?: now()->year);
        $isAnnual = $leaveType->code === LeaveType::CODE_TAHUNAN;

        $balance = LeaveBalance::firstOrNew([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'year' => $year,
        ]);

        if (!$balance->exists) {
            $balance->opening_balance = 0;
            $balance->entitlement = $isAnnual ? self::ANNUAL_ENTITLEMENT_DAYS : 0;
            $balance->carry_forward = $isAnnual ? $this->resolveAnnualCarryForward($user, $leaveType, $year) : 0;
            $balance->adjustment_plus = 0;
            $balance->adjustment_minus = 0;
            $balance->used_days = 0;
            $balance->reserved_days = 0;
        } elseif ($isAnnual) {
            $balance->entitlement = self::ANNUAL_ENTITLEMENT_DAYS;
            $annualRecap = (array) data_get($balance->meta_json, 'annual_recap', []);
            if (empty($annualRecap['manual_carry_forward'])) {
                $balance->carry_forward = $this->resolveAnnualCarryForward($user, $leaveType, $year);
            } else {
                $breakdown = $this->annualCarryForwardBreakdown($balance->toArray(), $year);
                $balance->carry_forward = array_sum($breakdown);
            }
        }

        $this->recalculateRemaining($balance);

        return $balance;
    }

    protected function resolveAnnualCarryForward(User $user = null, LeaveType $leaveType, $year)
    {
        if (!$user || $year <= 1) {
            return 0;
        }

        $previousYear = (int) $year - 1;
        $twoYearsAgo = (int) $year - 2;
        $previousBalance = LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $previousYear)
            ->first();

        if ($this->hasAnnualLeaveHistory($user, $leaveType, $previousYear)
            && $this->hasAnnualLeaveHistory($user, $leaveType, $twoYearsAgo)
            && $this->annualLeaveUnused($user, $leaveType, $previousYear)
            && $this->annualLeaveUnused($user, $leaveType, $twoYearsAgo)) {
            return self::ANNUAL_LONG_CARRY_FORWARD_MAX_DAYS;
        }

        if (!$previousBalance) {
            return $this->inferAnnualCarryForwardFromRequests($user, $leaveType, $previousYear);
        }

        return min(max((int) $previousBalance->remaining_balance, 0), self::ANNUAL_CARRY_FORWARD_MAX_DAYS);
    }

    protected function hasAnnualLeaveHistory(User $user, LeaveType $leaveType, $year)
    {
        if ($year <= 0) {
            return false;
        }

        if (LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->exists()) {
            return true;
        }

        return LeaveRequest::where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->whereYear('start_date', $year)
            ->whereNotIn('status', [
                LeaveRequest::STATUS_DRAFT,
                LeaveRequest::STATUS_REJECTED,
                LeaveRequest::STATUS_CHANGED,
                LeaveRequest::STATUS_DEFERRED,
                LeaveRequest::STATUS_CANCELLED,
            ])
            ->exists();
    }

    protected function annualLeaveUnused(User $user, LeaveType $leaveType, $year)
    {
        if ($year <= 0) {
            return false;
        }

        $balance = LeaveBalance::where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        if ($balance) {
            return (int) $balance->used_days === 0 && (int) $balance->reserved_days === 0;
        }

        return $this->annualLeaveUsedDaysFromRequests($user, $leaveType, $year) === 0;
    }

    protected function inferAnnualCarryForwardFromRequests(User $user, LeaveType $leaveType, $previousYear)
    {
        if (!$this->hasAnnualLeaveHistory($user, $leaveType, $previousYear)) {
            return 0;
        }

        $usedDays = $this->annualLeaveUsedDaysFromRequests($user, $leaveType, $previousYear);
        $previousRemaining = self::ANNUAL_ENTITLEMENT_DAYS - (int) $usedDays;

        return min(max($previousRemaining, 0), self::ANNUAL_CARRY_FORWARD_MAX_DAYS);
    }

    protected function annualLeaveUsedDaysFromRequests(User $user, LeaveType $leaveType, $year)
    {
        return (int) LeaveRequest::where('user_id', $user->id)
            ->where('leave_type_id', $leaveType->id)
            ->whereYear('start_date', $year)
            ->whereNotIn('status', [
                LeaveRequest::STATUS_DRAFT,
                LeaveRequest::STATUS_REJECTED,
                LeaveRequest::STATUS_CHANGED,
                LeaveRequest::STATUS_DEFERRED,
                LeaveRequest::STATUS_CANCELLED,
            ])
            ->get()
            ->sum(function (LeaveRequest $request) {
                return (int) ($request->approved_days ?: $request->requested_days ?: $request->workday_count);
            });
    }

    protected function recalculateRemaining(LeaveBalance $balance)
    {
        $balance->remaining_balance = (int) $balance->opening_balance
            + (int) $balance->entitlement
            + (int) $balance->carry_forward
            + (int) $balance->adjustment_plus
            - (int) $balance->adjustment_minus
            - (int) $balance->used_days
            - (int) $balance->reserved_days;

        $balance->save();

        return $balance;
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
