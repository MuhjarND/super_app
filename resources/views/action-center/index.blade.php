@extends('layouts.app')

@section('title', 'Tindak Lanjut Terpadu')

@section('content')
    @php
        $moduleToneMap = [
            'persuratan' => ['bg' => '#eef2ff', 'text' => '#4338ca', 'icon' => 'fas fa-envelope-open-text'],
            'rapat' => ['bg' => '#eef2ff', 'text' => '#4338ca', 'icon' => 'fas fa-calendar-alt'],
            'cuti' => ['bg' => '#fef2f2', 'text' => '#dc2626', 'icon' => 'fas fa-calendar-check'],
            'progress_zi' => ['bg' => '#fff7ed', 'text' => '#d97706', 'icon' => 'fas fa-chart-line'],
            'perawatan' => ['bg' => '#ecfeff', 'text' => '#0f766e', 'icon' => 'fas fa-tools'],
        ];

        $statusToneMap = [
            'waiting' => ['bg' => '#fef3c7', 'text' => '#92400e'],
            'process' => ['bg' => '#e0e7ff', 'text' => '#4338ca'],
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
            gap: 12px;
        }

        .action-center-board {
            border: 1px solid #dbe5f3;
            border-radius: 16px;
            background: #fff;
            overflow: hidden;
        }

        .action-compact-toolbar {
            display: grid;
            grid-template-columns: minmax(240px, 1fr) repeat(3, minmax(132px, 170px)) auto;
            gap: 10px;
            align-items: center;
            padding: 12px 14px;
            border-bottom: 1px solid #eaf0f8;
            background: #fbfdff;
        }

        .action-compact-control {
            height: 40px;
            border: 1px solid #dbe5f3;
            border-radius: 12px;
            background: #fff;
            color: #0f172a;
            font-size: 0.84rem;
            font-weight: 700;
            box-shadow: none;
        }

        .action-compact-search {
            position: relative;
        }

        .action-compact-search i {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.82rem;
        }

        .action-compact-search .form-control {
            padding-left: 36px;
        }

        .action-compact-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 40px;
            min-width: 76px;
            padding: 0 12px;
            border-radius: 999px;
            background: linear-gradient(135deg, #4338ca, #6d5dfc);
            color: #fff;
            font-weight: 800;
            font-size: 0.82rem;
        }

        .action-type-filters {
            display: flex;
            gap: 8px;
            padding: 10px 14px;
            overflow-x: auto;
            border-bottom: 1px solid #eaf0f8;
            background: #fff;
            scrollbar-width: thin;
        }

        .action-type-filter {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            flex: 0 0 auto;
            min-height: 36px;
            padding: 7px 12px;
            border: 1px solid #dbe5f3;
            border-radius: 999px;
            background: #fff;
            color: #475569;
            font-size: .76rem;
            font-weight: 800;
            transition: .16s ease;
        }

        .action-type-filter:hover {
            border-color: #a5b4fc;
            color: #4338ca;
        }

        .action-type-filter.active {
            border-color: #4f46e5;
            background: #4f46e5;
            color: #fff;
            box-shadow: 0 7px 16px rgba(79, 70, 229, .18);
        }

        .action-type-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: #eef2ff;
            color: #4338ca;
            font-size: .68rem;
        }

        .action-type-filter.active .action-type-count {
            background: rgba(255, 255, 255, .2);
            color: #fff;
        }

        .action-list {
            padding: 14px;
            display: grid;
            gap: 14px;
        }

        .action-group {
            display: grid;
            gap: 8px;
        }

        .action-group-head {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 12px;
            padding: 2px 2px 0;
        }

        .action-group-title {
            font-size: 0.78rem;
            font-weight: 800;
            color: #1e293b;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .action-group-count {
            font-size: 0.72rem;
            color: #64748b;
            font-weight: 700;
        }

        .action-item {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px;
            background: #fff;
            transition: border-color 0.16s ease, box-shadow 0.16s ease, transform 0.16s ease;
        }

        .action-item:hover {
            border-color: #c7d2fe;
            box-shadow: 0 10px 24px rgba(67, 56, 202, 0.08);
            transform: translateY(-1px);
        }

        .action-item-top {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 12px;
        }

        .action-item-main {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .action-item-main > .min-w-0 {
            min-width: 0;
        }

        .action-item-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.92rem;
            flex-shrink: 0;
        }

        .action-item-title {
            font-size: 0.93rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.25;
            overflow-wrap: anywhere;
        }

        .action-item-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 5px;
            font-size: 0.74rem;
            color: #64748b;
            font-weight: 700;
        }

        .action-item-meta span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .action-quick-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .action-state-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }

        .action-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 9px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .action-item-side {
            min-width: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-item-side .btn {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            box-shadow: 0 8px 18px rgba(67, 56, 202, 0.16);
        }

        .action-empty {
            border: 1px dashed #cbd5e1;
            border-radius: 14px;
            padding: 28px;
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 991.98px) {
            .action-compact-toolbar {
                grid-template-columns: 1fr 1fr;
            }

            .action-compact-search {
                grid-column: 1 / -1;
            }

            .action-item-side {
                min-width: 38px;
            }
        }

        @media (max-width: 767.98px) {
            .action-center-board {
                border-radius: 14px;
            }

            .action-compact-toolbar {
                grid-template-columns: 1fr;
                padding: 10px;
            }

            .action-compact-count {
                justify-self: stretch;
            }

            .action-list {
                padding: 10px;
            }

            .action-type-filters {
                padding: 9px 10px;
            }

            .action-item {
                padding: 11px;
            }

            .action-item-top {
                grid-template-columns: minmax(0, 1fr) 38px;
                align-items: flex-start;
            }

            .action-item-side {
                width: auto;
                min-width: 0;
                align-items: center;
                justify-content: center;
            }
        }
    </style>

    <div class="action-center-shell">
        <div class="action-center-board">
            <form method="GET" action="{{ route('action-center.index') }}" id="actionCenterFilter">
                <div class="action-compact-toolbar">
                    <div class="action-compact-search">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" class="form-control action-compact-control" value="{{ $filters['search'] }}" placeholder="Cari tugas...">
                    </div>
                    <select name="tab" class="form-control action-compact-control" aria-label="Tab">
                        @foreach($tab_options as $key => $label)
                            <option value="{{ $key }}" {{ $filters['tab'] === $key ? 'selected' : '' }}>{{ $label }} ({{ $tab_counts[$key] ?? 0 }})</option>
                        @endforeach
                    </select>
                    <select name="status" class="form-control action-compact-control" aria-label="Status">
                        @foreach($status_options as $key => $label)
                            <option value="{{ $key }}" {{ $filters['status'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="group" class="form-control action-compact-control" aria-label="Kelompok">
                        @foreach($group_options as $key => $label)
                            <option value="{{ $key }}" {{ $filters['group'] === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="module" value="all">
                    <input type="hidden" name="type" id="actionCenterType" value="{{ $filters['type'] }}">
                    <input type="hidden" name="unit" value="all">
                    <input type="hidden" name="assignee" value="all">
                    <button type="submit" class="d-none">Terapkan</button>
                    <div class="action-compact-count">{{ $summary['visible_count'] }} item</div>
                </div>

                <div class="action-type-filters" aria-label="Filter jenis tindak lanjut">
                    @foreach($type_options as $key => $label)
                        <button type="button" class="action-type-filter {{ $filters['type'] === $key ? 'active' : '' }}" data-action-type="{{ $key }}">
                            {{ $label }}
                            <span class="action-type-count">{{ $type_counts[$key] ?? 0 }}</span>
                        </button>
                    @endforeach
                </div>
            </form>

            <div class="action-list">
                @if($items->isEmpty())
                    <div class="action-empty">
                        <div class="font-weight-bold text-dark mb-1">Tidak ada item.</div>
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
                                                <div class="action-item-meta">
                                                    <span><i class="fas fa-layer-group"></i>{{ $item['type_label'] }}</span>
                                                    @if(($item['assignee_name'] ?? '-') !== '-')
                                                        <span><i class="fas fa-user"></i>{{ $item['assignee_name'] }}</span>
                                                    @endif
                                                    <span><i class="fas fa-history"></i>{{ $item['activity_label'] }}</span>
                                                    @if(($item['target_label'] ?? '-') !== '-')
                                                        <span><i class="fas fa-bullseye"></i>Target {{ $item['target_label'] }}</span>
                                                    @endif
                                                </div>
                                                <div class="action-state-row">
                                                    <span class="action-chip" style="background: {{ $statusTone['bg'] }}; color: {{ $statusTone['text'] }};">
                                                        {{ $item['status_label'] }}
                                                    </span>
                                                    @if(($item['priority_key'] ?? 'normal') === 'high')
                                                        <span class="action-chip" style="background: {{ $priorityTone['bg'] }}; color: {{ $priorityTone['text'] }};">Prioritas Tinggi</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="action-item-side">
                                            <a href="{{ $item['action_url'] }}" class="btn btn-primary btn-sm" title="{{ $item['action_text'] }}">
                                                <i class="fas fa-arrow-right"></i>
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

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('actionCenterFilter');
            if (!form) return;

            form.querySelectorAll('select').forEach(function (select) {
                select.addEventListener('change', function () {
                    form.submit();
                });
            });

            form.querySelectorAll('[data-action-type]').forEach(function (button) {
                button.addEventListener('click', function () {
                    document.getElementById('actionCenterType').value = button.getAttribute('data-action-type');
                    form.submit();
                });
            });
        });
    </script>
@endsection
