<?php

namespace App\Services;

use App\LeaveHoliday;
use App\LeaveType;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class LeaveDateService
{
    public function effectiveDates($startDate, $endDate, LeaveType $leaveType = null)
    {
        $start = $this->normalizeDate($startDate);
        $end = $this->normalizeDate($endDate);

        if (!$start || !$end || $end->lt($start)) {
            return collect();
        }

        $usesCalendarDays = $this->usesCalendarDays($leaveType);
        $excludedDates = $this->excludedHolidayDates($start, $end, $leaveType, $usesCalendarDays);
        $dates = collect();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if (!$usesCalendarDays && $date->isWeekend()) {
                continue;
            }

            if (isset($excludedDates[$date->toDateString()])) {
                continue;
            }

            $dates->push($date->copy());
        }

        return $dates;
    }

    public function countEffectiveDates($startDate, $endDate, LeaveType $leaveType = null)
    {
        return $this->effectiveDates($startDate, $endDate, $leaveType)->count();
    }

    public function formatEffectiveDates($startDate, $endDate, LeaveType $leaveType = null)
    {
        $dates = $this->effectiveDates($startDate, $endDate, $leaveType);

        if ($dates->isEmpty()) {
            return '-';
        }

        $ranges = $this->contiguousRanges($dates);
        $parts = [];
        $monthChunk = null;

        foreach ($ranges as $range) {
            $rangeStart = $range['start'];
            $rangeEnd = $range['end'];
            $sameMonth = $rangeStart->format('Y-m') === $rangeEnd->format('Y-m');

            if (!$sameMonth) {
                $this->flushMonthChunk($monthChunk, $parts);
                $monthChunk = null;
                $parts[] = $rangeStart->translatedFormat('j F Y') . '-' . $rangeEnd->translatedFormat('j F Y');
                continue;
            }

            $monthKey = $rangeStart->format('Y-m');
            if ($monthChunk && $monthChunk['key'] !== $monthKey) {
                $this->flushMonthChunk($monthChunk, $parts);
                $monthChunk = null;
            }

            if (!$monthChunk) {
                $monthChunk = [
                    'key' => $monthKey,
                    'month_year' => $rangeStart->translatedFormat('F Y'),
                    'days' => [],
                ];
            }

            $monthChunk['days'][] = $rangeStart->isSameDay($rangeEnd)
                ? $rangeStart->format('j')
                : $rangeStart->format('j') . '-' . $rangeEnd->format('j');
        }

        $this->flushMonthChunk($monthChunk, $parts);

        return implode('; ', $parts);
    }

    public function usesCalendarDays(LeaveType $leaveType = null)
    {
        return $leaveType && in_array($leaveType->code, [
            LeaveType::CODE_SAKIT,
            LeaveType::CODE_ALASAN_PENTING,
        ], true);
    }

    protected function excludedHolidayDates(Carbon $start, Carbon $end, LeaveType $leaveType = null, $collectiveOnly = false)
    {
        $container = Container::getInstance();
        if (!$container || !$container->bound('db') || !class_exists(LeaveHoliday::class) || !Schema::hasTable('leave_holidays')) {
            return [];
        }

        $query = LeaveHoliday::query()
            ->where('is_active', true)
            ->whereBetween('holiday_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($query) use ($leaveType) {
                // Cuti bersama berlaku untuk seluruh jenis pengajuan. Kolom
                // leave_type_id pada data lama kadang diisi dengan jenis CBS,
                // sehingga tidak boleh membuat tanggal tersebut hanya berlaku
                // untuk satu jenis cuti.
                $query->whereNull('leave_type_id')
                    ->orWhere('is_collective_leave', true)
                    ->orWhere('category', 'cuti_bersama');

                if ($leaveType && $leaveType->getKey()) {
                    $query->orWhere('leave_type_id', $leaveType->getKey());
                }
            });

        // Cuti sakit dan cuti karena alasan penting tetap memakai hari kalender,
        // tetapi cuti bersama tidak boleh dianggap sebagai hari cuti pegawai.
        if ($collectiveOnly) {
            $query->where(function ($query) {
                $query->where('is_collective_leave', true)
                    ->orWhere('category', 'cuti_bersama');
            });
        }

        return $query->pluck('holiday_date')
            ->map(function ($date) {
                return Carbon::parse($date)->toDateString();
            })
            ->flip()
            ->all();
    }

    protected function contiguousRanges(Collection $dates)
    {
        $ranges = [];

        foreach ($dates as $date) {
            $lastIndex = count($ranges) - 1;
            if ($lastIndex >= 0 && $ranges[$lastIndex]['end']->copy()->addDay()->isSameDay($date)) {
                $ranges[$lastIndex]['end'] = $date->copy();
                continue;
            }

            $ranges[] = ['start' => $date->copy(), 'end' => $date->copy()];
        }

        return $ranges;
    }

    protected function flushMonthChunk($monthChunk, array &$parts)
    {
        if (!$monthChunk) {
            return;
        }

        $parts[] = implode(', ', $monthChunk['days']) . ' ' . $monthChunk['month_year'];
    }

    protected function normalizeDate($value)
    {
        if ($value instanceof Carbon) {
            return $value->copy()->startOfDay();
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->startOfDay();
    }
}
