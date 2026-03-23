<?php

namespace App\Providers;

use App\Disposisi;
use App\LeaveApproval;
use App\RapatApproval;
use App\RapatNotulensiApproval;
use App\RapatNotulensiTindakLanjut;
use App\SuratKeluar;
use App\SuratMasuk;
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
        //
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

        View::composer('layouts.app', function ($view) {
            $counts = [
                'sidebarSuratMasukOpenCount' => 0,
                'sidebarSuratKeluarDraftCount' => 0,
                'sidebarArsipCount' => 0,
                'sidebarApprovalPendingCount' => 0,
                'sidebarNotulensiApprovalPendingCount' => 0,
                'sidebarLeaveApprovalPendingCount' => 0,
                'sidebarApprovalTotalCount' => 0,
                'sidebarNotulensiFollowUpCount' => 0,
                'topbarActionCount' => 0,
                'topbarActionItems' => collect(),
            ];

            $user = Auth::user();
            if (!$user) {
                $view->with($counts);
                return;
            }

            $suratMasukVisible = SuratMasuk::visibleTo($user);
            $suratKeluarVisible = SuratKeluar::visibleTo($user);

            $counts['sidebarSuratMasukOpenCount'] = (clone $suratMasukVisible)
                ->where('status', '!=', 'selesai')
                ->count();
            $counts['sidebarSuratKeluarDraftCount'] = (clone $suratKeluarVisible)
                ->where('status', 'draft')
                ->count();
            $counts['sidebarArsipCount'] = (clone $suratKeluarVisible)
                ->where('status', 'lengkap')
                ->count();

            $actionItems = collect();

            $pendingDisposisiQuery = Disposisi::with(['suratMasuk'])
                ->where('kepada_user_id', $user->id)
                ->where('status', 'pending');

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

            $pendingApprovalQuery = RapatApproval::with(['rapat'])
                ->where('status', 'pending');

            if (!$user->isMeetingAdmin()) {
                $pendingApprovalQuery->where('approver_id', $user->id);
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
                $pendingNotulensiApprovalQuery->where('approver_id', $user->id);
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
                ->where('status', 'pending');

            if (!$user->canAccessMeetingMinutes()) {
                if ($user->canMonitorNotulensiFollowUps()) {
                    $monitorableUnits = $user->monitorable_meeting_unit_codes;
                    $pendingFollowUpQuery->whereHas('user.unit', function ($unitQuery) use ($monitorableUnits) {
                        $unitQuery->whereIn('kode', $monitorableUnits);
                    });
                } else {
                    $pendingFollowUpQuery->where('user_id', $user->id);
                }
            }

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

            if (Schema::hasTable('leave_approvals') && $user->canApproveLeave()) {
                $pendingLeaveApprovalQuery = LeaveApproval::with(['leaveRequest.user', 'leaveRequest.leaveType'])
                    ->where('status', 'pending');

                if (!$user->isSuperAdmin()) {
                    $pendingLeaveApprovalQuery->where('approver_id', $user->id);
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

            $counts['sidebarNotulensiFollowUpCount'] = (clone $pendingFollowUpQuery)->count();
            $counts['sidebarApprovalTotalCount'] = $counts['sidebarApprovalPendingCount'] + $counts['sidebarNotulensiApprovalPendingCount'] + $counts['sidebarLeaveApprovalPendingCount'];

            $counts['topbarActionItems'] = $actionItems
                ->sortByDesc('sort_at')
                ->take(8)
                ->values();
            $counts['topbarActionCount'] = (clone $pendingDisposisiQuery)->count()
                + $counts['sidebarApprovalPendingCount']
                + $counts['sidebarNotulensiApprovalPendingCount']
                + $counts['sidebarLeaveApprovalPendingCount']
                + $counts['sidebarNotulensiFollowUpCount'];

            $view->with($counts);
        });
    }
}
