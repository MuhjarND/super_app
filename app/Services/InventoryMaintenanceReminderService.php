<?php

namespace App\Services;

use App\InventoryMaintenanceSchedule;
use App\InventoryMaintenanceScheduleNotification;
use App\User;
use Illuminate\Support\Facades\Log;

class InventoryMaintenanceReminderService
{
    const RECIPIENT_JABATAN_CODES = ['KASUBAG_TURT', 'KASUBAG_LAPKEU'];

    protected $whatsAppService;

    public function __construct(WhatsAppNotificationService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function processDueSchedules()
    {
        $result = [
            'schedules' => 0,
            'recipients' => 0,
            'sent' => 0,
            'failed' => 0,
        ];

        InventoryMaintenanceSchedule::with(['item', 'detail'])
            ->where('status', 'scheduled')
            ->whereNull('notification_completed_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('id')
            ->chunkById(50, function ($schedules) use (&$result) {
                foreach ($schedules as $schedule) {
                    $scheduleResult = $this->notifySchedule($schedule);
                    $result['schedules']++;
                    $result['recipients'] += $scheduleResult['recipients'];
                    $result['sent'] += $scheduleResult['sent'];
                    $result['failed'] += $scheduleResult['failed'];
                }
            });

        return $result;
    }

    public function notifySchedule(InventoryMaintenanceSchedule $schedule)
    {
        $recipients = $this->recipients();
        $result = ['recipients' => $recipients->count(), 'sent' => 0, 'failed' => 0];

        if ($recipients->isEmpty()) {
            Log::warning('[Maintenance Schedule] Tidak ada penerima aktif untuk jadwal #' . $schedule->id);
            return $result;
        }

        foreach ($recipients as $recipient) {
            $notification = InventoryMaintenanceScheduleNotification::firstOrCreate(
                [
                    'inventory_maintenance_schedule_id' => $schedule->id,
                    'user_id' => $recipient->id,
                ],
                ['status' => 'pending']
            );

            if ($notification->status === 'sent') {
                continue;
            }

            if ($notification->status === 'failed'
                && $notification->last_attempt_at
                && $notification->last_attempt_at->greaterThan(now()->subHour())) {
                continue;
            }

            $notification->update([
                'attempts' => $notification->attempts + 1,
                'last_attempt_at' => now(),
                'last_error' => null,
            ]);

            try {
                $sent = $this->whatsAppService->notifyInventoryMaintenanceDue($schedule, $recipient);
                $notification->update([
                    'status' => $sent ? 'sent' : 'failed',
                    'sent_at' => $sent ? now() : null,
                    'last_error' => $sent ? null : 'Pengiriman WhatsApp tidak berhasil atau nomor tujuan tidak tersedia.',
                ]);

                $sent ? $result['sent']++ : $result['failed']++;
            } catch (\Throwable $e) {
                $notification->update([
                    'status' => 'failed',
                    'last_error' => $e->getMessage(),
                ]);
                $result['failed']++;
                Log::error('[Maintenance Schedule] ' . $e->getMessage(), ['schedule_id' => $schedule->id]);
            }
        }

        $recipientIds = $recipients->pluck('id');
        $allSent = InventoryMaintenanceScheduleNotification::where('inventory_maintenance_schedule_id', $schedule->id)
            ->whereIn('user_id', $recipientIds)
            ->where('status', 'sent')
            ->count() === $recipientIds->count();

        if ($allSent && !$schedule->notification_completed_at) {
            $schedule->update(['notification_completed_at' => now()]);
        }

        return $result;
    }

    public function recipients()
    {
        $codes = self::RECIPIENT_JABATAN_CODES;

        return User::active()
            ->where(function ($query) use ($codes) {
                $query->whereHas('jabatan', function ($jabatanQuery) use ($codes) {
                    $jabatanQuery->whereIn('kode', $codes);
                })->orWhereHas('activeJabatanDelegations.jabatan', function ($jabatanQuery) use ($codes) {
                    $jabatanQuery->whereIn('kode', $codes);
                });
            })
            ->with(['jabatan', 'roles', 'activeJabatanDelegations.jabatan'])
            ->get()
            ->unique('id')
            ->values();
    }
}
