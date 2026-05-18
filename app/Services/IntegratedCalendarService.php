<?php

namespace App\Services;

use App\AgendaPimpinan;
use App\LeaveRequest;
use App\Rapat;
use App\SuratKeluar;
use App\User;
use App\ZiActivity;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class IntegratedCalendarService
{
    public function build(User $user, array $filters = [])
    {
        $filters = $this->normalizeFilters($filters);

        $events = collect();

        if (in_array('rapat', $filters['modules'], true)) {
            $events = $events->merge($this->buildRapatEvents($user, $filters));
        }

        if (in_array('agenda_pimpinan', $filters['modules'], true)) {
            $events = $events->merge($this->buildAgendaEvents($user, $filters));
        }

        if (in_array('cuti', $filters['modules'], true)) {
            $events = $events->merge($this->buildLeaveEvents($user, $filters));
        }

        if (in_array('zi', $filters['modules'], true)) {
            $events = $events->merge($this->buildZiEvents($user, $filters));
        }

        if (in_array('surat_tugas', $filters['modules'], true)) {
            $events = $events->merge($this->buildSuratTugasEvents($user, $filters));
        }

        if (!empty($filters['status'])) {
            $events = $events->where('status_key', $filters['status'])->values();
        }

        $events = $events->sortBy(function ($event) {
            return $event['sort_ts'];
        })->values();

        return [
            'events' => $events->map(function ($event) {
                unset($event['sort_ts'], $event['date_keys']);

                return $event;
            })->values()->all(),
            'meta' => [
                'counts' => [
                    'all' => $events->count(),
                    'rapat' => $events->where('module_key', 'rapat')->count(),
                    'agenda_pimpinan' => $events->where('module_key', 'agenda_pimpinan')->count(),
                    'cuti' => $events->where('module_key', 'cuti')->count(),
                    'zi' => $events->where('module_key', 'zi')->count(),
                    'surat_tugas' => $events->where('module_key', 'surat_tugas')->count(),
                ],
                'conflicts' => $this->buildConflicts($events),
                'upcoming' => $this->buildUpcoming($events),
            ],
        ];
    }

    protected function normalizeFilters(array $filters)
    {
        $start = !empty($filters['start']) ? Carbon::parse($filters['start'], 'Asia/Jayapura')->startOfDay() : now('Asia/Jayapura')->startOfMonth();
        $end = !empty($filters['end']) ? Carbon::parse($filters['end'], 'Asia/Jayapura')->endOfDay() : now('Asia/Jayapura')->endOfMonth();

        $modules = collect($filters['modules'] ?? ['rapat', 'agenda_pimpinan', 'cuti', 'zi', 'surat_tugas'])
            ->filter(function ($module) {
                return in_array($module, ['rapat', 'agenda_pimpinan', 'cuti', 'zi', 'surat_tugas'], true);
            })
            ->unique()
            ->values()
            ->all();

        if (empty($modules)) {
            $modules = ['rapat', 'agenda_pimpinan', 'cuti', 'zi', 'surat_tugas'];
        }

        return [
            'start' => $start,
            'end' => $end,
            'scope' => ($filters['scope'] ?? 'all') === 'mine' ? 'mine' : 'all',
            'modules' => $modules,
            'unit_id' => !empty($filters['unit_id']) ? (int) $filters['unit_id'] : null,
            'status' => !empty($filters['status']) && in_array($filters['status'], ['dijadwalkan', 'berjalan', 'selesai', 'tertunda', 'overdue'], true)
                ? $filters['status']
                : null,
        ];
    }

    protected function buildRapatEvents(User $user, array $filters)
    {
        $query = Rapat::visibleTo($user)
            ->with(['creator.unit', 'creator.bidang', 'kategoriRapat', 'approvals', 'pesertas'])
            ->whereBetween('tanggal', [$filters['start']->toDateString(), $filters['end']->toDateString()]);

        if ($filters['scope'] === 'mine' && ($user->canManageRapat() || $user->isSuperAdmin())) {
            $query->where(function ($builder) use ($user) {
                $builder->where('created_by', $user->id)
                    ->orWhere('approver_1_id', $user->id)
                    ->orWhere('approver_2_id', $user->id)
                    ->orWhereHas('pesertas', function ($participantQuery) use ($user) {
                        $participantQuery->where('users.id', $user->id);
                    });
            });
        }

        if ($filters['unit_id']) {
            $query->whereHas('creator', function ($creatorQuery) use ($filters) {
                $creatorQuery->where('unit_id', $filters['unit_id']);
            });
        }

        return $query->get()->map(function ($rapat) {
            $start = Carbon::parse($rapat->tanggal->format('Y-m-d') . ' ' . ($rapat->waktu_mulai ?: '08:00:00'), 'Asia/Jayapura');
            $end = (clone $start)->addHour();
            $statusKey = $this->resolveMeetingStatus($rapat->display_status_key, $rapat->tanggal, $rapat->tanggal);
            $participants = $this->implodeNames($rapat->pesertas->pluck('name')->all());

            return $this->makeEvent([
                'id' => 'rapat-' . $rapat->id,
                'module_key' => 'rapat',
                'module_label' => 'Rapat',
                'source_key' => 'rapat',
                'title' => $rapat->judul,
                'start' => $start,
                'end' => $end,
                'allDay' => false,
                'status_key' => $statusKey,
                'status_label' => $this->statusLabel($statusKey),
                'color' => '#2563eb',
                'textColor' => '#ffffff',
                'url' => route('rapat.index'),
                'meta' => [
                    'kategori' => optional($rapat->kategoriRapat)->nama ?: 'Rapat',
                    'unit' => optional(optional($rapat->creator)->unit)->nama ?: '-',
                    'pic' => optional($rapat->creator)->name ?: '-',
                    'location' => $rapat->tempat ?: '-',
                    'time' => $rapat->waktu_mulai_formatted !== '-' ? $rapat->waktu_mulai_formatted . ' WIT' : '-',
                    'participants' => $participants,
                    'description' => $rapat->deskripsi ?: 'Agenda rapat internal.',
                ],
            ]);
        });
    }

    protected function buildAgendaEvents(User $user, array $filters)
    {
        $query = $this->visibleAgendaQuery($user)
            ->with(['creator.unit', 'recipients'])
            ->whereBetween('tanggal_kegiatan', [$filters['start']->toDateString(), $filters['end']->toDateString()]);

        if ($filters['scope'] === 'mine' && ($user->isSuperAdmin() || $user->canAccessAgendaPimpinan())) {
            $query->where(function ($builder) use ($user) {
                $builder->where('created_by', $user->id)
                    ->orWhereHas('recipients', function ($recipientQuery) use ($user) {
                        $recipientQuery->where('users.id', $user->id);
                    });
            });
        }

        if ($filters['unit_id']) {
            $query->whereHas('creator', function ($creatorQuery) use ($filters) {
                $creatorQuery->where('unit_id', $filters['unit_id']);
            });
        }

        return $query->get()->map(function ($agenda) use ($user) {
            $start = Carbon::parse($agenda->tanggal_kegiatan->format('Y-m-d') . ' ' . ($agenda->waktu ?: '08:00:00'), 'Asia/Jayapura');
            $end = (clone $start)->addHour();
            $statusKey = $this->resolveTimeStatus($agenda->tanggal_kegiatan, $agenda->tanggal_kegiatan);
            $participants = $this->implodeNames($agenda->recipients->pluck('name')->all());

            return $this->makeEvent([
                'id' => 'agenda-' . $agenda->id,
                'module_key' => 'agenda_pimpinan',
                'module_label' => 'Agenda Pimpinan',
                'source_key' => 'agenda',
                'title' => $agenda->judul_agenda,
                'start' => $start,
                'end' => $end,
                'allDay' => false,
                'status_key' => $statusKey,
                'status_label' => $this->statusLabel($statusKey),
                'color' => '#64748b',
                'textColor' => '#ffffff',
                'url' => ($user->isSuperAdmin() || $user->canAccessAgendaPimpinan()) ? route('rapat.agenda.index') : null,
                'meta' => [
                    'kategori' => 'Agenda Pimpinan',
                    'unit' => optional(optional($agenda->creator)->unit)->nama ?: '-',
                    'pic' => optional($agenda->creator)->name ?: '-',
                    'location' => $agenda->tempat ?: '-',
                    'time' => $agenda->waktu_formatted !== '-' ? $agenda->waktu_formatted . ' WIT' : '-',
                    'participants' => $participants,
                    'description' => $agenda->catatan ?: ('Yang menghadiri: ' . ($agenda->yang_menghadiri ?: '-')),
                ],
            ]);
        });
    }

    protected function buildLeaveEvents(User $user, array $filters)
    {
        $query = LeaveRequest::with(['user.unit', 'leaveType'])
            ->whereDate('start_date', '<=', $filters['end']->toDateString())
            ->whereDate('end_date', '>=', $filters['start']->toDateString());

        if (!$user->isSuperAdmin() && !$user->canApproveLeave()) {
            $query->where('user_id', $user->id);
        }

        if ($filters['scope'] === 'mine') {
            $query->where('user_id', $user->id);
        }

        if ($filters['unit_id']) {
            $query->whereHas('user', function ($userQuery) use ($filters) {
                $userQuery->where('unit_id', $filters['unit_id']);
            });
        }

        return $query->get()->map(function ($leave) {
            $startDate = Carbon::parse($leave->start_date, 'Asia/Jayapura')->startOfDay();
            $endDate = Carbon::parse($leave->end_date, 'Asia/Jayapura')->endOfDay();
            $statusKey = $this->resolveLeaveStatus($leave);

            return $this->makeEvent([
                'id' => 'cuti-' . $leave->id,
                'module_key' => 'cuti',
                'module_label' => 'Cuti',
                'source_key' => 'cuti',
                'title' => ($leave->leaveType ? $leave->leaveType->name : 'Cuti') . ' - ' . optional($leave->user)->name,
                'start' => $startDate,
                'end' => (clone $endDate)->addDay()->startOfDay(),
                'allDay' => true,
                'status_key' => $statusKey,
                'status_label' => $this->statusLabel($statusKey),
                'color' => '#dc2626',
                'textColor' => '#ffffff',
                'url' => route('cuti.show', $leave),
                'meta' => [
                    'kategori' => optional($leave->leaveType)->name ?: 'Cuti',
                    'unit' => optional(optional($leave->user)->unit)->nama ?: ($leave->unit_snapshot ?: '-'),
                    'pic' => optional($leave->user)->name ?: '-',
                    'location' => $leave->leave_address ?: '-',
                    'time' => $leave->period_label,
                    'participants' => '-',
                    'description' => $leave->purpose ?: 'Pengajuan cuti pegawai.',
                ],
            ]);
        });
    }

    protected function buildZiEvents(User $user, array $filters)
    {
        $query = ZiActivity::with(['area.pics', 'area.pic', 'pic', 'pic.unit', 'latestApproval'])
            ->where(function ($dateQuery) {
                $dateQuery->whereNotNull('target_start_date')
                    ->orWhereNotNull('target_end_date');
            })
            ->where(function ($rangeQuery) use ($filters) {
                $rangeQuery->whereBetween('target_start_date', [$filters['start']->toDateString(), $filters['end']->toDateString()])
                    ->orWhereBetween('target_end_date', [$filters['start']->toDateString(), $filters['end']->toDateString()])
                    ->orWhere(function ($overlapQuery) use ($filters) {
                        $overlapQuery->whereDate('target_start_date', '<=', $filters['start']->toDateString())
                            ->whereDate('target_end_date', '>=', $filters['end']->toDateString());
                    });
            });

        $activePeriodId = optional(\App\ZiPeriod::where('is_active', true)->latest('year')->first())->id;
        if ($activePeriodId) {
            $query->where('zi_period_id', $activePeriodId);
        }

        if (!$user->canManageProgressZiMasterData()) {
            $query->where(function ($activityQuery) use ($user) {
                $activityQuery->where('pic_user_id', $user->id)
                    ->orWhereHas('area', function ($areaQuery) use ($user) {
                        $areaQuery->where('pic_user_id', $user->id)
                            ->orWhereHas('pics', function ($picsQuery) use ($user) {
                                $picsQuery->where('users.id', $user->id);
                            });
                    });
            });
        }

        if ($filters['scope'] === 'mine') {
            $query->where(function ($activityQuery) use ($user) {
                $activityQuery->where('pic_user_id', $user->id)
                    ->orWhereHas('area', function ($areaQuery) use ($user) {
                        $areaQuery->where('pic_user_id', $user->id)
                            ->orWhereHas('pics', function ($picsQuery) use ($user) {
                                $picsQuery->where('users.id', $user->id);
                            });
                    });
            });
        }

        if ($filters['unit_id']) {
            $query->whereHas('pic', function ($picQuery) use ($filters) {
                $picQuery->where('unit_id', $filters['unit_id']);
            });
        }

        return $query->get()->map(function ($activity) {
            $startDate = Carbon::parse($activity->target_start_date ?: $activity->target_end_date, 'Asia/Jayapura')->startOfDay();
            $endDate = Carbon::parse($activity->target_end_date ?: $activity->target_start_date, 'Asia/Jayapura')->endOfDay();
            $statusKey = $this->resolveZiStatus($activity);

            return $this->makeEvent([
                'id' => 'zi-' . $activity->id,
                'module_key' => 'zi',
                'module_label' => 'ZI',
                'source_key' => 'zi',
                'title' => $activity->name,
                'start' => $startDate,
                'end' => (clone $endDate)->addDay()->startOfDay(),
                'allDay' => true,
                'status_key' => $statusKey,
                'status_label' => $this->statusLabel($statusKey),
                'color' => '#d97706',
                'textColor' => '#ffffff',
                'url' => route('progress-zi.activities.show', $activity),
                'meta' => [
                    'kategori' => optional($activity->area)->name ?: 'Progress ZI',
                    'unit' => optional(optional($activity->pic)->unit)->nama ?: '-',
                    'pic' => optional($activity->pic)->name ?: optional(optional($activity->area)->pic)->name ?: '-',
                    'location' => optional($activity->guidelineSubPoint)->title ?: '-',
                    'time' => $startDate->translatedFormat('d M Y') . ' - ' . $endDate->translatedFormat('d M Y'),
                    'participants' => '-',
                    'description' => $activity->description ?: 'Agenda kegiatan Zona Integritas.',
                ],
            ]);
        });
    }

    protected function buildSuratTugasEvents(User $user, array $filters)
    {
        if (!$user->canAccessPersuratanMenu()) {
            return collect();
        }

        $query = SuratKeluar::visibleTo($user)
            ->with(['creator.unit', 'templateApproval', 'penerimaInternal'])
            ->whereHas('templateApproval', function ($approvalQuery) {
                $approvalQuery->where('template_slug', 'surat-tugas');
            });

        if ($filters['scope'] === 'mine' && ($user->canManageSuratKeluar() || $user->isSuperAdmin())) {
            $query->where(function ($builder) use ($user) {
                $builder->where('created_by', $user->id)
                    ->orWhereHas('penerimaInternal', function ($recipientQuery) use ($user) {
                        $recipientQuery->where('users.id', $user->id);
                    });
            });
        }

        if ($filters['unit_id']) {
            $query->whereHas('creator', function ($creatorQuery) use ($filters) {
                $creatorQuery->where('unit_id', $filters['unit_id']);
            });
        }

        return $query->get()->filter(function ($surat) use ($filters) {
            $fieldValues = optional($surat->templateApproval)->field_values ?: [];
            $start = $fieldValues['tanggal_mulai'] ?? optional($surat->tanggal_surat)->toDateString();
            $end = $fieldValues['tanggal_selesai'] ?? $start;

            if (!$start) {
                return false;
            }

            $startDate = Carbon::parse($start, 'Asia/Jayapura')->startOfDay();
            $endDate = Carbon::parse($end ?: $start, 'Asia/Jayapura')->endOfDay();

            return $startDate->lte($filters['end']) && $endDate->gte($filters['start']);
        })->map(function ($surat) {
            $fieldValues = optional($surat->templateApproval)->field_values ?: [];
            $startDate = Carbon::parse($fieldValues['tanggal_mulai'] ?? optional($surat->tanggal_surat)->toDateString(), 'Asia/Jayapura')->startOfDay();
            $endDate = Carbon::parse($fieldValues['tanggal_selesai'] ?? $fieldValues['tanggal_mulai'] ?? optional($surat->tanggal_surat)->toDateString(), 'Asia/Jayapura')->endOfDay();
            $statusKey = $this->resolveTaskLetterStatus($surat, $startDate, $endDate);
            $petugasRows = collect($fieldValues['petugas_rows'] ?? []);
            $petugas = $this->implodeNames($petugasRows->pluck('nama')->filter()->values()->all());

            return $this->makeEvent([
                'id' => 'surat-tugas-' . $surat->id,
                'module_key' => 'surat_tugas',
                'module_label' => 'Surat Tugas',
                'source_key' => 'surat_tugas',
                'title' => $surat->perihal ?: ('Surat Tugas ' . $surat->nomor_surat_formatted),
                'start' => $startDate,
                'end' => (clone $endDate)->addDay()->startOfDay(),
                'allDay' => true,
                'status_key' => $statusKey,
                'status_label' => $this->statusLabel($statusKey),
                'color' => '#16a34a',
                'textColor' => '#ffffff',
                'url' => ($surat->file_path || $surat->templateApproval) ? route('surat-keluar.file', $surat) : route('surat-keluar.index'),
                'meta' => [
                    'kategori' => 'Surat Tugas',
                    'unit' => optional(optional($surat->creator)->unit)->nama ?: '-',
                    'pic' => optional($surat->creator)->name ?: '-',
                    'location' => $fieldValues['dalam_rangka'] ?? '-',
                    'time' => $startDate->translatedFormat('d M Y') . ' - ' . $endDate->translatedFormat('d M Y'),
                    'participants' => $petugas !== '-' ? $petugas : $this->implodeNames(optional($surat->penerimaInternal)->pluck('name')->all() ?? []),
                    'description' => $fieldValues['untuk_tugas'] ?? ($surat->perihal ?: 'Penugasan pegawai.'),
                ],
            ]);
        })->values();
    }

    protected function makeEvent(array $data)
    {
        $dateKeys = $this->expandDateKeys($data['start'], $data['end'], !empty($data['allDay']));

        return [
            'id' => $data['id'],
            'title' => $data['title'],
            'start' => $data['start']->toIso8601String(),
            'end' => $data['end']->toIso8601String(),
            'allDay' => (bool) $data['allDay'],
            'backgroundColor' => $data['color'],
            'borderColor' => $data['color'],
            'textColor' => $data['textColor'],
            'url' => $data['url'],
            'module_key' => $data['module_key'],
            'status_key' => $data['status_key'],
            'sort_ts' => $data['start']->timestamp,
            'date_keys' => $dateKeys,
            'extendedProps' => [
                'module_key' => $data['module_key'],
                'module_label' => $data['module_label'],
                'source_key' => $data['source_key'],
                'status_key' => $data['status_key'],
                'status_label' => $data['status_label'],
                'kategori' => $data['meta']['kategori'],
                'unit' => $data['meta']['unit'],
                'pic' => $data['meta']['pic'],
                'location' => $data['meta']['location'],
                'time_label' => $data['meta']['time'],
                'participants' => $data['meta']['participants'] ?? '-',
                'description' => $data['meta']['description'],
            ],
        ];
    }

    protected function implodeNames(array $names)
    {
        $names = collect($names)->filter()->values();

        if ($names->isEmpty()) {
            return '-';
        }

        $visible = $names->take(4);
        $suffix = $names->count() > 4 ? ' +' . ($names->count() - 4) . ' lainnya' : '';

        return $visible->implode(', ') . $suffix;
    }

    protected function buildConflicts(Collection $events)
    {
        $byDate = [];

        foreach ($events as $event) {
            foreach ($event['date_keys'] as $dateKey) {
                $byDate[$dateKey] = $byDate[$dateKey] ?? [];
                $byDate[$dateKey][] = $event;
            }
        }

        return collect($byDate)
            ->filter(function ($items) {
                return count($items) > 1;
            })
            ->map(function ($items, $dateKey) {
                return [
                    'date' => Carbon::parse($dateKey, 'Asia/Jayapura')->translatedFormat('d F Y'),
                    'count' => count($items),
                    'titles' => collect($items)->pluck('title')->take(3)->values()->all(),
                ];
            })
            ->take(6)
            ->values()
            ->all();
    }

    protected function buildUpcoming(Collection $events)
    {
        return $events->filter(function ($event) {
            return Carbon::parse($event['start'], 'Asia/Jayapura')->gte(now('Asia/Jayapura')->startOfDay());
        })->take(6)->map(function ($event) {
            return [
                'title' => $event['title'],
                'date' => Carbon::parse($event['start'], 'Asia/Jayapura')->translatedFormat('d M Y'),
                'module' => $event['extendedProps']['module_label'],
                'status' => $event['extendedProps']['status_label'],
            ];
        })->values()->all();
    }

    protected function expandDateKeys(Carbon $start, Carbon $end, $allDay)
    {
        $keys = [];
        $cursor = $start->copy()->startOfDay();
        $last = $allDay ? $end->copy()->subDay()->startOfDay() : $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            $keys[] = $cursor->toDateString();
            $cursor->addDay();
        }

        return $keys ?: [$start->toDateString()];
    }

    protected function resolveMeetingStatus($rawStatus, Carbon $startDate, Carbon $endDate)
    {
        if (in_array($rawStatus, ['draft', 'pending_approval', 'ditolak', 'dibatalkan'], true)) {
            return 'tertunda';
        }

        return $this->resolveTimeStatus($startDate, $endDate);
    }

    protected function resolveLeaveStatus(LeaveRequest $leave)
    {
        if (in_array($leave->status, [LeaveRequest::STATUS_REJECTED, LeaveRequest::STATUS_CANCELLED, LeaveRequest::STATUS_DRAFT], true)) {
            return 'tertunda';
        }

        if (in_array($leave->status, [LeaveRequest::STATUS_SUBMITTED, LeaveRequest::STATUS_UNDER_REVIEW, LeaveRequest::STATUS_VERIFIED], true)) {
            return 'tertunda';
        }

        return $this->resolveTimeStatus($leave->start_date, $leave->end_date);
    }

    protected function resolveZiStatus(ZiActivity $activity)
    {
        $today = now('Asia/Jayapura')->startOfDay();
        $end = $activity->target_end_date ?: $activity->target_start_date;
        $start = $activity->target_start_date ?: $activity->target_end_date;

        if ($end && Carbon::parse($end, 'Asia/Jayapura')->lt($today) && !in_array($activity->status, ['selesai', 'sudah_terlaksana'], true)) {
            return 'overdue';
        }

        if (in_array($activity->status, ['perlu_perbaikan'], true)) {
            return 'tertunda';
        }

        if (in_array($activity->status, ['selesai', 'sudah_terlaksana'], true)) {
            return 'selesai';
        }

        if (in_array($activity->status, ['sedang_berjalan'], true)) {
            return 'berjalan';
        }

        return $this->resolveTimeStatus($start, $end);
    }

    protected function resolveTaskLetterStatus(SuratKeluar $surat, Carbon $startDate, Carbon $endDate)
    {
        $approval = $surat->templateApproval;

        if ($approval && $approval->status === 'rejected') {
            return 'tertunda';
        }

        if ($approval && $approval->status === 'pending') {
            return 'tertunda';
        }

        if ($surat->status === 'draft') {
            return 'tertunda';
        }

        return $this->resolveTimeStatus($startDate, $endDate);
    }

    protected function resolveTimeStatus($startDate, $endDate)
    {
        $today = now('Asia/Jayapura')->startOfDay();
        $start = Carbon::parse($startDate, 'Asia/Jayapura')->startOfDay();
        $end = Carbon::parse($endDate ?: $startDate, 'Asia/Jayapura')->startOfDay();

        if ($end->lt($today)) {
            return 'selesai';
        }

        if ($start->gt($today)) {
            return 'dijadwalkan';
        }

        return 'berjalan';
    }

    protected function statusLabel($statusKey)
    {
        $map = [
            'dijadwalkan' => 'Dijadwalkan',
            'berjalan' => 'Berjalan',
            'selesai' => 'Selesai',
            'tertunda' => 'Tertunda',
            'overdue' => 'Overdue',
        ];

        return $map[$statusKey] ?? ucfirst((string) $statusKey);
    }

    protected function visibleAgendaQuery(User $user)
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
}

