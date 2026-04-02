<?php

namespace App\Http\Controllers;

use App\AgendaPimpinan;
use App\Disposisi;
use App\InventoryItem;
use App\InventoryItemDetail;
use App\InventoryMaintenanceTransaction;
use App\InventoryRoom;
use App\LeaveApproval;
use App\LeaveRequest;
use App\Rapat;
use App\RapatApproval;
use App\RapatNotulensiApproval;
use App\RapatNotulensiTindakLanjut;
use App\SuratKeluar;
use App\SuratMasuk;
use App\ZiActivity;
use App\ZiArea;
use App\ZiEvidence;
use App\ZiIndicator;
use App\ZiPeriod;
use App\Services\IntegratedCalendarService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = auth()->user();

        $persuratan = $this->buildPersuratanSection($user);
        $meeting = $this->buildMeetingSection($user);
        $leave = $this->buildLeaveSection($user);
        $progressZi = $this->buildProgressZiSection($user);
        $inventory = $this->buildInventorySection($user);
        $calendarOverview = $this->buildCalendarOverviewSection($user);

        $actionItems = collect()
            ->merge($persuratan['actions'])
            ->merge($meeting['actions'])
            ->merge($leave['actions'])
            ->merge($progressZi['actions'])
            ->merge($inventory['actions'])
            ->sortByDesc('sort_at')
            ->take(10)
            ->values();

        return view('dashboard', [
            'persuratan' => $persuratan,
            'meeting' => $meeting,
            'leave' => $leave,
            'progressZi' => $progressZi,
            'inventory' => $inventory,
            'calendarOverview' => $calendarOverview,
            'actionItems' => $actionItems,
            'dashboardSummary' => [
                'today_masuk' => $persuratan['today_masuk'],
                'today_keluar' => $persuratan['today_keluar'],
                'upcoming_meetings' => $meeting['upcoming_count'],
                'pending_leave_approvals' => $leave['pending_approvals'],
                'inventory_transactions' => $inventory['stats']['maintenance_count'] ?? 0,
                'action_count' => $actionItems->count(),
            ],
        ]);
    }

    protected function buildCalendarOverviewSection($user)
    {
        $service = app(IntegratedCalendarService::class);
        $monthStart = now('Asia/Jayapura')->startOfMonth();
        $monthEnd = now('Asia/Jayapura')->endOfMonth();
        $today = now('Asia/Jayapura')->toDateString();

        $calendar = $service->build($user, [
            'start' => $monthStart->toDateString(),
            'end' => $monthEnd->toDateString(),
            'scope' => 'all',
        ]);
        $todayMineCalendar = $service->build($user, [
            'start' => $today,
            'end' => $today,
            'scope' => 'mine',
        ]);

        $events = collect($calendar['events']);
        $counts = $calendar['meta']['counts'] ?? [];
        $conflicts = collect($calendar['meta']['conflicts'] ?? []);
        $upcoming = collect($calendar['meta']['upcoming'] ?? []);
        $todayEvents = collect($todayMineCalendar['events'])
            ->sortBy('start')
            ->take(5)
            ->map(function ($event) {
                return [
                    'title' => $event['title'],
                    'module' => $event['extendedProps']['module_label'] ?? '-',
                    'status' => $event['extendedProps']['status_label'] ?? '-',
                    'time' => $event['extendedProps']['time_label'] ?? '-',
                ];
            })
            ->values();

        return [
            'month_label' => $monthStart->translatedFormat('F Y'),
            'event_count' => $counts['all'] ?? 0,
            'meeting_count' => $counts['rapat'] ?? 0,
            'leave_count' => $counts['cuti'] ?? 0,
            'zi_count' => $counts['zi'] ?? 0,
            'conflict_count' => $conflicts->count(),
            'days_with_events' => $events
                ->map(function ($event) {
                    return Carbon::parse($event['start'], 'Asia/Jayapura')->toDateString();
                })
                ->unique()
                ->count(),
            'month_days' => $this->buildCalendarMonthGrid($events, $monthStart, $monthEnd),
            'weekday_labels' => ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
            'today_events' => $todayEvents,
            'today_event_count' => $todayMineCalendar['meta']['counts']['all'] ?? 0,
            'upcoming' => $upcoming->take(5)->values(),
            'conflicts' => $conflicts->take(4)->values(),
        ];
    }

    protected function buildCalendarMonthGrid(Collection $events, Carbon $monthStart, Carbon $monthEnd)
    {
        $eventMap = [];

        foreach ($events as $event) {
            foreach ($this->expandDashboardEventDates($event, $monthStart, $monthEnd) as $dateKey) {
                if (!isset($eventMap[$dateKey])) {
                    $eventMap[$dateKey] = [
                        'count' => 0,
                        'modules' => [],
                    ];
                }

                $eventMap[$dateKey]['count']++;
                $moduleKey = $event['module_key'] ?? null;
                if ($moduleKey && !in_array($moduleKey, $eventMap[$dateKey]['modules'], true)) {
                    $eventMap[$dateKey]['modules'][] = $moduleKey;
                }
            }
        }

        $gridStart = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);
        $weeks = collect();
        $currentWeek = [];
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $dateKey = $cursor->toDateString();
            $meta = $eventMap[$dateKey] ?? ['count' => 0, 'modules' => []];

            $currentWeek[] = [
                'date' => $dateKey,
                'day' => $cursor->day,
                'in_month' => $cursor->month === $monthStart->month,
                'is_today' => $cursor->isToday(),
                'event_count' => $meta['count'],
                'module_keys' => array_slice($meta['modules'], 0, 3),
            ];

            if (count($currentWeek) === 7) {
                $weeks->push($currentWeek);
                $currentWeek = [];
            }

            $cursor->addDay();
        }

        return $weeks;
    }

    protected function expandDashboardEventDates(array $event, Carbon $monthStart, Carbon $monthEnd)
    {
        $start = Carbon::parse($event['start'], 'Asia/Jayapura')->startOfDay();
        $end = Carbon::parse($event['end'], 'Asia/Jayapura');
        $last = !empty($event['allDay'])
            ? $end->copy()->subDay()->startOfDay()
            : $end->copy()->startOfDay();

        if ($last->lt($start)) {
            $last = $start->copy();
        }

        if ($start->lt($monthStart)) {
            $start = $monthStart->copy();
        }

        if ($last->gt($monthEnd)) {
            $last = $monthEnd->copy();
        }

        $dates = [];
        $cursor = $start->copy();

        while ($cursor->lte($last)) {
            $dates[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $dates;
    }

    protected function buildProgressZiSection($user)
    {
        $enabled = $user->canAccessProgressZiModule() && Schema::hasTable('zi_periods');
        if (!$enabled) {
            return [
                'enabled' => false,
                'stats' => [],
                'actions' => collect(),
            ];
        }

        $activePeriod = ZiPeriod::where('is_active', true)->latest('year')->first();
        $activities = ZiActivity::query()->with(['area', 'pic', 'indicators']);
        $indicators = ZiIndicator::query();
        $evidences = ZiEvidence::query();
        $areas = ZiArea::query();

        if ($activePeriod) {
            $activities->where('zi_period_id', $activePeriod->id);
            $indicators->whereHas('activity', function ($query) use ($activePeriod) {
                $query->where('zi_period_id', $activePeriod->id);
            });
            $evidences->whereHas('realization.activity', function ($query) use ($activePeriod) {
                $query->where('zi_period_id', $activePeriod->id);
            });
            $areas->whereHas('activities', function ($query) use ($activePeriod) {
                $query->where('zi_period_id', $activePeriod->id);
            });
        }

        if (!$user->canManageProgressZiMasterData()) {
            $activities->where(function ($query) use ($user) {
                $query->where('pic_user_id', $user->id)
                    ->orWhereHas('area', function ($areaQuery) use ($user) {
                        $areaQuery->where('pic_user_id', $user->id);
                    });
            });
            $indicators->whereHas('activity', function ($query) use ($user) {
                $query->where('pic_user_id', $user->id)
                    ->orWhereHas('area', function ($areaQuery) use ($user) {
                        $areaQuery->where('pic_user_id', $user->id);
                    });
            });
            $evidences->whereHas('realization.activity', function ($query) use ($user) {
                $query->where('pic_user_id', $user->id)
                    ->orWhereHas('area', function ($areaQuery) use ($user) {
                        $areaQuery->where('pic_user_id', $user->id);
                    });
            });
            $areas->where('pic_user_id', $user->id);
        }

        $activityCollection = $activities->get();

        $actions = $activityCollection->filter(function ($activity) {
            return $activity->target_end_date && $activity->target_end_date->lt(today()) && !in_array($activity->status, ['sudah_terlaksana', 'selesai'], true);
        })->sortBy('target_end_date')->take(4)->map(function ($activity) {
            return [
                'module' => 'Progress ZI',
                'title' => 'Kegiatan overdue',
                'subtitle' => $activity->name,
                'description' => optional($activity->area)->name . ' • target ' . optional($activity->target_end_date)->translatedFormat('d F Y'),
                'url' => route('progress-zi.activities.show', $activity),
                'icon' => 'fas fa-chart-line',
                'tone' => 'purple',
                'sort_at' => optional($activity->target_end_date)->timestamp ?: 0,
                'time' => optional($activity->target_end_date)->diffForHumans(),
            ];
        })->values();

        $score = $activityCollection->isEmpty()
            ? 0
            : round($activityCollection->avg(function ($activity) {
                return $activity->progress_score;
            }), 1);

        return [
            'enabled' => true,
            'stats' => [
                'area_count' => $areas->count(),
                'activity_count' => $activityCollection->count(),
                'indicator_count' => (clone $indicators)->count(),
                'evidence_count' => (clone $evidences)->count(),
                'overdue_count' => $actions->count(),
                'period_score' => $score,
                'period_name' => optional($activePeriod)->name ?: 'Belum ada periode aktif',
            ],
            'actions' => $actions,
        ];
    }

    protected function buildPersuratanSection($user)
    {
        $enabled = $user->canAccessPersuratanMenu();
        if (!$enabled) {
            return [
                'enabled' => false,
                'stats' => [],
                'recent' => collect(),
                'actions' => collect(),
                'today_masuk' => 0,
                'today_keluar' => 0,
            ];
        }

        $suratMasukVisible = SuratMasuk::visibleTo($user);
        $suratKeluarVisible = SuratKeluar::visibleTo($user);

        $pendingDisposisiQuery = Disposisi::with(['suratMasuk', 'dariUser'])
            ->where('kepada_user_id', $user->id)
            ->where('status', 'pending');

        $recent = collect()
            ->merge(
                (clone $suratMasukVisible)->with(['creator'])
                    ->latest()
                    ->take(4)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'type' => 'Surat Masuk',
                            'title' => $item->nomor_surat ?: '-',
                            'subtitle' => $item->perihal ?: '-',
                            'meta' => ($item->pengirim ?: '-') . ' • ' . optional($item->created_at)->diffForHumans(),
                            'url' => route('surat-masuk.show', $item),
                            'badge' => $item->status_badge,
                            'sort_at' => optional($item->created_at)->timestamp ?: 0,
                        ];
                    })
            )
            ->merge(
                (clone $suratKeluarVisible)->with(['creator'])
                    ->latest()
                    ->take(4)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'type' => 'Surat Keluar',
                            'title' => $item->nomor_surat_formatted ?: '-',
                            'subtitle' => $item->perihal ?: '-',
                            'meta' => (optional($item->creator)->name ?: '-') . ' • ' . optional($item->created_at)->diffForHumans(),
                            'url' => route('surat-keluar.index'),
                            'badge' => $item->status_badge,
                            'sort_at' => optional($item->created_at)->timestamp ?: 0,
                        ];
                    })
            )
            ->sortByDesc('sort_at')
            ->take(6)
            ->values();

        $actions = (clone $pendingDisposisiQuery)->latest()->take(5)->get()->map(function ($disposisi) {
            $suratMasuk = $disposisi->suratMasuk;

            return [
                'module' => 'Persuratan',
                'title' => 'Tindak lanjut disposisi',
                'subtitle' => $suratMasuk ? ($suratMasuk->nomor_surat ?: 'Surat masuk') : 'Surat masuk',
                'description' => $suratMasuk ? ($suratMasuk->perihal ?: 'Ada disposisi yang menunggu tindak lanjut.') : 'Ada disposisi yang menunggu tindak lanjut.',
                'url' => route('surat-masuk.index'),
                'icon' => 'fas fa-inbox',
                'tone' => 'amber',
                'sort_at' => optional($disposisi->created_at)->timestamp ?: 0,
                'time' => optional($disposisi->created_at)->diffForHumans(),
            ];
        });

        return [
            'enabled' => true,
            'stats' => [
                'total_masuk' => (clone $suratMasukVisible)->count(),
                'surat_baru' => (clone $suratMasukVisible)->where('status', 'baru')->count(),
                'disposisi_pending' => (clone $pendingDisposisiQuery)->count(),
                'total_keluar' => (clone $suratKeluarVisible)->count(),
                'keluar_draft' => (clone $suratKeluarVisible)->where('status', 'draft')->count(),
                'keluar_lengkap' => (clone $suratKeluarVisible)->where('status', 'lengkap')->count(),
            ],
            'today_masuk' => (clone $suratMasukVisible)->whereDate('created_at', today())->count(),
            'today_keluar' => (clone $suratKeluarVisible)->whereDate('created_at', today())->count(),
            'recent' => $recent,
            'actions' => $actions,
        ];
    }

    protected function buildMeetingSection($user)
    {
        $enabled = $user->canAccessMeetingModule();
        if (!$enabled) {
            return [
                'enabled' => false,
                'stats' => [],
                'recent' => collect(),
                'actions' => collect(),
                'upcoming' => collect(),
                'upcoming_count' => 0,
            ];
        }

        $visibleRapats = Rapat::visibleTo($user)->with(['creator', 'pesertas', 'approvals']);
        $visibleAgenda = $this->visibleAgendaQuery($user)->with(['creator', 'recipients']);

        $pendingRapatApprovalQuery = RapatApproval::with(['rapat'])
            ->where('status', 'pending');
        if (!$user->isMeetingAdmin() && !$user->isSuperAdmin()) {
            $pendingRapatApprovalQuery->where('approver_id', $user->id);
        }

        $pendingNotulensiApprovalQuery = RapatNotulensiApproval::with(['notulensi.rapat'])
            ->where('status', 'pending');
        if (!$user->isMeetingAdmin() && !$user->isSuperAdmin()) {
            $pendingNotulensiApprovalQuery->where('approver_id', $user->id);
        }

        $pendingFollowUpQuery = $this->pendingMeetingFollowUpQuery($user);

        $upcomingRapats = (clone $visibleRapats)
            ->whereDate('tanggal', '>=', today())
            ->orderBy('tanggal')
            ->orderBy('waktu_mulai')
            ->take(4)
            ->get()
            ->map(function ($rapat) {
                return [
                    'type' => 'Rapat',
                    'title' => $rapat->judul,
                    'meta' => optional($rapat->tanggal)->translatedFormat('d F Y') . ' • ' . $rapat->waktu_mulai_formatted . ' WIT',
                    'submeta' => $rapat->tempat ?: '-',
                    'url' => route('rapat.index'),
                    'badge' => $rapat->status_badge,
                    'sort_at' => optional($rapat->tanggal)->timestamp ?: 0,
                ];
            });

        $upcomingAgenda = (clone $visibleAgenda)
            ->whereDate('tanggal_kegiatan', '>=', today())
            ->orderBy('tanggal_kegiatan')
            ->orderBy('waktu')
            ->take(4)
            ->get()
            ->map(function ($agenda) {
                return [
                    'type' => 'Agenda',
                    'title' => $agenda->judul_agenda,
                    'meta' => optional($agenda->tanggal_kegiatan)->translatedFormat('d F Y') . ' • ' . $agenda->waktu_formatted . ' WIT',
                    'submeta' => $agenda->tempat ?: '-',
                    'url' => route('rapat.agenda.index'),
                    'badge' => '<span class="badge badge-info app-status-badge">Agenda</span>',
                    'sort_at' => optional($agenda->tanggal_kegiatan)->timestamp ?: 0,
                ];
            });

        $recent = collect()
            ->merge(
                (clone $visibleRapats)->latest()->take(4)->get()->map(function ($rapat) {
                    return [
                        'type' => 'Rapat',
                        'title' => $rapat->judul,
                        'subtitle' => $rapat->nomor_undangan ?: '-',
                        'meta' => optional($rapat->tanggal)->translatedFormat('d F Y') . ' • ' . $rapat->pesertas->count() . ' peserta',
                        'url' => route('rapat.index'),
                        'badge' => $rapat->status_badge,
                        'sort_at' => optional($rapat->created_at)->timestamp ?: 0,
                    ];
                })
            )
            ->merge(
                (clone $visibleAgenda)->latest()->take(4)->get()->map(function ($agenda) {
                    return [
                        'type' => 'Agenda',
                        'title' => $agenda->judul_agenda,
                        'subtitle' => $agenda->nomor_naskah_dinas ?: 'Agenda pimpinan',
                        'meta' => optional($agenda->tanggal_kegiatan)->translatedFormat('d F Y') . ' • ' . $agenda->recipients->count() . ' penerima',
                        'url' => route('rapat.agenda.index'),
                        'badge' => '<span class="badge badge-info app-status-badge">Agenda</span>',
                        'sort_at' => optional($agenda->created_at)->timestamp ?: 0,
                    ];
                })
            )
            ->sortByDesc('sort_at')
            ->take(6)
            ->values();

        $actions = collect()
            ->merge(
                (clone $pendingRapatApprovalQuery)->latest()->take(4)->get()->map(function ($approval) {
                    return [
                        'module' => 'Rapat / Agenda',
                        'title' => $approval->stage_label . ' undangan rapat',
                        'subtitle' => optional($approval->rapat)->judul ?: 'Undangan rapat',
                        'description' => optional($approval->rapat)->nomor_undangan ?: 'Ada dokumen rapat yang menunggu approval.',
                        'url' => route('rapat.approval.show', $approval),
                        'icon' => 'fas fa-file-signature',
                        'tone' => 'blue',
                        'sort_at' => optional($approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
                        'time' => optional($approval->updated_at ?: $approval->created_at)->diffForHumans(),
                    ];
                })
            )
            ->merge(
                (clone $pendingNotulensiApprovalQuery)->latest()->take(4)->get()->map(function ($approval) {
                    return [
                        'module' => 'Rapat / Agenda',
                        'title' => 'Approval notulensi',
                        'subtitle' => optional(optional($approval->notulensi)->rapat)->judul ?: 'Notulensi',
                        'description' => optional($approval->notulensi)->judul ?: 'Ada notulensi yang menunggu approval.',
                        'url' => route('rapat.notulensi-approval.show', $approval),
                        'icon' => 'fas fa-file-contract',
                        'tone' => 'purple',
                        'sort_at' => optional($approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
                        'time' => optional($approval->updated_at ?: $approval->created_at)->diffForHumans(),
                    ];
                })
            )
            ->merge(
                (clone $pendingFollowUpQuery)->latest()->take(4)->get()->map(function ($followUp) {
                    return [
                        'module' => 'Rapat / Agenda',
                        'title' => 'Tindak lanjut notulen',
                        'subtitle' => optional(optional($followUp->notulensi)->rapat)->judul ?: 'Notulen',
                        'description' => $followUp->deskripsi_snapshot ?: 'Ada tindak lanjut rapat yang belum selesai.',
                        'url' => route('rapat.notulensi.follow-ups'),
                        'icon' => 'fas fa-tasks',
                        'tone' => 'red',
                        'sort_at' => optional($followUp->updated_at ?: $followUp->created_at)->timestamp ?: 0,
                        'time' => optional($followUp->updated_at ?: $followUp->created_at)->diffForHumans(),
                    ];
                })
            );

        $upcoming = $upcomingRapats
            ->merge($upcomingAgenda)
            ->sortBy('sort_at')
            ->take(6)
            ->values();

        return [
            'enabled' => true,
            'stats' => [
                'total_rapat' => (clone $visibleRapats)->count(),
                'total_agenda' => (clone $visibleAgenda)->count(),
                'pending_undangan' => (clone $pendingRapatApprovalQuery)->count(),
                'pending_notulensi' => (clone $pendingNotulensiApprovalQuery)->count(),
                'pending_tindak_lanjut' => (clone $pendingFollowUpQuery)->count(),
            ],
            'recent' => $recent,
            'actions' => $actions,
            'upcoming' => $upcoming,
            'upcoming_count' => $upcoming->count(),
        ];
    }

    protected function buildLeaveSection($user)
    {
        $enabled = $user->canAccessLeaveModule();
        if (!$enabled || !Schema::hasTable('leave_requests')) {
            return [
                'enabled' => false,
                'stats' => [],
                'recent' => collect(),
                'actions' => collect(),
                'pending_approvals' => 0,
            ];
        }

        $myLeaveQuery = LeaveRequest::with(['leaveType', 'user'])
            ->where('user_id', $user->id);

        $relevantLeaveQuery = LeaveRequest::with(['leaveType', 'user', 'approvals']);
        if (!$user->isSuperAdmin() && !$user->canApproveLeave()) {
            $relevantLeaveQuery->where('user_id', $user->id);
        } elseif (!$user->isSuperAdmin()) {
            $relevantLeaveQuery->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('approvals', function ($approvalQuery) use ($user) {
                        $approvalQuery->where('approver_id', $user->id);
                    });
            });
        }

        $pendingLeaveApprovalQuery = LeaveApproval::with(['leaveRequest.user', 'leaveRequest.leaveType'])
            ->where('status', 'pending');
        if (!$user->isSuperAdmin()) {
            $pendingLeaveApprovalQuery->where('approver_id', $user->id);
        }

        $recent = (clone $relevantLeaveQuery)
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($leaveRequest) {
                return [
                    'type' => 'Cuti',
                    'title' => $leaveRequest->display_number,
                    'subtitle' => optional($leaveRequest->leaveType)->name ?: 'Cuti',
                    'meta' => $leaveRequest->period_label . ' • ' . (optional($leaveRequest->user)->name ?: '-'),
                    'url' => route('cuti.show', $leaveRequest),
                    'badge' => $leaveRequest->status_badge,
                    'sort_at' => optional($leaveRequest->created_at)->timestamp ?: 0,
                ];
            });

        $actions = (clone $pendingLeaveApprovalQuery)->latest()->take(5)->get()->map(function ($approval) {
            $leaveRequest = $approval->leaveRequest;
            return [
                'module' => 'Cuti',
                'title' => 'Approval cuti',
                'subtitle' => optional($leaveRequest->user)->name ?: 'Pengajuan cuti',
                'description' => optional($leaveRequest->leaveType)->name ?: 'Ada pengajuan cuti yang menunggu approval.',
                'url' => route('cuti.approval.show', $approval),
                'icon' => 'fas fa-calendar-check',
                'tone' => 'green',
                'sort_at' => optional($approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
                'time' => optional($approval->updated_at ?: $approval->created_at)->diffForHumans(),
            ];
        });

        return [
            'enabled' => true,
            'stats' => [
                'pengajuan_saya' => (clone $myLeaveQuery)->count(),
                'draft' => (clone $myLeaveQuery)->where('status', LeaveRequest::STATUS_DRAFT)->count(),
                'diproses' => (clone $myLeaveQuery)->whereIn('status', [
                    LeaveRequest::STATUS_SUBMITTED,
                    LeaveRequest::STATUS_UNDER_REVIEW,
                    LeaveRequest::STATUS_VERIFIED,
                ])->count(),
                'disetujui' => (clone $myLeaveQuery)->whereIn('status', [
                    LeaveRequest::STATUS_APPROVED,
                    LeaveRequest::STATUS_COMPLETED,
                ])->count(),
                'approval_pending' => (clone $pendingLeaveApprovalQuery)->count(),
            ],
            'recent' => $recent,
            'actions' => $actions,
            'pending_approvals' => (clone $pendingLeaveApprovalQuery)->count(),
        ];
    }

    protected function buildInventorySection($user)
    {
        $enabled = $user->canAccessInventoryModule() && Schema::hasTable('inventory_items');
        if (!$enabled) {
            return [
                'enabled' => false,
                'stats' => [],
                'recent' => collect(),
                'actions' => collect(),
            ];
        }

        $recentTransactions = InventoryMaintenanceTransaction::with(['item', 'detail'])
            ->latest('transaction_date')
            ->take(6)
            ->get()
            ->map(function ($transaction) {
                return [
                    'type' => 'Perawatan Alat/Mesin',
                    'title' => optional($transaction->item)->name ?: 'Inventaris',
                    'subtitle' => optional($transaction->detail)->sub_code ?: '-',
                    'meta' => optional($transaction->transaction_date)->translatedFormat('d F Y') . ' • Rp ' . number_format($transaction->amount, 0, ',', '.'),
                    'url' => route('perawatan-alat-mesin.maintenance.index'),
                    'badge' => $transaction->status_badge,
                    'sort_at' => optional($transaction->created_at)->timestamp ?: 0,
                ];
            });

        return [
            'enabled' => true,
            'stats' => [
                'item_count' => InventoryItem::count(),
                'detail_count' => InventoryItemDetail::count(),
                'room_count' => InventoryRoom::count(),
                'maintenance_count' => InventoryMaintenanceTransaction::count(),
                'maintenance_total' => (float) InventoryMaintenanceTransaction::sum('amount'),
            ],
            'recent' => $recentTransactions,
            'actions' => collect(),
        ];
    }

    protected function visibleAgendaQuery($user)
    {
        $query = AgendaPimpinan::query();

        if (!$user->isSuperAdmin() && !$user->canAccessAgendaPimpinan()) {
            $query->where(function ($builder) use ($user) {
                $builder->where('created_by', $user->id)
                    ->orWhereHas('recipients', function ($recipientQuery) use ($user) {
                        $recipientQuery->where('users.id', $user->id);
                    });
            });
        }

        return $query;
    }

    protected function pendingMeetingFollowUpQuery($user)
    {
        $query = RapatNotulensiTindakLanjut::with(['notulensi.rapat', 'user.unit'])
            ->where('status', 'pending');

        if (!$user->canAccessMeetingMinutes()) {
            if ($user->canMonitorNotulensiFollowUps()) {
                $monitorableUnits = $user->monitorable_meeting_unit_codes;
                $query->whereHas('user.unit', function ($unitQuery) use ($monitorableUnits) {
                    $unitQuery->whereIn('kode', $monitorableUnits);
                });
            } else {
                $query->where('user_id', $user->id);
            }
        }

        return $query;
    }
}
