<?php

namespace App\Http\Controllers;

use App\AgendaPimpinan;
use App\Disposisi;
use App\LeaveApproval;
use App\LeaveRequest;
use App\Rapat;
use App\RapatApproval;
use App\RapatNotulensiApproval;
use App\RapatNotulensiTindakLanjut;
use App\SuratKeluar;
use App\SuratMasuk;
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

        $actionItems = collect()
            ->merge($persuratan['actions'])
            ->merge($meeting['actions'])
            ->merge($leave['actions'])
            ->sortByDesc('sort_at')
            ->take(10)
            ->values();

        return view('dashboard', [
            'persuratan' => $persuratan,
            'meeting' => $meeting,
            'leave' => $leave,
            'actionItems' => $actionItems,
            'dashboardSummary' => [
                'today_masuk' => $persuratan['today_masuk'],
                'today_keluar' => $persuratan['today_keluar'],
                'upcoming_meetings' => $meeting['upcoming_count'],
                'pending_leave_approvals' => $leave['pending_approvals'],
                'action_count' => $actionItems->count(),
            ],
        ]);
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
