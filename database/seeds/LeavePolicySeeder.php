<?php

use Illuminate\Database\Seeder;
use App\LeavePolicy;
use App\LeaveType;

class LeavePolicySeeder extends Seeder
{
    public function run()
    {
        $map = ['CT' => ['carry_forward_enabled' => ['value' => true], 'carry_forward_max_days' => ['value' => 6]], 'CM' => ['melahirkan_max_child_default' => ['value' => 3]], 'CBS' => ['collective_leave_deducts_balance' => ['value' => true]]];
        foreach ($map as $code => $policies) {
            $leaveType = LeaveType::where('code', $code)->first();
            if (!$leaveType) { continue; }
            foreach ($policies as $key => $value) {
                LeavePolicy::updateOrCreate(['leave_type_id' => $leaveType->id, 'key' => $key], ['value_json' => $value, 'is_active' => true]);
            }
        }
    }
}
