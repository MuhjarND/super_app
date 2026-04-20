<?php

namespace App\Http\Controllers;

use App\AgendaPimpinan;
use App\Disposisi;
use App\InventoryMaintenanceTransaction;
use App\LeaveApproval;
use App\LeaveRequest;
use App\Rapat;
use App\RapatApproval;
use App\RapatNotulensiApproval;
use App\SuratKeluarApproval;
use App\Services\IntegratedCalendarService;
use App\Services\UnifiedActionCenterService;
use App\ZiActivity;
use App\ZiActivityApproval;
use App\ZiPeriod;
use Illuminate\Support\Facades\Schema;

class LeadershipDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();
        abort_unless($user->canAccessLeadershipDashboard(), 403);

        $actionCenter = app(UnifiedActionCenterService::class)->build($user, ['tab' => 'all']);
        $calendar = app(IntegratedCalendarService::class)->build($user, [
            'start' => now('Asia/Jayapura')->toDateString(),
            'end' => now('Asia/Jayapura')->copy()->addDays(7)->toDateString(),
            'scope' => 'all',
        ]);

        $today = now('Asia/Jayapura')->toDateString();
        $activePeriod = Schema::hasTable('zi_periods') ? ZiPeriod::where('is_active', true)->latest('year')->first() : null;

        $approvalSummary = [
            'rapat' => Schema::hasTable('rapat_approvals') ? RapatApproval::where('status', 'pending')->count() : 0,
            'notulensi' => Schema::hasTable('rapat_notulensi_approvals') ? RapatNotulensiApproval::where('status', 'pending')->count() : 0,
            'cuti' => Schema::hasTable('leave_approvals') ? LeaveApproval::where('status', 'pending')->count() : 0,
            'surat' => Schema::hasTable('surat_keluar_approvals') ? SuratKeluarApproval::where('status', 'pending')->count() : 0,
            'zi' => Schema::hasTable('zi_activity_approvals') ? ZiActivityApproval::where('status', 'pending')->count() : 0,
        ];

        $pendingDisposisi = Disposisi::with(['suratMasuk', 'dariUser', 'kepadaUser'])
            ->where('status', 'pending')
            ->orderByRaw('CASE WHEN priority_level = "high" THEN 1 WHEN priority_level = "normal" THEN 2 ELSE 3 END')
            ->orderBy('target_tindak_lanjut_at')
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        $upcomingEvents = collect($calendar['events'] ?? [])
            ->sortBy('start')
            ->take(8)
            ->values();

        $ziStats = [
            'period_name' => optional($activePeriod)->name ?: 'Belum ada periode aktif',
            'overdue_count' => Schema::hasTable('zi_activities') ? ZiActivity::whereDate('target_end_date', '<', today())->whereNotIn('status', ['sudah_terlaksana', 'selesai'])->count() : 0,
            'approval_pending' => Schema::hasTable('zi_activity_approvals') ? ZiActivityApproval::where('status', 'pending')->count() : 0,
        ];

        $inventoryStats = [
            'draft_count' => Schema::hasTable('inventory_maintenance_transactions') ? InventoryMaintenanceTransaction::where('status', 'draft')->count() : 0,
            'attachment_pending_count' => Schema::hasTable('inventory_maintenance_transactions') ? InventoryMaintenanceTransaction::withCount('attachments')->where('status', 'completed')->get()->where('attachments_count', 0)->count() : 0,
        ];

        return view('dashboard.leadership', [
            'summary' => [
                'pending_approvals' => array_sum($approvalSummary),
                'pending_dispositions' => Disposisi::where('status', 'pending')->count(),
                'today_agenda' => Rapat::whereDate('tanggal', $today)->count() + AgendaPimpinan::whereDate('tanggal_kegiatan', $today)->count(),
                'active_leave' => Schema::hasTable('leave_requests') ? LeaveRequest::whereIn('status', [LeaveRequest::STATUS_APPROVED, LeaveRequest::STATUS_COMPLETED])->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->count() : 0,
                'urgent_actions' => collect($actionCenter['items'] ?? [])->where('priority_key', 'high')->count(),
                'overdue_actions' => collect($actionCenter['items'] ?? [])->where('is_overdue', true)->count(),
            ],
            'approvalSummary' => $approvalSummary,
            'actionItems' => collect($actionCenter['items'] ?? [])->take(10),
            'upcomingEvents' => $upcomingEvents,
            'pendingDisposisi' => $pendingDisposisi,
            'ziStats' => $ziStats,
            'inventoryStats' => $inventoryStats,
        ]);
    }
}
