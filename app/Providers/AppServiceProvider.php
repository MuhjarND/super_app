<?php

namespace App\Providers;

use App\Disposisi;
use App\LeaveApproval;
use App\RapatApproval;
use App\RapatNotulensiApproval;
use App\RapatNotulensiTindakLanjut;
use App\SuratKeluarApproval;
use App\SuratKeluar;
use App\SuratMasuk;
use App\ZiActivity;
use App\ZiActivityApproval;
use App\ZiEvidence;
use App\ZiIndicator;
use App\Services\UnifiedActionCenterService;
use App\Services\ModuleSettingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        spl_autoload_register(function ($class) {
            $prefix = 'Picqer\\Barcode\\';
            if (strpos($class, $prefix) !== 0) {
                return;
            }

            $relative = substr($class, strlen($prefix));
            $path = app_path('Support/PicqerBarcode/' . str_replace('\\', '/', $relative) . '.php');
            if (is_file($path)) {
                require_once $path;
            }
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        date_default_timezone_set(config('app.timezone', 'Asia/Jayapura'));
        Carbon::setLocale(config('app.locale', 'id'));
        Carbon::setFallbackLocale(config('app.fallback_locale', 'id'));

        setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'id', 'Indonesian_indonesia.1252', 'IND');

        View::composer('library.*', function ($view) {
            $view->with('canManageLibrary', Auth::check() && Auth::user()->canManageLibraryModule());
        });

        View::composer('layouts.app', function ($view) {
            $counts = [
                'sidebarSuratMasukOpenCount' => 0,
                'sidebarSuratKeluarDraftCount' => 0,
                'sidebarArsipCount' => 0,
                'sidebarApprovalPendingCount' => 0,
                'sidebarNotulensiApprovalPendingCount' => 0,
                'sidebarLeaveApprovalPendingCount' => 0,
                'sidebarSuratKeluarApprovalPendingCount' => 0,
                'sidebarApprovalTotalCount' => 0,
                'sidebarNotulensiFollowUpCount' => 0,
                'sidebarProgressZiAttentionCount' => 0,
                'sidebarProgressZiApprovalPendingCount' => 0,
                'sidebarActionCenterCount' => 0,
                'topbarActionCount' => 0,
                'topbarActionItems' => collect(),
            ];

            $user = Auth::user();
            $view->with('moduleControls', app(ModuleSettingService::class)->statesFor($user));
            if (!$user) {
                $view->with($counts);
                return;
            }

            $suratMasukVisible = SuratMasuk::visibleTo($user);
            $suratKeluarVisible = SuratKeluar::visibleTo($user);
            $notificationYear = now('Asia/Jayapura')->year;

            $suratMasukBadgeQuery = SuratMasuk::visibleTo($user)
                ->forLetterYear($notificationYear)
                ->where(function ($query) use ($user) {
                    $query->whereHas('disposisis', function ($disposisiQuery) use ($user) {
                        $disposisiQuery->addressedToUser($user)
                            ->where('status', 'pending');
                    });

                    if ($user->isSuperAdmin() || $user->canManageInitialSuratMasuk()) {
                        $query->orWhere('status', 'baru');
                    }
                });

            $suratKeluarBadgeQuery = SuratKeluar::visibleTo($user)
                ->forLetterYear($notificationYear)
                ->where(function ($query) use ($user) {
                    $query->where(function ($createdQuery) use ($user) {
                        $createdQuery->where('created_by', $user->id)
                            ->where('status', 'draft');
                    });

                    if (Schema::hasColumn('surat_keluar_penerima', 'read_at')) {
                        $query->orWhereHas('penerimaInternal', function ($penerimaQuery) use ($user) {
                            $penerimaQuery->whereIn('users.id', $user->effectiveAssignmentUserIds())
                                ->whereNull('surat_keluar_penerima.read_at');
                        });
                    }
                });

            $counts['sidebarSuratMasukOpenCount'] = (clone $suratMasukBadgeQuery)->count();
            $counts['sidebarSuratKeluarDraftCount'] = (clone $suratKeluarBadgeQuery)->count();
            $counts['sidebarArsipCount'] = (clone $suratKeluarVisible)
                ->where('status', 'lengkap')
                ->count();

            $actionItems = collect();

            $pendingDisposisiQuery = Disposisi::with(['suratMasuk'])
                ->addressedToUser($user)
                ->where('status', 'pending')
                ->whereHas('suratMasuk', function ($suratQuery) use ($notificationYear) {
                    $suratQuery->forLetterYear($notificationYear);
                });

            foreach ((clone $pendingDisposisiQuery)->latest()->take(5)->get() as $disposisi) {
                $suratMasuk = $disposisi->suratMasuk;
                $actionItems->push([
                    'type' => 'surat',
                    'icon' => 'fas fa-inbox',
                    'icon_bg' => '#fef3c7',
                    'icon_color' => '#b45309',
                    'title' => 'Tindak lanjut surat masuk',
                    'subtitle' => $suratMasuk ? ($suratMasuk->nomor_surat ?: 'Surat masuk') : 'Surat masuk',
                    'description' => $suratMasuk ? ($suratMasuk->perihal ?: 'Ada disposisi yang perlu ditindaklanjuti.') : 'Ada disposisi yang perlu ditindaklanjuti.',
                    'time' => optional($disposisi->created_at)->diffForHumans(),
                    'url' => route('surat-masuk.index'),
                    'sort_at' => optional($disposisi->created_at)->timestamp ?: 0,
                ]);
            }

            if (Schema::hasColumn('surat_keluar_penerima', 'read_at')) {
                $taggedSuratKeluarQuery = SuratKeluar::visibleTo($user)
                    ->with(['creator'])
                    ->forLetterYear($notificationYear)
                    ->whereHas('penerimaInternal', function ($penerimaQuery) use ($user) {
                        $penerimaQuery->where('users.id', $user->id)
                            ->whereNull('surat_keluar_penerima.read_at');
                    });

                foreach ((clone $taggedSuratKeluarQuery)->latest()->take(5)->get() as $suratKeluar) {
                    $actionItems->push([
                        'type' => 'surat-keluar-tag',
                        'icon' => 'fas fa-paper-plane',
                        'icon_bg' => '#e0f2fe',
                        'icon_color' => '#0369a1',
                        'title' => 'Surat keluar untuk Anda',
                        'subtitle' => $suratKeluar->nomor_surat_formatted ?: $suratKeluar->nomor_surat,
                        'description' => $suratKeluar->perihal ?: 'Ada surat keluar baru yang menandai Anda.',
                        'time' => optional($suratKeluar->created_at)->diffForHumans(),
                        'url' => route('surat-keluar.file', $suratKeluar),
                        'sort_at' => optional($suratKeluar->created_at)->timestamp ?: 0,
                    ]);
                }
            }

            $pendingApprovalQuery = RapatApproval::with(['rapat'])
                ->where('status', 'pending');

            if (!$user->isMeetingAdmin()) {
                $pendingApprovalQuery->whereIn('approver_id', $user->effectiveAssignmentUserIds());
            }

            $counts['sidebarApprovalPendingCount'] = (clone $pendingApprovalQuery)->count();

            foreach ((clone $pendingApprovalQuery)->latest()->take(5)->get() as $approval) {
                $rapat = $approval->rapat;
                $actionItems->push([
                    'type' => 'rapat',
                    'icon' => 'fas fa-file-signature',
                    'icon_bg' => '#dbeafe',
                    'icon_color' => '#1d4ed8',
                    'title' => $approval->stage_label . ' undangan rapat',
                    'subtitle' => $rapat ? ($rapat->nomor_undangan ?: 'Undangan rapat') : 'Undangan rapat',
                    'description' => $rapat ? ($rapat->judul ?: 'Ada dokumen rapat yang menunggu approval.') : 'Ada dokumen rapat yang menunggu approval.',
                    'time' => optional($approval->updated_at ?: $approval->created_at)->diffForHumans(),
                    'url' => route('rapat.approval.show', $approval),
                    'sort_at' => optional($approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
                ]);
            }

            $pendingNotulensiApprovalQuery = RapatNotulensiApproval::with(['notulensi.rapat'])
                ->where('status', 'pending');

            if (!$user->isMeetingAdmin()) {
                $pendingNotulensiApprovalQuery->whereIn('approver_id', $user->effectiveAssignmentUserIds());
            }

            $counts['sidebarNotulensiApprovalPendingCount'] = (clone $pendingNotulensiApprovalQuery)->count();

            foreach ((clone $pendingNotulensiApprovalQuery)->latest()->take(5)->get() as $approval) {
                $notulensi = $approval->notulensi;
                $rapat = optional($notulensi)->rapat;
                $actionItems->push([
                    'type' => 'notulensi-approval',
                    'icon' => 'fas fa-file-contract',
                    'icon_bg' => '#ede9fe',
                    'icon_color' => '#6d28d9',
                    'title' => 'Approval notulen',
                    'subtitle' => $rapat ? ($rapat->judul ?: 'Notulen agenda') : 'Notulen agenda',
                    'description' => $notulensi ? ($notulensi->judul ?: 'Ada notulen yang menunggu approval.') : 'Ada notulen yang menunggu approval.',
                    'time' => optional($approval->updated_at ?: $approval->created_at)->diffForHumans(),
                    'url' => route('rapat.notulensi-approval.show', $approval),
                    'sort_at' => optional($approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
                ]);
            }

            $pendingFollowUpQuery = RapatNotulensiTindakLanjut::with(['notulensi.rapat'])
                ->where('status', 'pending')
                ->visibleTo($user);

            foreach ((clone $pendingFollowUpQuery)->latest()->take(5)->get() as $followUp) {
                $rapat = optional($followUp->notulensi)->rapat;
                $actionItems->push([
                    'type' => 'notulensi',
                    'icon' => 'fas fa-tasks',
                    'icon_bg' => '#fee2e2',
                    'icon_color' => '#b91c1c',
                    'title' => 'Tindak lanjut rekomendasi notulen',
                    'subtitle' => $rapat ? ($rapat->judul ?: 'Notulen rapat') : 'Notulen rapat',
                    'description' => $followUp->deskripsi_snapshot ?: 'Ada rekomendasi rapat yang perlu ditindaklanjuti.',
                    'time' => optional($followUp->updated_at ?: $followUp->created_at)->diffForHumans(),
                    'url' => route('rapat.notulensi.follow-ups'),
                    'sort_at' => optional($followUp->updated_at ?: $followUp->created_at)->timestamp ?: 0,
                ]);
            }

            if (Schema::hasTable('leave_approvals') && $user->canAccessLeaveApproval()) {
                $pendingLeaveApprovalQuery = LeaveApproval::with(['leaveRequest.user', 'leaveRequest.leaveType'])
                    ->where('status', 'pending');

                if (!$user->isSuperAdmin()) {
                    $pendingLeaveApprovalQuery->whereIn('approver_id', $user->effectiveAssignmentUserIds());
                }

                $counts['sidebarLeaveApprovalPendingCount'] = (clone $pendingLeaveApprovalQuery)->count();

                foreach ((clone $pendingLeaveApprovalQuery)->latest()->take(5)->get() as $approval) {
                    $leaveRequest = $approval->leaveRequest;
                    $actionItems->push([
                        'type' => 'leave-approval',
                        'icon' => 'fas fa-calendar-check',
                        'icon_bg' => '#dcfce7',
                        'icon_color' => '#166534',
                        'title' => 'Approval cuti',
                        'subtitle' => optional($leaveRequest->user)->name ?: 'Pengajuan cuti',
                        'description' => optional($leaveRequest->leaveType)->name ?: 'Ada pengajuan cuti yang menunggu approval.',
                        'time' => optional($approval->updated_at ?: $approval->created_at)->diffForHumans(),
                        'url' => route('cuti.approval.show', $approval),
                        'sort_at' => optional($approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
                    ]);
                }
            }

            if (Schema::hasTable('surat_keluar_approvals') && Schema::hasColumn('surat_keluar_approvals', 'paraf_user_id')) {
                $assignmentUserIds = $user->effectiveAssignmentUserIds();
                $pendingSuratKeluarApprovalQuery = SuratKeluarApproval::with(['suratKeluar', 'parafUser', 'approver'])
                    ->where('status', 'pending')
                    ->whereHas('suratKeluar', function ($suratQuery) use ($notificationYear) {
                        $suratQuery->forLetterYear($notificationYear);
                    });

                if (!$user->isSuperAdmin()) {
                    $pendingSuratKeluarApprovalQuery->where(function ($workflowQuery) use ($user, $assignmentUserIds) {
                        $workflowQuery->where(function ($parafQuery) use ($assignmentUserIds) {
                            $parafQuery->where('paraf_status', 'pending')->whereIn('paraf_user_id', $assignmentUserIds);
                        });

                        if ($user->canApproveSuratKeluarTemplate()) {
                            $workflowQuery->orWhere(function ($approvalQuery) use ($assignmentUserIds) {
                                $approvalQuery->whereIn('paraf_status', ['not_required', 'approved'])
                                    ->whereIn('approver_id', $assignmentUserIds);
                            });
                        }
                    });
                }

                $counts['sidebarSuratKeluarApprovalPendingCount'] = (clone $pendingSuratKeluarApprovalQuery)->count();

                foreach ((clone $pendingSuratKeluarApprovalQuery)->latest()->take(5)->get() as $approval) {
                    $suratKeluar = $approval->suratKeluar;
                    $isParafTask = $approval->paraf_status === 'pending'
                        && ($user->isSuperAdmin() || in_array((int) $approval->paraf_user_id, array_map('intval', $assignmentUserIds), true));
                    $actionItems->push([
                        'type' => 'surat-keluar-approval',
                        'icon' => 'fas fa-paper-plane',
                        'icon_bg' => '#fee2e2',
                        'icon_color' => '#b91c1c',
                        'title' => $isParafTask ? 'Paraf Surat Tugas' : 'Approval surat keluar',
                        'subtitle' => $approval->template_name ?: 'Surat Keluar',
                        'description' => $suratKeluar ? ($suratKeluar->nomor_surat_formatted ?: $suratKeluar->nomor_surat) : ($isParafTask ? 'Surat Tugas menunggu paraf.' : 'Dokumen surat keluar dari template menunggu approval.'),
                        'time' => optional($approval->updated_at ?: $approval->created_at)->diffForHumans(),
                        'url' => route('surat-keluar.approval.show', $approval),
                        'sort_at' => optional($approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
                    ]);
                }
            }

            if (Schema::hasTable('zi_activities') && $user->canAccessProgressZiModule()) {
                $ziActivityQuery = ZiActivity::with(['area', 'pic'])
                    ->where(function ($query) {
                        $query->whereDate('target_end_date', '<', today())
                            ->whereNotIn('status', ['sudah_terlaksana', 'selesai']);
                    });

                $ziIndicatorQuery = ZiIndicator::with(['activity.area'])
                    ->whereIn('status', ['belum_diisi', 'belum_terpenuhi', 'sebagian_terpenuhi', 'ditolak']);

                $ziEvidenceQuery = ZiEvidence::with(['realization.activity.area'])
                    ->whereIn('status', ['terupload', 'terhubung', 'revisi', 'tidak_valid']);

                if (!$user->canManageProgressZiMasterData()) {
                    $ziActivityQuery->where(function ($query) use ($user) {
                        $query->where('pic_user_id', $user->id)
                            ->orWhereHas('area', function ($areaQuery) use ($user) {
                                $areaQuery->where('pic_user_id', $user->id);
                            });
                    });

                    $ziIndicatorQuery->whereHas('activity', function ($query) use ($user) {
                        $query->where('pic_user_id', $user->id)
                            ->orWhereHas('area', function ($areaQuery) use ($user) {
                                $areaQuery->where('pic_user_id', $user->id);
                            });
                    });

                    $ziEvidenceQuery->whereHas('realization.activity', function ($query) use ($user) {
                        $query->where('pic_user_id', $user->id)
                            ->orWhereHas('area', function ($areaQuery) use ($user) {
                                $areaQuery->where('pic_user_id', $user->id);
                            });
                    });
                }

                foreach ((clone $ziActivityQuery)->orderBy('target_end_date')->take(4)->get() as $activity) {
                    $actionItems->push([
                        'type' => 'progress-zi-overdue',
                        'icon' => 'fas fa-chart-line',
                        'icon_bg' => '#ede9fe',
                        'icon_color' => '#6d28d9',
                        'title' => 'Kegiatan ZI overdue',
                        'subtitle' => $activity->name,
                        'description' => (optional($activity->area)->name ?: 'Progress ZI') . ' • target ' . (optional($activity->target_end_date)->translatedFormat('d F Y') ?: '-'),
                        'time' => optional($activity->target_end_date)->diffForHumans(),
                        'url' => route('progress-zi.activities.index', ['period_id' => $activity->zi_period_id, 'area_id' => $activity->zi_area_id]),
                        'sort_at' => optional($activity->target_end_date)->timestamp ?: 0,
                    ]);
                }

                if ($user->canVerifyProgressZi()) {
                    foreach ((clone $ziIndicatorQuery)->latest()->take(3)->get() as $indicator) {
                        $actionItems->push([
                            'type' => 'progress-zi-indicator',
                            'icon' => 'fas fa-clipboard-check',
                            'icon_bg' => '#dbeafe',
                            'icon_color' => '#1d4ed8',
                            'title' => 'Review indikator ZI',
                            'subtitle' => $indicator->name,
                            'description' => optional($indicator->activity)->name ?: 'Indikator perlu review',
                            'time' => optional($indicator->updated_at ?: $indicator->created_at)->diffForHumans(),
                            'url' => route('progress-zi.activities.index', ['period_id' => optional($indicator->activity)->zi_period_id, 'area_id' => optional($indicator->activity)->zi_area_id]),
                            'sort_at' => optional($indicator->updated_at ?: $indicator->created_at)->timestamp ?: 0,
                        ]);
                    }

                    foreach ((clone $ziEvidenceQuery)->latest()->take(3)->get() as $evidence) {
                        $actionItems->push([
                            'type' => 'progress-zi-evidence',
                            'icon' => 'fas fa-paperclip',
                            'icon_bg' => '#dcfce7',
                            'icon_color' => '#166534',
                            'title' => 'Review eviden ZI',
                            'subtitle' => $evidence->title,
                            'description' => optional(optional($evidence->realization)->activity)->name ?: 'Eviden perlu review',
                            'time' => optional($evidence->updated_at ?: $evidence->created_at)->diffForHumans(),
                            'url' => route('progress-zi.activities.index', ['period_id' => optional(optional($evidence->realization)->activity)->zi_period_id, 'area_id' => optional(optional($evidence->realization)->activity)->zi_area_id]),
                            'sort_at' => optional($evidence->updated_at ?: $evidence->created_at)->timestamp ?: 0,
                        ]);
                    }
                }

                $counts['sidebarProgressZiAttentionCount'] = (clone $ziActivityQuery)->count()
                    + ($user->canVerifyProgressZi() ? (clone $ziIndicatorQuery)->count() + (clone $ziEvidenceQuery)->count() : 0);
            }

            if (Schema::hasTable('zi_activity_approvals') && $user->canVerifyProgressZi()) {
                $ziApprovalQuery = ZiActivityApproval::with(['activity.area'])
                    ->where('status', 'pending');

                if (!$user->isSuperAdmin()) {
                    $ziApprovalQuery->whereIn('approver_id', $user->effectiveAssignmentUserIds());
                }

                $counts['sidebarProgressZiApprovalPendingCount'] = (clone $ziApprovalQuery)->count();

                foreach ((clone $ziApprovalQuery)->latest('requested_at')->take(5)->get() as $approval) {
                    $actionItems->push([
                        'type' => 'progress-zi-approval',
                        'icon' => 'fas fa-clipboard-check',
                        'icon_bg' => '#fee2e2',
                        'icon_color' => '#b91c1c',
                        'title' => 'Review pimpinan Progress ZI',
                        'subtitle' => optional($approval->activity)->name ?: 'Progress ZI',
                        'description' => optional(optional($approval->activity)->area)->name ?: 'Ada tindak lanjut ZI yang menunggu review pimpinan.',
                        'time' => optional($approval->requested_at ?: $approval->created_at)->diffForHumans(),
                        'url' => route('progress-zi.approvals.show', $approval),
                        'sort_at' => optional($approval->requested_at ?: $approval->created_at)->timestamp ?: 0,
                    ]);
                }
            }

            $counts['sidebarNotulensiFollowUpCount'] = (clone $pendingFollowUpQuery)->count();
            $counts['sidebarApprovalTotalCount'] = $counts['sidebarApprovalPendingCount'] + $counts['sidebarNotulensiApprovalPendingCount'] + $counts['sidebarLeaveApprovalPendingCount'] + $counts['sidebarSuratKeluarApprovalPendingCount'] + $counts['sidebarProgressZiApprovalPendingCount'];

            $unifiedPayload = app(UnifiedActionCenterService::class)->build($user, ['tab' => 'all']);
            $counts['sidebarActionCenterCount'] = $unifiedPayload['summary']['active_count'] ?? 0;
            $counts['topbarActionCount'] = $counts['sidebarActionCenterCount'];
            $counts['topbarActionItems'] = collect($unifiedPayload['items'] ?? [])->take(8)->map(function ($item) {
                return [
                    'type' => $item['module_key'] ?? 'action-center',
                    'icon' => $item['module_icon'] ?? 'fas fa-tasks',
                    'icon_bg' => $this->notificationTone($item['module_key'] ?? null)['bg'],
                    'icon_color' => $this->notificationTone($item['module_key'] ?? null)['text'],
                    'title' => $item['type_label'] ?? 'Tindak Lanjut',
                    'subtitle' => $item['title'] ?? '-',
                    'description' => $item['description'] ?? '-',
                    'time' => !empty($item['target_at']) ? $item['target_at']->diffForHumans() : null,
                    'url' => $item['action_url'] ?? route('action-center.index'),
                    'sort_at' => $item['sort_at'] ?? 0,
                ];
            })->values();

            $view->with($counts);
        });
    }

    protected function notificationTone($moduleKey)
    {
        $map = [
            'persuratan' => ['bg' => '#eff6ff', 'text' => '#1d4ed8'],
            'rapat' => ['bg' => '#eef2ff', 'text' => '#4338ca'],
            'cuti' => ['bg' => '#fef2f2', 'text' => '#dc2626'],
            'progress_zi' => ['bg' => '#fff7ed', 'text' => '#d97706'],
            'perawatan' => ['bg' => '#ecfeff', 'text' => '#0f766e'],
        ];

        return $map[$moduleKey] ?? ['bg' => '#f1f5f9', 'text' => '#334155'];
    }
}
