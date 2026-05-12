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

        $this->validateServiceYears($user, $leaveType, $leaveRequest->start_date);
        $this->validateBalance($user, $leaveType, $workdayCount, $leaveRequest->start_date);
        $childNumberContext = !is_null(optional($user)->jumlah_anak)
            ? ((int) $user->jumlah_anak + 1)
            : null;

        $this->validateChildCount($leaveType, $childNumberContext);
        $this->validateRequiredDocuments($leaveRequest, $leaveType);
        $this->validateDoctorLetter($leaveRequest, $leaveType, $workdayCount);
        $this->validateDateOverlap($leaveRequest);
        $this->validateSpecialLimits($leaveType, $workdayCount);
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

    protected function validateServiceYears(User $user, LeaveType $leaveType, $startDate)
    {
        $yearsRequired = (int) ($leaveType->service_years_required ?: 0);
        if ($yearsRequired < 1 || empty($user->tmt_pns)) {
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

    protected function validateSpecialLimits(LeaveType $leaveType, $requestedDays)
    {
        if (!empty($leaveType->max_days) && $requestedDays > (int) $leaveType->max_days) {
            throw ValidationException::withMessages(['end_date' => 'Jumlah hari melebihi batas maksimum jenis cuti.']);
        }
        if ($leaveType->code === LeaveType::CODE_BESAR && $requestedDays > 90) {
            throw ValidationException::withMessages(['end_date' => 'Cuti besar maksimal tiga bulan.']);
        }
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
