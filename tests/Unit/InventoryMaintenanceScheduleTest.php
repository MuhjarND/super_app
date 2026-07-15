<?php

namespace Tests\Unit;

use App\InventoryItem;
use App\InventoryItemDetail;
use App\InventoryMaintenanceSchedule;
use App\Services\InventoryMaintenanceReminderService;
use Carbon\Carbon;
use Tests\TestCase;

class InventoryMaintenanceScheduleTest extends TestCase
{
    public function test_schedule_exposes_monitoring_labels()
    {
        $item = new InventoryItem(['code' => '3.01', 'name' => 'AC Split']);
        $detail = new InventoryItemDetail(['sub_code' => '3.01.1', 'name' => 'AC Ruang Sidang']);

        $schedule = new InventoryMaintenanceSchedule([
            'status' => 'scheduled',
            'scheduled_at' => Carbon::now()->subMinute(),
        ]);
        $schedule->setRelation('item', $item);
        $schedule->setRelation('detail', $detail);

        $this->assertSame('Terjadwal', $schedule->status_label);
        $this->assertSame('danger', $schedule->status_color);
        $this->assertSame('3.01.1 - AC Ruang Sidang', $schedule->asset_label);
    }

    public function test_reminder_targets_both_required_positions()
    {
        $this->assertSame(
            ['KASUBAG_TURT', 'KASUBAG_LAPKEU'],
            InventoryMaintenanceReminderService::RECIPIENT_JABATAN_CODES
        );
    }
}
