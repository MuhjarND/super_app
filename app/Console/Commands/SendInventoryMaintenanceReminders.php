<?php

namespace App\Console\Commands;

use App\Services\InventoryMaintenanceReminderService;
use Illuminate\Console\Command;

class SendInventoryMaintenanceReminders extends Command
{
    protected $signature = 'inventory:send-maintenance-reminders';

    protected $description = 'Kirim notifikasi WhatsApp untuk jadwal perawatan alat dan mesin yang jatuh tempo';

    public function handle(InventoryMaintenanceReminderService $service)
    {
        $result = $service->processDueSchedules();

        $this->info(sprintf(
            'Jadwal: %d, penerima: %d, terkirim: %d, gagal: %d.',
            $result['schedules'],
            $result['recipients'],
            $result['sent'],
            $result['failed']
        ));

        return $result['failed'] > 0 ? 1 : 0;
    }
}
