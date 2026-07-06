<?php

namespace App\Services;

use App\Disposisi;
use App\InventoryMaintenanceTransaction;
use App\LeaveApproval;
use App\LeaveRequest;
use App\RapatApproval;
use App\RapatNotulensiApproval;
use App\RapatNotulensiTindakLanjut;
use App\SuratKeluar;
use App\SuratKeluarApproval;
use App\User;
use App\ZiActivity;
use App\ZiActivityApproval;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Optional;
use Illuminate\Support\Facades\Schema;

class UnifiedActionCenterService
{
    public function build(User $user, array $filters = [])
    {
        $filters = $this->normalizeFilters($filters);

        $activeItems = collect()
            ->merge($this->buildPersuratanItems($user))
            ->merge($this->buildMeetingItems($user))
            ->merge($this->buildLeaveItems($user))
            ->merge($this->buildProgressZiItems($user))
            ->merge($this->buildInventoryItems($user))
            ->map(function (array $item) {
                return $this->normalizeItem($item);
            })
            ->values();

        $historyItems = collect()
            ->merge($this->buildHistoricalMeetingItems($user))
            ->merge($this->buildHistoricalLeaveItems($user))
            ->merge($this->buildHistoricalProgressZiItems($user))
            ->merge($this->buildHistoricalPersuratanItems($user))
            ->merge($this->buildHistoricalInventoryItems($user))
            ->map(function (array $item) {
                return $this->normalizeItem($item);
            })
            ->values();

        $baseItems = in_array($filters['tab'], ['history', 'done_today'], true) ? $historyItems : $activeItems;
        $filteredWithoutTab = $this->applyBaseFilters($baseItems, $filters);
        $filtered = $this->applyTabFilter($filteredWithoutTab, $filters['tab'], $user)->values();
        $sorted = $this->sortItems($filtered)->values();

        return [
            'items' => $sorted,
            'summary' => $this->buildSummary($activeItems, $filteredWithoutTab, $sorted),
            'tab_counts' => $this->buildTabCounts($activeItems, $historyItems, $user),
            'filters' => $filters,
            'module_options' => $this->moduleOptions(),
            'status_options' => $this->statusOptions(),
            'tab_options' => $this->tabOptions(),
            'group_options' => $this->groupOptions(),
            'unit_options' => $this->unitOptions($activeItems, $historyItems),
            'assignee_options' => $this->assigneeOptions($activeItems, $historyItems),
        ];
    }

    protected function normalizeFilters(array $filters)
    {
        return [
            'tab' => $filters['tab'] ?? 'all',
            'module' => $filters['module'] ?? 'all',
            'status' => $filters['status'] ?? 'all',
            'unit' => $filters['unit'] ?? 'all',
            'assignee' => $filters['assignee'] ?? 'all',
            'group' => $filters['group'] ?? 'module',
            'search' => trim((string) ($filters['search'] ?? '')),
        ];
    }

    protected function normalizeItem(array $item)
    {
        $targetAt = $this->normalizeDateTime($item['target_at'] ?? null);

        $statusMap = [
            'waiting' => 'Menunggu Aksi',
            'process' => 'Diproses',
            'revision' => 'Perlu Revisi',
            'overdue' => 'Overdue',
            'done' => 'Selesai',
        ];

        $priorityMap = [
            'high' => ['label' => 'Tinggi', 'weight' => 3],
            'normal' => ['label' => 'Normal', 'weight' => 2],
            'low' => ['label' => 'Rendah', 'weight' => 1],
        ];

        $priority = $priorityMap[$item['priority_key'] ?? 'normal'] ?? $priorityMap['normal'];
        $statusKey = $item['status_key'] ?? 'waiting';
        $statusLabel = $item['status_label'] ?? ($statusMap[$statusKey] ?? ucfirst($statusKey));
        $isOverdue = (bool) ($item['is_overdue'] ?? false);

        if ($isOverdue && $statusKey === 'waiting') {
            $statusKey = 'overdue';
            $statusLabel = $statusMap['overdue'];
        }

        $targetLabel = '-';
        if ($targetAt) {
            $today = now('Asia/Jayapura')->startOfDay();
            if ($targetAt->isToday()) {
                $targetLabel = 'Hari ini';
            } elseif ($targetAt->copy()->startOfDay()->equalTo($today->copy()->addDay())) {
                $targetLabel = 'Besok';
            } else {
                $targetLabel = $targetAt->translatedFormat('d M Y');
            }
        }

        $searchText = collect([
            $item['module_label'] ?? null,
            $item['type_label'] ?? null,
            $item['title'] ?? null,
            $item['subtitle'] ?? null,
            $item['description'] ?? null,
            $item['unit_label'] ?? null,
            $statusLabel,
        ])->filter()->implode(' ');

        return array_merge($item, [
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
            'priority_label' => $item['priority_label'] ?? $priority['label'],
            'priority_weight' => $item['priority_weight'] ?? $priority['weight'],
            'target_at' => $targetAt,
            'target_date' => optional($targetAt)->toDateString(),
            'target_label' => $item['target_label'] ?? $targetLabel,
            'is_overdue' => $isOverdue,
            'sort_at' => $item['sort_at'] ?? optional($targetAt)->timestamp ?? 0,
            'search_text' => mb_strtolower($searchText),
            'action_text' => $item['action_text'] ?? 'Buka',
            'subtitle' => $item['subtitle'] ?? '-',
            'description' => $item['description'] ?? '-',
            'unit_label' => $item['unit_label'] ?? '-',
            'assignee_name' => $item['assignee_name'] ?? '-',
        ]);
    }

    protected function normalizeDateTime($value)
    {
        if ($value instanceof Carbon) {
            return $value->copy()->timezone('Asia/Jayapura');
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->timezone('Asia/Jayapura');
        }

        if ($value instanceof Optional) {
            $value = $value->toDateTimeString() ?: $value->toDateString() ?: null;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value, 'Asia/Jayapura');
    }

    protected function applyBaseFilters(Collection $items, array $filters)
    {
        return $items->filter(function (array $item) use ($filters) {
            if ($filters['module'] !== 'all' && $item['module_key'] !== $filters['module']) {
                return false;
            }

            if ($filters['status'] !== 'all' && $item['status_key'] !== $filters['status']) {
                return false;
            }

            if ($filters['unit'] !== 'all' && ($item['unit_label'] ?? '-') !== $filters['unit']) {
                return false;
            }

            if ($filters['assignee'] !== 'all' && ($item['assignee_name'] ?? '-') !== $filters['assignee']) {
                return false;
            }

            if ($filters['search'] !== '' && mb_strpos($item['search_text'], mb_strtolower($filters['search'])) === false) {
                return false;
            }

            return true;
        })->values();
    }

    protected function applyTabFilter(Collection $items, $tab, User $user)
    {
        return $items->filter(function (array $item) use ($tab, $user) {
            switch ($tab) {
                case 'high':
                    return $item['priority_key'] === 'high';
                case 'overdue':
                    return $item['is_overdue'];
                case 'today':
                    return $item['target_date'] === now('Asia/Jayapura')->toDateString();
                case 'mine':
                    return (int) ($item['assignee_id'] ?? 0) === (int) $user->id;
                case 'done_today':
                    return $item['status_key'] === 'done'
                        && $item['target_date'] === now('Asia/Jayapura')->toDateString();
                case 'history':
                    return true;
                default:
                    return true;
            }
        });
    }

    protected function sortItems(Collection $items)
    {
        return $items->sort(function (array $left, array $right) {
            $leftOverdue = $left['is_overdue'] ? 1 : 0;
            $rightOverdue = $right['is_overdue'] ? 1 : 0;
            if ($leftOverdue !== $rightOverdue) {
                return $rightOverdue <=> $leftOverdue;
            }

            if (($left['priority_weight'] ?? 0) !== ($right['priority_weight'] ?? 0)) {
                return ($right['priority_weight'] ?? 0) <=> ($left['priority_weight'] ?? 0);
            }

            $leftTarget = $left['target_at'] ? $left['target_at']->timestamp : PHP_INT_MAX;
            $rightTarget = $right['target_at'] ? $right['target_at']->timestamp : PHP_INT_MAX;
            if ($leftTarget !== $rightTarget) {
                return $leftTarget <=> $rightTarget;
            }

            return ($right['sort_at'] ?? 0) <=> ($left['sort_at'] ?? 0);
        });
    }

    protected function buildSummary(Collection $allItems, Collection $filteredWithoutTab, Collection $items)
    {
        return [
            'visible_count' => $items->count(),
            'active_count' => $filteredWithoutTab->count(),
            'high_count' => $filteredWithoutTab->where('priority_key', 'high')->count(),
            'overdue_count' => $filteredWithoutTab->where('is_overdue', true)->count(),
            'today_count' => $filteredWithoutTab->where('target_date', now('Asia/Jayapura')->toDateString())->count(),
            'module_counts' => [
                'persuratan' => $allItems->where('module_key', 'persuratan')->count(),
                'rapat' => $allItems->where('module_key', 'rapat')->count(),
                'cuti' => $allItems->where('module_key', 'cuti')->count(),
                'progress_zi' => $allItems->where('module_key', 'progress_zi')->count(),
                'perawatan' => $allItems->where('module_key', 'perawatan')->count(),
            ],
        ];
    }

    protected function buildTabCounts(Collection $activeItems, Collection $historyItems, User $user)
    {
        return [
            'all' => $activeItems->count(),
            'high' => $activeItems->where('priority_key', 'high')->count(),
            'overdue' => $activeItems->where('is_overdue', true)->count(),
            'today' => $activeItems->where('target_date', now('Asia/Jayapura')->toDateString())->count(),
            'mine' => $activeItems->where('assignee_id', $user->id)->count(),
            'done_today' => $historyItems->where('status_key', 'done')->where('target_date', now('Asia/Jayapura')->toDateString())->count(),
            'history' => $historyItems->count(),
        ];
    }

    protected function moduleOptions()
    {
        return [
            'all' => 'Semua Modul',
            'persuratan' => 'Persuratan',
            'rapat' => 'Rapat / Agenda',
            'cuti' => 'Cuti',
            'progress_zi' => 'Progress ZI',
            'perawatan' => 'Perawatan Alat dan Mesin',
        ];
    }

    protected function statusOptions()
    {
        return [
            'all' => 'Semua Status',
            'waiting' => 'Menunggu Aksi',
            'process' => 'Diproses',
            'revision' => 'Perlu Revisi',
            'overdue' => 'Overdue',
            'done' => 'Selesai',
        ];
    }

    protected function tabOptions()
    {
        return [
            'all' => 'Semua',
            'high' => 'Prioritas Tinggi',
            'overdue' => 'Overdue',
            'today' => 'Hari Ini',
            'mine' => 'Milik Saya',
            'done_today' => 'Selesai Hari Ini',
            'history' => 'Riwayat',
        ];
    }

    protected function groupOptions()
    {
        return [
            'module' => 'Kelompok Modul',
            'deadline' => 'Kelompok Target',
            'none' => 'Tanpa Kelompok',
        ];
    }

    protected function unitOptions(Collection $activeItems, Collection $historyItems)
    {
        return $activeItems->merge($historyItems)
            ->pluck('unit_label')
            ->filter(function ($value) {
                return $value && $value !== '-';
            })
            ->unique()
            ->sort()
            ->values();
    }

    protected function assigneeOptions(Collection $activeItems, Collection $historyItems)
    {
        return $activeItems->merge($historyItems)
            ->pluck('assignee_name')
            ->filter(function ($value) {
                return $value && $value !== '-';
            })
            ->unique()
            ->sort()
            ->values();
    }

    protected function buildPersuratanItems(User $user)
    {
        if (!$user->canAccessPersuratanMenu()) {
            return collect();
        }

        $items = collect();

        $disposisiQuery = Disposisi::with(['suratMasuk', 'dariUser', 'kepadaUser.unit'])
            ->whereIn('status', ['pending', 'dibaca', 'diproses'])
            ->addressedToUser($user);

        $items = $items->merge($disposisiQuery->latest()->take(10)->get()->map(function ($disposisi) {
            $targetAt = $disposisi->target_tindak_lanjut_at ?: $disposisi->created_at;

            return $this->makeItem([
                'id' => 'persuratan-disposisi-' . $disposisi->id,
                'module_key' => 'persuratan',
                'module_label' => 'Persuratan',
                'module_icon' => 'fas fa-envelope-open-text',
                'type_label' => 'Tindak Lanjut Disposisi',
                'title' => optional($disposisi->suratMasuk)->nomor_surat ?: 'Surat masuk',
                'subtitle' => optional($disposisi->suratMasuk)->perihal ?: 'Disposisi menunggu tindak lanjut',
                'description' => 'Dari ' . (optional($disposisi->dariUser)->name ?: '-') . ' • ' . ($disposisi->petunjuk ?: 'Belum ada petunjuk'),
                'status_key' => in_array($disposisi->status, ['dibaca', 'diproses'], true) ? 'process' : 'waiting',
                'priority_key' => $disposisi->priority_level ?: 'normal',
                'target_at' => $targetAt,
                'is_overdue' => $disposisi->is_overdue,
                'assignee_id' => $disposisi->kepada_user_id,
                'assignee_name' => optional($disposisi->kepadaUser)->name ?: '-',
                'action_url' => optional($disposisi->suratMasuk) ? route('surat-masuk.show', $disposisi->suratMasuk) : route('surat-masuk.index'),
                'action_text' => 'Buka Surat',
                'unit_label' => optional(optional($disposisi->kepadaUser)->unit)->nama ?: '-',
                'sort_at' => optional($disposisi->updated_at ?: $targetAt)->timestamp ?: 0,
            ]);
        }));

        $draftQuery = SuratKeluar::with(['creator.unit', 'templateApproval'])
            ->where('status', 'draft')
            ->where('created_by', $user->id);

        $items = $items->merge($draftQuery->latest()->take(10)->get()->map(function ($surat) {
            return $this->makeItem([
                'id' => 'persuratan-draft-' . $surat->id,
                'module_key' => 'persuratan',
                'module_label' => 'Persuratan',
                'module_icon' => 'fas fa-envelope-open-text',
                'type_label' => 'Draft Surat Keluar',
                'title' => $surat->perihal ?: 'Draft surat keluar',
                'subtitle' => $surat->nomor_surat_formatted ?: 'Nomor surat belum final',
                'description' => 'Draft surat keluar belum diselesaikan.',
                'status_key' => 'process',
                'priority_key' => 'normal',
                'target_at' => $surat->tanggal_surat ?: $surat->created_at,
                'is_overdue' => optional($surat->created_at)->lt(now('Asia/Jayapura')->subDays(3)),
                'assignee_id' => $surat->created_by,
                'assignee_name' => optional($surat->creator)->name ?: '-',
                'action_url' => route('surat-keluar.index'),
                'action_text' => 'Buka Surat Keluar',
                'unit_label' => optional(optional($surat->creator)->unit)->nama ?: '-',
                'sort_at' => optional($surat->updated_at ?: $surat->created_at)->timestamp ?: 0,
            ]);
        }));

        if (Schema::hasColumn('surat_keluar_penerima', 'read_at')) {
            $taggedSuratKeluarQuery = SuratKeluar::with(['creator.unit'])
                ->whereHas('penerimaInternal', function ($penerimaQuery) use ($user) {
                    $penerimaQuery->whereIn('users.id', $user->effectiveAssignmentUserIds())
                        ->whereNull('surat_keluar_penerima.read_at');
                });

            $items = $items->merge($taggedSuratKeluarQuery->latest()->take(10)->get()->map(function ($surat) use ($user) {
                return $this->makeItem([
                    'id' => 'persuratan-surat-keluar-tagged-' . $surat->id,
                    'module_key' => 'persuratan',
                    'module_label' => 'Persuratan',
                    'module_icon' => 'fas fa-envelope-open-text',
                    'type_label' => 'Surat Keluar Baru',
                    'title' => $surat->perihal ?: 'Surat keluar',
                    'subtitle' => $surat->nomor_surat_formatted ?: $surat->nomor_surat,
                    'description' => 'Anda ditandai sebagai penerima surat keluar.',
                    'status_key' => 'waiting',
                    'priority_key' => 'normal',
                    'target_at' => $surat->tanggal_surat ?: $surat->created_at,
                    'is_overdue' => false,
                    'assignee_id' => $user->id,
                    'assignee_name' => $user->name,
                    'action_url' => route('surat-keluar.index'),
                    'action_text' => 'Buka Surat Keluar',
                    'unit_label' => optional(optional($surat->creator)->unit)->nama ?: '-',
                    'sort_at' => optional($surat->updated_at ?: $surat->created_at)->timestamp ?: 0,
                ]);
            }));
        }

        if ($user->isSuperAdmin() || $user->canApproveSuratKeluarTemplate()) {
            $approvalQuery = SuratKeluarApproval::with(['suratKeluar.creator.unit', 'requester.unit'])
                ->where('status', 'pending')
                ->whereIn('approver_id', $user->effectiveAssignmentUserIds());

            $items = $items->merge($approvalQuery->latest()->take(10)->get()->map(function ($approval) {
                return $this->makeItem([
                    'id' => 'persuratan-approval-' . $approval->id,
                    'module_key' => 'persuratan',
                    'module_label' => 'Persuratan',
                    'module_icon' => 'fas fa-envelope-open-text',
                    'type_label' => 'Approval Surat Keluar',
                    'title' => optional($approval->suratKeluar)->perihal ?: ($approval->template_name ?: 'Approval surat keluar'),
                    'subtitle' => optional($approval->requester)->name ?: 'Menunggu persetujuan',
                    'description' => 'Template surat keluar menunggu persetujuan.',
                    'status_key' => 'waiting',
                    'priority_key' => 'high',
                    'target_at' => optional($approval->created_at),
                    'is_overdue' => optional($approval->created_at)->lt(now('Asia/Jayapura')->subDays(2)),
                    'assignee_id' => $approval->approver_id,
                    'assignee_name' => optional($approval->approver)->name ?: '-',
                    'action_url' => route('surat-keluar.approval.show', $approval),
                    'action_text' => 'Buka Approval',
                    'unit_label' => optional(optional($approval->requester)->unit)->nama ?: '-',
                    'sort_at' => optional($approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
                ]);
            }));
        }

        return $items;
    }

    protected function buildMeetingItems(User $user)
    {
        if (!$user->canAccessMeetingModule()) {
            return collect();
        }

        $items = collect();

        if ($user->canAccessMeetingApproval()) {
            $rapatApprovalQuery = RapatApproval::with(['rapat.creator.unit'])
                ->where('status', 'pending')
                ->whereIn('approver_id', $user->effectiveAssignmentUserIds());

            $items = $items->merge($rapatApprovalQuery->latest()->take(10)->get()->map(function ($approval) {
                return $this->makeItem([
                    'id' => 'rapat-approval-' . $approval->id,
                    'module_key' => 'rapat',
                    'module_label' => 'Rapat / Agenda',
                    'module_icon' => 'fas fa-calendar-alt',
                    'type_label' => $approval->stage_label . ' Undangan',
                    'title' => optional($approval->rapat)->judul ?: 'Undangan rapat',
                    'subtitle' => optional($approval->rapat)->nomor_undangan ?: 'Menunggu approval',
                    'description' => 'Undangan rapat menunggu persetujuan.',
                    'status_key' => 'waiting',
                    'priority_key' => 'high',
                    'target_at' => optional($approval->created_at),
                    'is_overdue' => optional($approval->created_at)->lt(now('Asia/Jayapura')->subDays(2)),
                    'assignee_id' => $approval->approver_id,
                    'assignee_name' => optional($approval->approver)->name ?: '-',
                    'action_url' => route('rapat.approval.show', $approval),
                    'action_text' => 'Buka Approval',
                    'unit_label' => optional(optional(optional($approval->rapat)->creator)->unit)->nama ?: '-',
                    'sort_at' => optional($approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
                ]);
            }));

            $notulensiApprovalQuery = RapatNotulensiApproval::with(['notulensi.rapat.creator.unit'])
                ->where('status', 'pending')
                ->whereIn('approver_id', $user->effectiveAssignmentUserIds());

            $items = $items->merge($notulensiApprovalQuery->latest()->take(10)->get()->map(function ($approval) {
                return $this->makeItem([
                    'id' => 'rapat-notulensi-approval-' . $approval->id,
                    'module_key' => 'rapat',
                    'module_label' => 'Rapat / Agenda',
                    'module_icon' => 'fas fa-calendar-alt',
                    'type_label' => 'Approval Notulensi',
                    'title' => optional(optional($approval->notulensi)->rapat)->judul ?: 'Notulensi rapat',
                    'subtitle' => optional($approval->notulensi)->judul ?: 'Menunggu approval',
                    'description' => 'Notulensi menunggu persetujuan.',
                    'status_key' => 'waiting',
                    'priority_key' => 'high',
                    'target_at' => optional($approval->created_at),
                    'is_overdue' => optional($approval->created_at)->lt(now('Asia/Jayapura')->subDays(2)),
                    'assignee_id' => $approval->approver_id,
                    'assignee_name' => optional($approval->approver)->name ?: '-',
                    'action_url' => route('rapat.notulensi-approval.show', $approval),
                    'action_text' => 'Buka Approval',
                    'unit_label' => optional(optional(optional(optional($approval->notulensi)->rapat)->creator)->unit)->nama ?: '-',
                    'sort_at' => optional($approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
                ]);
            }));
        }

        $followUpQuery = RapatNotulensiTindakLanjut::with(['notulensi.rapat', 'user.unit'])
            ->whereIn('status', ['pending', 'process'])
            ->where('user_id', $user->id);

        $items = $items->merge($followUpQuery->latest()->take(12)->get()->map(function ($followUp) {
            $isProcess = $followUp->status === 'process';

            return $this->makeItem([
                'id' => 'rapat-follow-up-' . $followUp->id,
                'module_key' => 'rapat',
                'module_label' => 'Rapat / Agenda',
                'module_icon' => 'fas fa-calendar-alt',
                'type_label' => 'Tindak Lanjut Notulen',
                'title' => optional(optional($followUp->notulensi)->rapat)->judul ?: 'Tindak lanjut rapat',
                'subtitle' => $followUp->deskripsi_snapshot ?: 'Tindak lanjut menunggu penyelesaian',
                'description' => 'PIC: ' . (optional($followUp->user)->name ?: '-') . ' • ' . (optional(optional($followUp->user)->unit)->nama ?: '-'),
                'status_key' => $isProcess ? 'process' : 'waiting',
                'priority_key' => $isProcess ? 'normal' : 'high',
                'target_at' => optional($followUp->updated_at ?: $followUp->created_at),
                'is_overdue' => $followUp->status === 'pending' && optional($followUp->created_at)->lt(now('Asia/Jayapura')->subDays(3)),
                'assignee_id' => $followUp->user_id,
                'assignee_name' => optional($followUp->user)->name ?: '-',
                'action_url' => route('rapat.notulensi.follow-ups'),
                'action_text' => 'Buka Tindak Lanjut',
                'unit_label' => optional(optional($followUp->user)->unit)->nama ?: '-',
                'sort_at' => optional($followUp->updated_at ?: $followUp->created_at)->timestamp ?: 0,
            ]);
        }));

        return $items;
    }

    protected function buildLeaveItems(User $user)
    {
        if (!$user->canAccessLeaveModule() || !Schema::hasTable('leave_requests')) {
            return collect();
        }

        $items = collect();

        if ($user->canAccessLeaveApproval() || $user->isSuperAdmin()) {
            $approvalQuery = LeaveApproval::with(['leaveRequest.user.unit', 'leaveRequest.leaveType'])
                ->whereIn('status', ['waiting', 'pending'])
                ->whereIn('approver_id', $user->effectiveAssignmentUserIds());

            $items = $items->merge($approvalQuery->latest()->take(10)->get()->map(function ($approval) {
                return $this->makeItem([
                    'id' => 'cuti-approval-' . $approval->id,
                    'module_key' => 'cuti',
                    'module_label' => 'Cuti',
                    'module_icon' => 'fas fa-calendar-check',
                    'type_label' => 'Approval Cuti',
                    'title' => optional(optional($approval->leaveRequest)->user)->name ?: 'Pengajuan cuti',
                    'subtitle' => optional(optional($approval->leaveRequest)->leaveType)->name ?: 'Cuti',
                    'description' => $approval->role_label . ' • ' . optional($approval->leaveRequest)->period_label,
                    'status_key' => 'waiting',
                    'priority_key' => 'high',
                    'target_at' => optional($approval->created_at),
                    'is_overdue' => optional($approval->created_at)->lt(now('Asia/Jayapura')->subDays(2)),
                    'assignee_id' => $approval->approver_id,
                    'assignee_name' => optional($approval->approver)->name ?: '-',
                    'action_url' => route('cuti.approval.show', $approval),
                    'action_text' => 'Buka Approval',
                    'unit_label' => optional(optional(optional($approval->leaveRequest)->user)->unit)->nama ?: '-',
                    'sort_at' => optional($approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
                ]);
            }));
        }

        $revisionQuery = LeaveRequest::with(['leaveType', 'user.unit'])
            ->where('user_id', $user->id)
            ->where('status', LeaveRequest::STATUS_REJECTED);

        $items = $items->merge($revisionQuery->latest()->take(8)->get()->map(function ($leaveRequest) {
            return $this->makeItem([
                'id' => 'cuti-revision-' . $leaveRequest->id,
                'module_key' => 'cuti',
                'module_label' => 'Cuti',
                'module_icon' => 'fas fa-calendar-check',
                'type_label' => 'Perbaikan Pengajuan',
                'title' => optional($leaveRequest->leaveType)->name ?: 'Pengajuan cuti',
                'subtitle' => $leaveRequest->display_number,
                'description' => 'Pengajuan cuti perlu diperbaiki dan diajukan kembali.',
                'status_key' => 'revision',
                'priority_key' => 'normal',
                'target_at' => optional($leaveRequest->updated_at ?: $leaveRequest->created_at),
                'is_overdue' => optional($leaveRequest->updated_at ?: $leaveRequest->created_at)->lt(now('Asia/Jayapura')->subDays(3)),
                'assignee_id' => $leaveRequest->user_id,
                'assignee_name' => optional($leaveRequest->user)->name ?: '-',
                'action_url' => route('cuti.show', $leaveRequest),
                'action_text' => 'Buka Pengajuan',
                'unit_label' => optional(optional($leaveRequest->user)->unit)->nama ?: '-',
                'sort_at' => optional($leaveRequest->updated_at ?: $leaveRequest->created_at)->timestamp ?: 0,
            ]);
        }));

        return $items;
    }

    protected function buildProgressZiItems(User $user)
    {
        if (!$user->canAccessProgressZiModule() || !Schema::hasTable('zi_periods')) {
            return collect();
        }

        $items = collect();

        if ($user->canVerifyProgressZi() || $user->isSuperAdmin()) {
            $approvalQuery = ZiActivityApproval::with(['activity.area', 'requester.unit'])
                ->where('status', 'pending')
                ->whereIn('approver_id', $user->effectiveAssignmentUserIds());

            $items = $items->merge($approvalQuery->latest('requested_at')->take(10)->get()->map(function ($approval) {
                return $this->makeItem([
                    'id' => 'zi-approval-' . $approval->id,
                    'module_key' => 'progress_zi',
                    'module_label' => 'Progress ZI',
                    'module_icon' => 'fas fa-chart-line',
                    'type_label' => 'Review Pimpinan',
                    'title' => optional($approval->activity)->name ?: 'Aktivitas ZI',
                    'subtitle' => optional(optional($approval->activity)->area)->name ?: 'Progress ZI',
                    'description' => 'Review kegiatan ZI menunggu persetujuan pimpinan.',
                    'status_key' => 'waiting',
                    'priority_key' => 'high',
                    'target_at' => $approval->requested_at ?: $approval->created_at,
                    'is_overdue' => optional($approval->requested_at ?: $approval->created_at)->lt(now('Asia/Jayapura')->subDays(2)),
                    'assignee_id' => $approval->approver_id,
                    'assignee_name' => optional($approval->approver)->name ?: '-',
                    'action_url' => route('progress-zi.approvals.show', $approval),
                    'action_text' => 'Buka Review',
                    'unit_label' => optional(optional($approval->requester)->unit)->nama ?: '-',
                    'sort_at' => optional($approval->requested_at ?: $approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
                ]);
            }));
        }

        $revisionQuery = ZiActivityApproval::with(['activity.area', 'activity.pic.unit'])
            ->where('status', 'rejected')
            ->where('requested_by', $user->id);

        $items = $items->merge($revisionQuery->latest('acted_at')->take(8)->get()->map(function ($approval) {
            return $this->makeItem([
                'id' => 'zi-revision-' . $approval->id,
                'module_key' => 'progress_zi',
                'module_label' => 'Progress ZI',
                'module_icon' => 'fas fa-chart-line',
                'type_label' => 'Perbaikan Eviden / Kegiatan',
                'title' => optional($approval->activity)->name ?: 'Aktivitas ZI',
                'subtitle' => optional(optional($approval->activity)->area)->name ?: 'Progress ZI',
                'description' => $approval->review_notes ?: 'Perlu perbaikan sebelum diajukan ulang.',
                'status_key' => 'revision',
                'priority_key' => 'normal',
                'target_at' => $approval->acted_at ?: $approval->updated_at,
                'is_overdue' => optional($approval->acted_at ?: $approval->updated_at)->lt(now('Asia/Jayapura')->subDays(3)),
                'assignee_id' => $approval->requested_by,
                'assignee_name' => optional($approval->requester)->name ?: optional(optional($approval->activity)->pic)->name ?: '-',
                'action_url' => route('progress-zi.activities.index'),
                'action_text' => 'Buka Monitoring',
                'unit_label' => optional(optional(optional($approval->activity)->pic)->unit)->nama ?: '-',
                'sort_at' => optional($approval->acted_at ?: $approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
            ]);
        }));

        $activities = ZiActivity::with(['area.pics', 'pic.unit'])
            ->whereNotIn('status', ['sudah_terlaksana', 'selesai'])
            ->whereDate('target_end_date', '<', now('Asia/Jayapura')->toDateString())
            ->where(function ($query) use ($user) {
                $query->where('pic_user_id', $user->id)
                    ->orWhereHas('area.pics', function ($areaQuery) use ($user) {
                        $areaQuery->where('users.id', $user->id);
                    })
                    ->orWhereHas('area', function ($areaQuery) use ($user) {
                        $areaQuery->where('pic_user_id', $user->id);
                    });
            });

        $items = $items->merge($activities->orderBy('target_end_date')->take(8)->get()->map(function ($activity) use ($user) {
            return $this->makeItem([
                'id' => 'zi-overdue-' . $activity->id,
                'module_key' => 'progress_zi',
                'module_label' => 'Progress ZI',
                'module_icon' => 'fas fa-chart-line',
                'type_label' => 'Kegiatan Overdue',
                'title' => $activity->name,
                'subtitle' => optional($activity->area)->name ?: 'Progress ZI',
                'description' => 'Target berakhir ' . optional($activity->target_end_date)->translatedFormat('d F Y'),
                'status_key' => 'overdue',
                'priority_key' => 'high',
                'target_at' => $activity->target_end_date,
                'is_overdue' => true,
                'assignee_id' => $activity->pic_user_id ?: $user->id,
                'assignee_name' => optional($activity->pic)->name ?: $user->name,
                'action_url' => route('progress-zi.activities.index'),
                'action_text' => 'Buka Monitoring',
                'unit_label' => optional(optional($activity->pic)->unit)->nama ?: '-',
                'sort_at' => optional($activity->target_end_date)->timestamp ?: 0,
            ]);
        }));

        return $items;
    }

    protected function buildInventoryItems(User $user)
    {
        if (!$user->canAccessInventoryModule() || !Schema::hasTable('inventory_maintenance_transactions')) {
            return collect();
        }

        $items = collect();

        $draftQuery = InventoryMaintenanceTransaction::with(['item', 'detail.room', 'creator'])
            ->where('status', 'draft')
            ->where('created_by', $user->id);

        $items = $items->merge($draftQuery->latest()->take(10)->get()->map(function ($transaction) {
            return $this->makeItem([
                'id' => 'perawatan-draft-' . $transaction->id,
                'module_key' => 'perawatan',
                'module_label' => 'Perawatan Alat dan Mesin',
                'module_icon' => 'fas fa-tools',
                'type_label' => 'Transaksi Draft',
                'title' => optional($transaction->item)->name ?: 'Transaksi perawatan',
                'subtitle' => optional($transaction->detail)->sub_code ?: '-',
                'description' => $transaction->description ?: 'Transaksi perawatan belum diselesaikan.',
                'status_key' => 'process',
                'priority_key' => 'normal',
                'target_at' => $transaction->transaction_date ?: $transaction->created_at,
                'is_overdue' => optional($transaction->transaction_date ?: $transaction->created_at)->lt(now('Asia/Jayapura')->subDays(5)),
                'assignee_id' => $transaction->created_by,
                'assignee_name' => optional($transaction->creator)->name ?: '-',
                'action_url' => $transaction->detail ? route('perawatan-alat-mesin.maintenance.show', $transaction->detail) : route('perawatan-alat-mesin.maintenance.index'),
                'action_text' => 'Buka Histori',
                'unit_label' => optional(optional($transaction->detail)->room)->name ?: '-',
                'sort_at' => optional($transaction->updated_at ?: $transaction->created_at)->timestamp ?: 0,
            ]);
        }));

        $attachmentQuery = InventoryMaintenanceTransaction::with(['item', 'detail.room'])
            ->withCount('attachments')
            ->where('status', 'completed')
            ->where('created_by', $user->id)
            ->having('attachments_count', '=', 0);

        $items = $items->merge($attachmentQuery->latest()->take(10)->get()->map(function ($transaction) {
            return $this->makeItem([
                'id' => 'perawatan-lampiran-' . $transaction->id,
                'module_key' => 'perawatan',
                'module_label' => 'Perawatan Alat dan Mesin',
                'module_icon' => 'fas fa-tools',
                'type_label' => 'Lampiran Belum Lengkap',
                'title' => optional($transaction->item)->name ?: 'Transaksi perawatan',
                'subtitle' => optional($transaction->detail)->sub_code ?: '-',
                'description' => 'Transaksi selesai tetapi lampiran belum diunggah.',
                'status_key' => 'revision',
                'priority_key' => 'normal',
                'target_at' => $transaction->transaction_date ?: $transaction->created_at,
                'is_overdue' => optional($transaction->transaction_date ?: $transaction->created_at)->lt(now('Asia/Jayapura')->subDays(3)),
                'assignee_id' => $transaction->created_by,
                'assignee_name' => optional($transaction->creator)->name ?: '-',
                'action_url' => $transaction->detail ? route('perawatan-alat-mesin.maintenance.show', $transaction->detail) : route('perawatan-alat-mesin.maintenance.index'),
                'action_text' => 'Lengkapi Lampiran',
                'unit_label' => optional(optional($transaction->detail)->room)->name ?: '-',
                'sort_at' => optional($transaction->updated_at ?: $transaction->created_at)->timestamp ?: 0,
            ]);
        }));

        return $items;
    }

    protected function buildHistoricalPersuratanItems(User $user)
    {
        if (!$user->canAccessPersuratanMenu()) {
            return collect();
        }

        $query = SuratKeluarApproval::with(['suratKeluar', 'requester.unit'])
            ->whereIn('status', ['approved', 'rejected'])
            ->where(function ($builder) use ($user) {
                $builder->whereIn('approver_id', $user->effectiveAssignmentUserIds())
                    ->orWhere('requested_by', $user->id);
            });

        return $query->latest('acted_at')->take(20)->get()->map(function ($approval) {
            $isApproved = $approval->status === 'approved';

            return $this->makeItem([
                'id' => 'persuratan-history-' . $approval->id,
                'module_key' => 'persuratan',
                'module_label' => 'Persuratan',
                'module_icon' => 'fas fa-envelope-open-text',
                'type_label' => $isApproved ? 'Approval Surat Selesai' : 'Approval Surat Ditolak',
                'title' => optional($approval->suratKeluar)->perihal ?: ($approval->template_name ?: 'Surat keluar'),
                'subtitle' => optional($approval->requester)->name ?: 'Persuratan',
                'description' => $isApproved ? 'Dokumen surat keluar telah disetujui.' : 'Dokumen surat keluar ditolak dan perlu tindak lanjut.',
                'status_key' => $isApproved ? 'done' : 'revision',
                'priority_key' => 'low',
                'target_at' => $approval->acted_at ?: $approval->updated_at,
                'is_overdue' => false,
                'assignee_id' => $approval->requested_by,
                'assignee_name' => optional($approval->requester)->name ?: '-',
                'action_url' => route('surat-keluar.approval.show', $approval),
                'action_text' => 'Lihat Detail',
                'unit_label' => optional(optional($approval->requester)->unit)->nama ?: '-',
                'sort_at' => optional($approval->acted_at ?: $approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
            ]);
        });
    }

    protected function buildHistoricalMeetingItems(User $user)
    {
        if (!$user->canAccessMeetingModule()) {
            return collect();
        }

        $items = collect();

        if ($user->canAccessMeetingApproval()) {
            $rapatApprovalQuery = RapatApproval::with(['rapat.creator.unit'])
                ->whereIn('status', ['approved', 'rejected'])
                ->whereIn('approver_id', $user->effectiveAssignmentUserIds());

            $items = $items->merge($rapatApprovalQuery->latest('acted_at')->take(15)->get()->map(function ($approval) {
                $isApproved = $approval->status === 'approved';

                return $this->makeItem([
                    'id' => 'rapat-history-approval-' . $approval->id,
                    'module_key' => 'rapat',
                    'module_label' => 'Rapat / Agenda',
                    'module_icon' => 'fas fa-calendar-alt',
                    'type_label' => $isApproved ? 'Approval Undangan Selesai' : 'Approval Undangan Ditolak',
                    'title' => optional($approval->rapat)->judul ?: 'Undangan rapat',
                    'subtitle' => optional($approval->rapat)->nomor_undangan ?: '-',
                    'description' => $isApproved ? 'Undangan rapat telah disetujui.' : 'Undangan rapat ditolak.',
                    'status_key' => $isApproved ? 'done' : 'revision',
                    'priority_key' => 'low',
                    'target_at' => $approval->acted_at ?: $approval->updated_at,
                    'is_overdue' => false,
                    'assignee_id' => $approval->approver_id,
                    'assignee_name' => optional($approval->approver)->name ?: '-',
                    'action_url' => route('rapat.approval.show', $approval),
                    'action_text' => 'Lihat Detail',
                    'unit_label' => optional(optional(optional($approval->rapat)->creator)->unit)->nama ?: '-',
                    'sort_at' => optional($approval->acted_at ?: $approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
                ]);
            }));

            $notulensiApprovalQuery = RapatNotulensiApproval::with(['notulensi.rapat.creator.unit'])
                ->whereIn('status', ['approved', 'rejected'])
                ->whereIn('approver_id', $user->effectiveAssignmentUserIds());

            $items = $items->merge($notulensiApprovalQuery->latest('acted_at')->take(15)->get()->map(function ($approval) {
                $isApproved = $approval->status === 'approved';

                return $this->makeItem([
                    'id' => 'rapat-history-notulensi-' . $approval->id,
                    'module_key' => 'rapat',
                    'module_label' => 'Rapat / Agenda',
                    'module_icon' => 'fas fa-calendar-alt',
                    'type_label' => $isApproved ? 'Approval Notulensi Selesai' : 'Approval Notulensi Ditolak',
                    'title' => optional(optional($approval->notulensi)->rapat)->judul ?: 'Notulensi rapat',
                    'subtitle' => optional($approval->notulensi)->judul ?: '-',
                    'description' => $isApproved ? 'Notulensi telah disetujui.' : 'Notulensi ditolak.',
                    'status_key' => $isApproved ? 'done' : 'revision',
                    'priority_key' => 'low',
                    'target_at' => $approval->acted_at ?: $approval->updated_at,
                    'is_overdue' => false,
                    'assignee_id' => $approval->approver_id,
                    'assignee_name' => optional($approval->approver)->name ?: '-',
                    'action_url' => route('rapat.notulensi-approval.show', $approval),
                    'action_text' => 'Lihat Detail',
                    'unit_label' => optional(optional(optional(optional($approval->notulensi)->rapat)->creator)->unit)->nama ?: '-',
                    'sort_at' => optional($approval->acted_at ?: $approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
                ]);
            }));
        }

        $followUpQuery = RapatNotulensiTindakLanjut::with(['notulensi.rapat', 'user.unit'])
            ->where('status', 'completed')
            ->where('user_id', $user->id);

        $items = $items->merge($followUpQuery->latest('completed_at')->take(15)->get()->map(function ($followUp) {
            return $this->makeItem([
                'id' => 'rapat-history-followup-' . $followUp->id,
                'module_key' => 'rapat',
                'module_label' => 'Rapat / Agenda',
                'module_icon' => 'fas fa-calendar-alt',
                'type_label' => 'Tindak Lanjut Selesai',
                'title' => optional(optional($followUp->notulensi)->rapat)->judul ?: 'Tindak lanjut rapat',
                'subtitle' => $followUp->deskripsi_snapshot ?: '-',
                'description' => 'Tindak lanjut notulen telah diselesaikan.',
                'status_key' => 'done',
                'priority_key' => 'low',
                'target_at' => $followUp->completed_at ?: $followUp->updated_at,
                'is_overdue' => false,
                'assignee_id' => $followUp->user_id,
                'assignee_name' => optional($followUp->user)->name ?: '-',
                'action_url' => route('rapat.notulensi.follow-ups'),
                'action_text' => 'Lihat Tindak Lanjut',
                'unit_label' => optional(optional($followUp->user)->unit)->nama ?: '-',
                'sort_at' => optional($followUp->completed_at ?: $followUp->updated_at ?: $followUp->created_at)->timestamp ?: 0,
            ]);
        }));

        return $items;
    }

    protected function buildHistoricalLeaveItems(User $user)
    {
        if (!$user->canAccessLeaveModule() || !Schema::hasTable('leave_requests')) {
            return collect();
        }

        $query = LeaveApproval::with(['leaveRequest.user.unit', 'leaveRequest.leaveType'])
            ->whereIn('status', ['approved', 'rejected'])
            ->where(function ($builder) use ($user) {
                $builder->whereIn('approver_id', $user->effectiveAssignmentUserIds())
                    ->orWhereHas('leaveRequest', function ($leaveQuery) use ($user) {
                        $leaveQuery->where('user_id', $user->id);
                    });
            });

        return $query->latest('acted_at')->take(20)->get()->map(function ($approval) {
            $isApproved = $approval->status === 'approved';

            return $this->makeItem([
                'id' => 'cuti-history-' . $approval->id,
                'module_key' => 'cuti',
                'module_label' => 'Cuti',
                'module_icon' => 'fas fa-calendar-check',
                'type_label' => $isApproved ? 'Approval Cuti Selesai' : 'Approval Cuti Ditolak',
                'title' => optional(optional($approval->leaveRequest)->user)->name ?: 'Pengajuan cuti',
                'subtitle' => optional(optional($approval->leaveRequest)->leaveType)->name ?: 'Cuti',
                'description' => $isApproved ? 'Pengajuan cuti telah disetujui.' : 'Pengajuan cuti ditolak.',
                'status_key' => $isApproved ? 'done' : 'revision',
                'priority_key' => 'low',
                'target_at' => $approval->acted_at ?: $approval->updated_at,
                'is_overdue' => false,
                'assignee_id' => $approval->approver_id ?: optional($approval->leaveRequest)->user_id,
                'assignee_name' => optional($approval->approver)->name ?: optional(optional($approval->leaveRequest)->user)->name ?: '-',
                'action_url' => route('cuti.show', $approval->leaveRequest),
                'action_text' => 'Lihat Pengajuan',
                'unit_label' => optional(optional(optional($approval->leaveRequest)->user)->unit)->nama ?: '-',
                'sort_at' => optional($approval->acted_at ?: $approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
            ]);
        });
    }

    protected function buildHistoricalProgressZiItems(User $user)
    {
        if (!$user->canAccessProgressZiModule() || !Schema::hasTable('zi_activity_approvals')) {
            return collect();
        }

        $query = ZiActivityApproval::with(['activity.area', 'activity.pic.unit', 'requester.unit'])
            ->whereIn('status', ['approved', 'rejected'])
            ->where(function ($builder) use ($user) {
                $builder->whereIn('approver_id', $user->effectiveAssignmentUserIds())
                    ->orWhere('requested_by', $user->id);
            });

        return $query->latest('acted_at')->take(20)->get()->map(function ($approval) {
            $isApproved = $approval->status === 'approved';

            return $this->makeItem([
                'id' => 'zi-history-' . $approval->id,
                'module_key' => 'progress_zi',
                'module_label' => 'Progress ZI',
                'module_icon' => 'fas fa-chart-line',
                'type_label' => $isApproved ? 'Review Pimpinan Selesai' : 'Review Pimpinan Perlu Revisi',
                'title' => optional($approval->activity)->name ?: 'Aktivitas ZI',
                'subtitle' => optional(optional($approval->activity)->area)->name ?: 'Progress ZI',
                'description' => $isApproved ? 'Kegiatan ZI telah disetujui pimpinan.' : ($approval->review_notes ?: 'Kegiatan ZI perlu diperbaiki.'),
                'status_key' => $isApproved ? 'done' : 'revision',
                'priority_key' => 'low',
                'target_at' => $approval->acted_at ?: $approval->updated_at,
                'is_overdue' => false,
                'assignee_id' => $approval->requested_by,
                'assignee_name' => optional($approval->requester)->name ?: optional(optional($approval->activity)->pic)->name ?: '-',
                'action_url' => route('progress-zi.approvals.show', $approval),
                'action_text' => 'Lihat Review',
                'unit_label' => optional(optional($approval->requester)->unit)->nama ?: optional(optional(optional($approval->activity)->pic)->unit)->nama ?: '-',
                'sort_at' => optional($approval->acted_at ?: $approval->updated_at ?: $approval->created_at)->timestamp ?: 0,
            ]);
        });
    }

    protected function buildHistoricalInventoryItems(User $user)
    {
        if (!$user->canAccessInventoryModule() || !Schema::hasTable('inventory_maintenance_transactions')) {
            return collect();
        }

        $query = InventoryMaintenanceTransaction::with(['item', 'detail.room'])
            ->where('status', 'completed')
            ->where('created_by', $user->id);

        return $query->latest('updated_at')->take(20)->get()->map(function ($transaction) {
            return $this->makeItem([
                'id' => 'perawatan-history-' . $transaction->id,
                'module_key' => 'perawatan',
                'module_label' => 'Perawatan Alat dan Mesin',
                'module_icon' => 'fas fa-tools',
                'type_label' => 'Perawatan Selesai',
                'title' => optional($transaction->item)->name ?: 'Transaksi perawatan',
                'subtitle' => optional($transaction->detail)->sub_code ?: '-',
                'description' => $transaction->description ?: 'Transaksi perawatan telah diselesaikan.',
                'status_key' => 'done',
                'priority_key' => 'low',
                'target_at' => $transaction->updated_at ?: $transaction->transaction_date,
                'is_overdue' => false,
                'assignee_id' => $transaction->created_by,
                'assignee_name' => optional($transaction->creator)->name ?: '-',
                'action_url' => $transaction->detail ? route('perawatan-alat-mesin.maintenance.show', $transaction->detail) : route('perawatan-alat-mesin.maintenance.index'),
                'action_text' => 'Lihat Histori',
                'unit_label' => optional(optional($transaction->detail)->room)->name ?: '-',
                'sort_at' => optional($transaction->updated_at ?: $transaction->created_at)->timestamp ?: 0,
            ]);
        });
    }

    protected function makeItem(array $item)
    {
        return $item;
    }
}
