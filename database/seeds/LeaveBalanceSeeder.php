<?php

use Illuminate\Database\Seeder;
use App\LeaveBalance;
use App\LeaveType;
use App\User;
use Carbon\Carbon;

class LeaveBalanceSeeder extends Seeder
{
    public function run()
    {
        $leaveType = LeaveType::where('code', 'CT')->first();
        if (!$leaveType) {
            return;
        }

        $year = (int) date('Y');
        foreach (User::where('status_aktif_pegawai', true)->get() as $user) {
            $entitlement = 0;
            if ($user->tmt_pns && Carbon::parse($user->tmt_pns)->diffInYears(Carbon::create($year, 1, 1)) >= 1) {
                $entitlement = 12;
            }

            LeaveBalance::updateOrCreate(
                ['user_id' => $user->id, 'leave_type_id' => $leaveType->id, 'year' => $year],
                [
                    'opening_balance' => 0,
                    'entitlement' => $entitlement,
                    'carry_forward' => 0,
                    'adjustment_plus' => 0,
                    'adjustment_minus' => 0,
                    'used_days' => 0,
                    'reserved_days' => 0,
                    'remaining_balance' => $entitlement,
                ]
            );
        }
    }
}
