@extends('layouts.app')

@section('title', 'Tindak Lanjut Terpadu')

@section('content')
    @php
        $moduleToneMap = [
            'persuratan' => ['bg' => '#eff6ff', 'text' => '#1d4ed8', 'icon' => 'fas fa-envelope-open-text'],
            'rapat' => ['bg' => '#eef2ff', 'text' => '#4338ca', 'icon' => 'fas fa-calendar-alt'],
            'cuti' => ['bg' => '#fef2f2', 'text' => '#dc2626', 'icon' => 'fas fa-calendar-check'],
            'progress_zi' => ['bg' => '#fff7ed', 'text' => '#d97706', 'icon' => 'fas fa-chart-line'],
            'perawatan' => ['bg' => '#ecfeff', 'text' => '#0f766e', 'icon' => 'fas fa-tools'],
        ];

        $statusToneMap = [
            'waiting' => ['bg' => '#fef3c7', 'text' => '#92400e'],
            'process' => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
            'revision' => ['bg' => '#fee2e2', 'text' => '#b91c1c'],
            'overdue' => ['bg' => '#fecaca', 'text' => '#991b1b'],
            'done' => ['bg' => '#dcfce7', 'text' => '#166534'],
        ];

        $priorityToneMap = [
            'high' => ['bg' => '#fee2e2', 'text' => '#b91c1c'],
            'normal' => ['bg' => '#e0e7ff', 'text' => '#4338ca'],
            'low' => ['bg' => '#ecfccb', 'text' => '#3f6212'],
        ];

        $groupedItems = collect(['Daftar Item' => $items]);

        if ($filters['group'] === 'module') {
            $groupedItems = $items->groupBy('module_label');
        } elseif ($filters['group'] === 'deadline') {
            $groupedItems = $items->groupBy(function ($item) use ($filters) {
                if (in_array($filters['tab'], ['history', 'done_today'], true)) {
                    if (($item['target_date'] ?? null) === now('Asia/Jayapura')->toDateString()) {
                        return 'Hari Ini';
                    }

                    if (!empty($item['target_at']) && $item['target_at']->gte(now('Asia/Jayapura')->copy()->subDays(7))) {
                        return '7 Hari Terakhir';
                    }

                    return 'Riwayat Sebelumnya';
                }

                if (!empty($item['is_overdue'])) {
                    return 'Overdue';
                }

                if (($item['target_date'] ?? null) === now('Asia/Jayapura')->toDateString()) {
                    return 'Hari Ini';
                }

                if (!empty($item['target_at']) && $item['target_at']->isTomorrow()) {
                    return 'Besok';
                }

                if (!empty($item['target_at']) && $item['target_at']->lte(now('Asia/Jayapura')->copy()->endOfWeek())) {
                    return 'Minggu Ini';
                }

                if (!empty($item['target_at'])) {
                    return 'Setelah Minggu Ini';
                }

                return 'Tanpa Target';
            });
        }
    @endphp

    <style>
        .action-center-shell {
            display: grid;
            gap: 18px;
        }
        .action-center-board {
            border: 1px solid #dbe5f3;
            border-radius: 22px;
            background: #fff;
            overflow: hidden;
        }

        .action-center-board-head {
            padding: 22px 24px 14px;
            border-bottom: 1px solid #eaf0f8;
        }

        .action-tab-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .action-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 14px;
            border-radius: 999px;
            border: 1px solid #d9e5f5;
            background: #f8fbff;
            color: #334155;
            font-weight: 700;
            font-size: 0.82rem;
            text-decoration: none !important;
        }

        .action-tab.active {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            border-color: transparent;
            color: #fff;
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.2);
        }

        .action-center-filter {
            padding: 18px 24px;
            border-bottom: 1px solid #eaf0f8;
            background: #fbfdff;
        }

        .action-list {
            padding: 20px 24px 24px;
            display: grid;
            gap: 18px;
        }

        .action-group {
            display: grid;
            gap: 12px;
        }

        .action-group-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 4px 2px 0;
        }

        .action-group-title {
            font-size: 0.86rem;
            font-weight: 800;
            color: #1e293b;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .action-group-count {
            font-size: 0.74rem;
            color: #64748b;
            font-weight: 700;
        }

        .action-item {
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 18px 18px 16px;
            background: linear-gradient(180deg, #ffffff, #fbfdff);
        }

        .action-item-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }

        .action-item-main {
            display: flex;
            gap: 14px;
            min-width: 0;
        }

        .action-item-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .action-item-title {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.35;
        }

        .action-item-subtitle {
            margin-top: 3px;
            font-size: 0.82rem;
            color: #334155;
            font-weight: 600;
        }

        .action-item-description {
            margin-top: 7px;
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.45;
        }

        .action-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .action-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
        }

        .action-item-side {
            min-width: 170px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        .action-item-deadline {
            font-size: 0.77rem;
            color: #64748b;
            font-weight: 700;
            text-align: right;
        }

        .action-empty {
            border: 1px dashed #cbd5e1;
            border-radius: 18px;
            padding: 28px;
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 991.98px) {
            .action-center-board-head,
            .action-center-filter,
            .action-list {
                padding-left: 16px;
                padding-right: 16px;
            }

            .action-item-top {
                flex-direction: column;
            }

            .action-item-side {
                width: 100%;
                align-items: flex-start;
                min-width: 0;
            }
        }

        @media (max-width: 767.98px) {
            .action-center-board {
                border-radius: 16px;
            }

            .action-tab-row {
                flex-wrap: nowrap;
                overflow-x: auto;
                padding-bottom: 4px;
                margin-right: -4px;
            }

            .action-tab {
                flex: 0 0 auto;
                white-space: nowrap;
            }

            .action-center-board-head,
            .action-center-filter,
            .action-list {
                padding-left: 14px;
                padding-right: 14px;
            }

            .action-item {
                padding: 15px 14px 14px;
                border-radius: 16px;
            }

            .action-item-main {
                gap: 12px;
            }

            .action-item-icon {
                width: 40px;
                height: 40px;
                border-radius: 12px;
            }
        }
    </style>

    <div class="action-center-shell">
        <div class="action-center-board">
            <div class="action-center-board-head">
                <div class="d-flex flex-wrap align-items-start justify-content-between">
                    <div>
                        <h5 class="mb-1">Inbox kerja lintas modul</h5>
                        <p class="text-muted mb-0">Klik item untuk langsung masuk ke halaman aksi pada modul asal.</p>
                    </div>
                    <div class="text-md-right mt-2 mt-md-0">
                        <div class="font-weight-bold text-dark">{{ $summary['visible_count'] }} item tampil</div>
                        <div class="text-muted small">Setelah tab dan filter diterapkan</div>
                    </div>
                </div>

                <div class="action-tab-row">
                    @foreach($tab_options as $key => $label)
                        <a href="{{ route('action-center.index', array_merge(request()->except('page'), ['tab' => $key])) }}"
                            class="action-tab {{ $filters['tab'] === $key ? 'active' : '' }}">
                            <span>{{ $label }}</span>
                            <span>{{ $tab_counts[$key] ?? 0 }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="action-center-filter">
                <form method="GET" action="{{ route('action-center.index') }}">
                    <input type="hidden" name="tab" value="{{ $filters['tab'] }}">
                    <div class="form-row">
                        <div class="form-group col-md-2">
                            <label class="small text-muted font-weight-bold">Modul</label>
                            <select name="module" class="form-control">
                                @foreach($module_options as $key => $label)
                                    <option value="{{ $key }}" {{ $filters['module'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-muted font-weight-bold">Status</label>
                            <select name="status" class="form-control">
                                @foreach($status_options as $key => $label)
                                    <option value="{{ $key }}" {{ $filters['status'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-muted font-weight-bold">Unit</label>
                            <select name="unit" class="form-control">
                                <option value="all" {{ $filters['unit'] === 'all' ? 'selected' : '' }}>Semua Unit</option>
                                @foreach($unit_options as $label)
                                    <option value="{{ $label }}" {{ $filters['unit'] === $label ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-muted font-weight-bold">PIC</label>
                            <select name="assignee" class="form-control">
                                <option value="all" {{ $filters['assignee'] === 'all' ? 'selected' : '' }}>Semua PIC</option>
                                @foreach($assignee_options as $label)
                                    <option value="{{ $label }}" {{ $filters['assignee'] === $label ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <label class="small text-muted font-weight-bold">Kelompok</label>
                            <select name="group" class="form-control">
                                @foreach($group_options as $key => $label)
                                    <option value="{{ $key }}" {{ $filters['group'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-block">Terapkan</button>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-12 mb-0">
                            <label class="small text-muted font-weight-bold">Pencarian</label>
                            <input type="text" name="search" class="form-control" value="{{ $filters['search'] }}" placeholder="Cari judul, deskripsi, unit, PIC, atau modul">
                        </div>
                    </div>
                </form>
            </div>

            <div class="action-list">
                @if($items->isEmpty())
                    <div class="action-empty">
                        <div class="font-weight-bold text-dark mb-1">Tidak ada item tindak lanjut.</div>
                        <div>Filter saat ini tidak menemukan item yang perlu aksi.</div>
                    </div>
                @else
                    @foreach($groupedItems as $groupLabel => $groupItems)
                        <div class="action-group">
                            @if($filters['group'] !== 'none')
                                <div class="action-group-head">
                                    <div class="action-group-title">{{ $groupLabel }}</div>
                                    <div class="action-group-count">{{ $groupItems->count() }} item</div>
                                </div>
                            @endif

                            @foreach($groupItems as $item)
                                @php
                                    $moduleTone = $moduleToneMap[$item['module_key']] ?? ['bg' => '#eef2ff', 'text' => '#3730a3', 'icon' => 'fas fa-layer-group'];
                                    $statusTone = $statusToneMap[$item['status_key']] ?? ['bg' => '#e2e8f0', 'text' => '#334155'];
                                    $priorityTone = $priorityToneMap[$item['priority_key']] ?? ['bg' => '#e2e8f0', 'text' => '#334155'];
                                @endphp
                                <div class="action-item">
                                    <div class="action-item-top">
                                        <div class="action-item-main">
                                            <div class="action-item-icon" style="background: {{ $moduleTone['bg'] }}; color: {{ $moduleTone['text'] }};">
                                                <i class="{{ $item['module_icon'] ?? $moduleTone['icon'] }}"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="action-item-title">{{ $item['title'] }}</div>
                                                <div class="action-item-subtitle">{{ $item['type_label'] }} • {{ $item['subtitle'] }}</div>
                                                <div class="action-item-description">{{ $item['description'] }}</div>

                                                <div class="action-chip-row">
                                                    <span class="action-chip" style="background: {{ $moduleTone['bg'] }}; color: {{ $moduleTone['text'] }};">
                                                        {{ $item['module_label'] }}
                                                    </span>
                                                    <span class="action-chip" style="background: {{ $statusTone['bg'] }}; color: {{ $statusTone['text'] }};">
                                                        {{ $item['status_label'] }}
                                                    </span>
                                                    <span class="action-chip" style="background: {{ $priorityTone['bg'] }}; color: {{ $priorityTone['text'] }};">
                                                        Prioritas {{ $item['priority_label'] }}
                                                    </span>
                                                    <span class="action-chip" style="background: #f8fafc; color: #475569;">
                                                        Unit: {{ $item['unit_label'] }}
                                                    </span>
                                                    @if(($item['assignee_name'] ?? '-') !== '-')
                                                        <span class="action-chip" style="background: #f8fafc; color: #475569;">
                                                            PIC: {{ $item['assignee_name'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="action-item-side">
                                            <div class="action-item-deadline">
                                                <div>Target: {{ $item['target_label'] }}</div>
                                                @if(!empty($item['target_at']))
                                                    <div>{{ $item['target_at']->diffForHumans() }}</div>
                                                @endif
                                            </div>
                                            <a href="{{ $item['action_url'] }}" class="btn btn-primary">
                                                <i class="fas fa-external-link-alt mr-1"></i> {{ $item['action_text'] }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
@endsection
