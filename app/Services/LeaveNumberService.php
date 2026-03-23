<?php

namespace App\Services;

use App\LeaveNumberSequence;
use Illuminate\Support\Facades\DB;

class LeaveNumberService
{
    public function next($sequenceType, $year = null, $prefix = null)
    {
        $year = $year ?: (int) date('Y');
        return DB::transaction(function () use ($sequenceType, $year, $prefix) {
            $sequence = LeaveNumberSequence::firstOrCreate(
                ['year' => $year, 'sequence_type' => $sequenceType],
                ['prefix' => $prefix ?: strtoupper($sequenceType), 'last_number' => 0]
            );

            if ($prefix && $sequence->prefix !== $prefix) {
                $sequence->prefix = $prefix;
            }

            $sequence->last_number = (int) $sequence->last_number + 1;
            $sequence->save();

            return [
                'number' => $sequence->last_number,
                'formatted' => sprintf('%03d/%s/%s', $sequence->last_number, $sequence->prefix ?: strtoupper($sequenceType), $year),
            ];
        });
    }
}
