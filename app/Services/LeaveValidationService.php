<?php

namespace App\Services;

use App\LeaveHoliday;
use App\LeavePolicy;
use App\LeaveRequest;
use App\LeaveType;
use App\User;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Optional;
use Illuminate\Validation\ValidationException;

class LeaveValidationService
{
    protected $balanceService;

    public function __construct(LeaveBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function validateForSubmit(LeaveRequest $leaveRequest)
    {
        $user = $leaveRequest->user;
        $leaveType = $leaveRequest->leaveType;
        if (!$user || !$leaveType) {
            throw ValidationException::withMessages(['leave_type_id' => 'Data pegawai atau jenis cuti belum lengkap.']);
        }

        $workdayCount = $this->countWorkingDays($leaveRequest->start_date, $leaveRequest->end_date);
        $leaveRequest->workday_count = $workdayCount;
        $leaveRequest->requested_days = $workdayCount;

        $this->validateServiceYears($leaveRequest, $user, $leaveType, $leaveRequest->start_date);
        $this->validateBalance($user, $leaveType, $workdayCount, $leaveRequest->start_date);
        $childNumberContext = !is_null(optional($user)->jumlah_anak)
            ? ((int) $user->jumlah_anak + 1)
            : null;

        $this->validateChildCount($leaveType, $childNumberContext);
        $this->validateRequiredDocuments($leaveRequest, $leaveType);
        $this->validateDoctorLetter($leaveRequest, $leaveType, $workdayCount);
        $this->validateDateOverlap($leaveRequest);
        $this->validateSpecialLimits($leaveRequest, $leaveType, $workdayCount);
        $this->validateAbroadRequest($leaveRequest, $leaveType);
        $this->validateAnnualAndLargeLeaveInteraction($leaveRequest, $leaveType, $workdayCount);
    }

    public function countWorkingDays($startDate, $endDate)
    {
        $start = $this->normalizeDate($startDate);
        $end = $this->normalizeDate($endDate);

        if (!$start || !$end) {
            throw ValidationException::withMessages(['start_date' => 'Tanggal mulai dan selesai cuti wajib diisi.']);
        }

        $start = $start->startOfDay();
        $end = $end->startOfDay();
        $days = 0;
        while ($start->lte($end)) {
            if (!$start->isWeekend() && !$this->isHoliday($start)) {
                $days++;
            }
            $start->addDay();
        }
        return $days;
    }

    protected function validateServiceYears(LeaveRequest $leaveRequest, User $user, LeaveType $leaveType, $startDate)
    {
        $yearsRequired = (int) ($leaveType->service_years_required ?: 0);
        if ($yearsRequired < 1 || empty($user->tmt_pns)) {
            return;
        }

        if ($leaveType->code === LeaveType::CODE_BESAR && $this->isLargeLeaveServiceException($leaveRequest)) {
            return;
        }

        $tmtPns = $this->normalizeDate($user->tmt_pns);
        $start = $this->normalizeDate($startDate);
        if (!$tmtPns || !$start) {
            return;
        }

        if ($tmtPns->diffInYears($start) < $yearsRequired) {
            throw ValidationException::withMessages(['start_date' => 'Masa kerja belum memenuhi syarat untuk jenis cuti ini.']);
        }
    }

    protected function isLargeLeaveServiceException(LeaveRequest $leaveRequest)
    {
        $purpose = mb_strtolower((string) $leaveRequest->purpose);

        return str_contains($purpose, 'haji pertama')
            || str_contains($purpose, 'anak keempat')
            || str_contains($purpose, 'anak ke-4')
            || str_contains($purpose, 'kelahiran anak keempat')
            || str_contains($purpose, 'kelahiran anak ke-4');
    }

    protected function validateBalance(User $user, LeaveType $leaveType, $requestedDays, $startDate)
    {
        if (!$leaveType->requires_balance) {
            return;
        }
        $start = $this->normalizeDate($startDate);
        if (!$start) {
            throw ValidationException::withMessages(['start_date' => 'Tanggal mulai cuti wajib diisi.']);
        }

        $balance = $this->balanceService->getBalanceSnapshot($user, $leaveType, $start->year);
        if (($balance['remaining_balance'] ?? 0) < $requestedDays) {
            throw ValidationException::withMessages(['start_date' => 'Saldo cuti tidak mencukupi.']);
        }
    }

    protected function validateChildCount(LeaveType $leaveType, $childNumberContext)
    {
        if ($leaveType->code !== LeaveType::CODE_MELAHIRKAN || !$childNumberContext) {
            return;
        }
        $limit = (int) $this->getPolicyValue($leaveType, 'melahirkan_max_child_default', 3);
        if ($childNumberContext > $limit) {
            throw ValidationException::withMessages(['leave_type_id' => 'Anak keempat dan seterusnya mengikuti kebijakan khusus instansi.']);
        }
    }

    protected function validateRequiredDocuments(LeaveRequest $leaveRequest, LeaveType $leaveType)
    {
        if (!$leaveType->requires_document) {
            return;
        }

        if (!$leaveRequest->documents()->exists()) {
            throw ValidationException::withMessages(['documents' => 'Jenis cuti ini wajib melampirkan dokumen pendukung.']);
        }
    }

    protected function validateDoctorLetter(LeaveRequest $leaveRequest, LeaveType $leaveType, $requestedDays)
    {
        if ($leaveType->code === LeaveType::CODE_SAKIT && $requestedDays > 1) {
            $hasDoctorLetter = $leaveRequest->documents()->where('document_type', 'surat_dokter')->exists();
            if (!$hasDoctorLetter) {
                throw ValidationException::withMessages(['documents' => 'Cuti sakit lebih dari satu hari wajib melampirkan surat dokter.']);
            }
        }
    }

    protected function validateDateOverlap(LeaveRequest $leaveRequest)
    {
        $exists = LeaveRequest::where('user_id', $leaveRequest->user_id)
            ->where('id', '!=', $leaveRequest->id)
            ->whereIn('status', [LeaveRequest::STATUS_SUBMITTED, LeaveRequest::STATUS_UNDER_REVIEW, LeaveRequest::STATUS_VERIFIED, LeaveRequest::STATUS_APPROVED, LeaveRequest::STATUS_COMPLETED])
            ->where(function ($query) use ($leaveRequest) {
                $query->whereBetween('start_date', [$leaveRequest->start_date, $leaveRequest->end_date])
                    ->orWhereBetween('end_date', [$leaveRequest->start_date, $leaveRequest->end_date])
                    ->orWhere(function ($nested) use ($leaveRequest) {
                        $nested->where('start_date', '<=', $leaveRequest->start_date)->where('end_date', '>=', $leaveRequest->end_date);
                    });
            })->exists();

        if ($exists) {
            throw ValidationException::withMessages(['start_date' => 'Tanggal cuti bentrok dengan pengajuan lain.']);
        }
    }

    protected function validateSpecialLimits(LeaveRequest $leaveRequest, LeaveType $leaveType, $requestedDays)
    {
        if ($leaveType->code === LeaveType::CODE_TAHUNAN) {
            return;
        }

        $this->validateMaxMonths($leaveRequest, $leaveType);

        if (!empty($leaveType->max_days) && $requestedDays > (int) $leaveType->max_days) {
            throw ValidationException::withMessages(['end_date' => 'Jumlah hari melebihi batas maksimum jenis cuti.']);
        }
    }

    protected function validateMaxMonths(LeaveRequest $leaveRequest, LeaveType $leaveType)
    {
        if (empty($leaveType->max_months)) {
            return;
        }

        $start = $this->normalizeDate($leaveRequest->start_date);
        $end = $this->normalizeDate($leaveRequest->end_date);
        if (!$start || !$end) {
            return;
        }

        $latestEnd = $start->copy()->startOfDay()->addMonthsNoOverflow((int) $leaveType->max_months)->subDay();
        if ($end->startOfDay()->gt($latestEnd)) {
            throw ValidationException::withMessages(['end_date' => 'Lama cuti melebihi batas ' . (int) $leaveType->max_months . ' bulan kalender.']);
        }
    }

    protected function validateAbroadRequest(LeaveRequest $leaveRequest, LeaveType $leaveType)
    {
        if (!$leaveRequest->is_abroad) {
            return;
        }

        if (!in_array($leaveType->code, [
            LeaveType::CODE_TAHUNAN,
            LeaveType::CODE_BESAR,
            LeaveType::CODE_SAKIT,
            LeaveType::CODE_MELAHIRKAN,
            LeaveType::CODE_ALASAN_PENTING,
        ], true)) {
            return;
        }

        $start = $this->normalizeDate($leaveRequest->start_date);
        if (!$start) {
            return;
        }

        $minimumStart = Carbon::now('Asia/Jayapura')->startOfDay()->addMonthNoOverflow();
        if ($start->startOfDay()->lt($minimumStart)) {
            throw ValidationException::withMessages(['start_date' => 'Cuti yang dijalankan di luar negeri wajib diajukan paling lambat 1 bulan sebelum pelaksanaan.']);
        }
    }

    protected function validateAnnualAndLargeLeaveInteraction(LeaveRequest $leaveRequest, LeaveType $leaveType, $requestedDays)
    {
        if (!in_array($leaveType->code, [LeaveType::CODE_TAHUNAN, LeaveType::CODE_BESAR], true)) {
            return;
        }

        $start = $this->normalizeDate($leaveRequest->start_date);
        if (!$start) {
            return;
        }

        $year = $start->year;
        $annualType = LeaveType::where('code', LeaveType::CODE_TAHUNAN)->first();
        $largeType = LeaveType::where('code', LeaveType::CODE_BESAR)->first();
        if (!$annualType || !$largeType) {
            return;
        }

        if ($leaveType->code === LeaveType::CODE_TAHUNAN && $this->hasActiveLeaveOfType($leaveRequest, $largeType->id, $year)) {
            throw ValidationException::withMessages(['leave_type_id' => 'Cuti tahunan tidak dapat digunakan pada tahun yang sama ketika cuti besar sedang/akan digunakan.']);
        }

        if ($leaveType->code === LeaveType::CODE_BESAR) {
            $annualUsedDays = $this->usedDaysForLeaveType($leaveRequest, $annualType->id, $year);
            $latestEnd = $start->copy()->startOfDay()->addMonthsNoOverflow((int) ($leaveType->max_months ?: 3))->subDay();
            $largeLeaveWorkdayLimit = $this->countWorkingDays($leaveRequest->start_date, $latestEnd);
            if ($annualUsedDays > 0 && ($requestedDays + $annualUsedDays) > $largeLeaveWorkdayLimit) {
                throw ValidationException::withMessages(['end_date' => 'Cuti besar wajib mempertimbangkan cuti tahunan yang sudah digunakan pada tahun berjalan.']);
            }
        }
    }

    protected function hasActiveLeaveOfType(LeaveRequest $leaveRequest, $leaveTypeId, $year)
    {
        return LeaveRequest::where('user_id', $leaveRequest->user_id)
            ->where('id', '!=', $leaveRequest->id)
            ->where('leave_type_id', $leaveTypeId)
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

    protected function usedDaysForLeaveType(LeaveRequest $leaveRequest, $leaveTypeId, $year)
    {
        return (int) LeaveRequest::where('user_id', $leaveRequest->user_id)
            ->where('id', '!=', $leaveRequest->id)
            ->where('leave_type_id', $leaveTypeId)
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

    protected function isHoliday(Carbon $date)
    {
        return class_exists(LeaveHoliday::class) && LeaveHoliday::whereDate('holiday_date', $date->toDateString())->where('is_active', true)->exists();
    }

    protected function getPolicyValue(LeaveType $leaveType, $key, $default = null)
    {
        $policy = LeavePolicy::where('leave_type_id', $leaveType->id)->where('key', $key)->where('is_active', true)->latest('id')->first();
        return $policy ? data_get($policy->value_json, 'value', $default) : $default;
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
