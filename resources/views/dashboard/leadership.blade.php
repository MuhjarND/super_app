@extends('layouts.app')

@section('title', 'Dashboard Pimpinan')

@push('styles')
    <style>
        .leadership-page {
            display: grid;
            gap: 18px;
        }

        .leadership-hero {
            position: relative;
            overflow: hidden;
            padding: 24px 26px;
            border-radius: 20px;
            color: #fff;
            background: linear-gradient(135deg, #172554 0%, #3730a3 52%, #6d28d9 100%);
            box-shadow: 0 16px 38px rgba(49, 46, 129, .18);
        }

        .leadership-hero::after {
            content: '';
            position: absolute;
            width: 280px;
            height: 280px;
            right: -90px;
            top: -130px;
            border-radius: 50%;
            background: rgba(255, 255, 255, .09);
        }

        .leadership-hero-content {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
        }

        .leadership-hero h2 {
            margin: 0 0 5px;
            font-size: 1.45rem;
            font-weight: 800;
        }

        .leadership-hero p {
            margin: 0;
            color: rgba(255, 255, 255, .76);
            font-size: .9rem;
        }

        .leadership-hero-actions {
            display: flex;
            gap: 9px;
            flex-wrap: wrap;
        }

        .leadership-hero-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 39px;
            padding: 8px 14px;
            border: 1px solid rgba(255, 255, 255, .25);
            border-radius: 11px;
            color: #fff !important;
            background: rgba(255, 255, 255, .12);
            font-size: .82rem;
            font-weight: 700;
            text-decoration: none !important;
        }

        .leadership-summary-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 12px;
        }

        .leadership-summary-card {
            min-width: 0;
            padding: 17px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 4px 18px rgba(15, 23, 42, .04);
        }

        .leadership-summary-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            margin-bottom: 13px;
            border-radius: 11px;
            color: #4338ca;
            background: #eef2ff;
        }

        .leadership-summary-card.is-danger .leadership-summary-icon {
            color: #dc2626;
            background: #fef2f2;
        }

        .leadership-summary-card.is-warning .leadership-summary-icon {
            color: #b45309;
            background: #fffbeb;
        }

        .leadership-summary-value {
            margin-bottom: 3px;
            color: #0f172a;
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1;
        }

        .leadership-summary-label {
            overflow: hidden;
            color: #64748b;
            font-size: .78rem;
            font-weight: 650;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .leadership-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.45fr) minmax(330px, .85fr);
            gap: 18px;
            align-items: start;
        }

        .leadership-column {
            display: grid;
            gap: 18px;
        }

        .leadership-panel {
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 4px 20px rgba(15, 23, 42, .04);
        }

        .leadership-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            min-height: 64px;
            padding: 16px 19px;
            border-bottom: 1px solid #eef2f7;
        }

        .leadership-panel-title {
            margin: 0;
            color: #0f172a;
            font-size: .96rem;
            font-weight: 800;
        }

        .leadership-panel-subtitle {
            margin-top: 3px;
            color: #64748b;
            font-size: .76rem;
        }

        .leadership-panel-link {
            flex-shrink: 0;
            color: #4f46e5;
            font-size: .76rem;
            font-weight: 750;
        }

        .approval-breakdown {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            padding: 16px 18px 18px;
        }

        .approval-breakdown-item {
            padding: 13px;
            border: 1px solid #e8edf5;
            border-radius: 13px;
            background: #f8fafc;
        }

        .approval-breakdown-value {
            color: #312e81;
            font-size: 1.25rem;
            font-weight: 800;
        }

        .approval-breakdown-label {
            margin-top: 2px;
            color: #64748b;
            font-size: .72rem;
        }

        .leadership-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .leadership-list-item {
            display: flex;
            align-items: flex-start;
            gap: 13px;
            padding: 14px 18px;
            border-bottom: 1px solid #eef2f7;
        }

        .leadership-list-item:last-child {
            border-bottom: 0;
        }

        .leadership-list-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            border-radius: 11px;
            color: #4f46e5;
            background: #eef2ff;
        }

        .leadership-list-body {
            min-width: 0;
            flex: 1;
        }

        .leadership-list-title {
            overflow: hidden;
            margin-bottom: 3px;
            color: #172033;
            font-size: .84rem;
            font-weight: 750;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .leadership-list-meta,
        .leadership-list-description {
            color: #64748b;
            font-size: .73rem;
            line-height: 1.45;
        }

        .leadership-list-description {
            margin-top: 4px;
        }

        .leadership-list-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            border-radius: 10px;
            color: #4f46e5;
            background: #f1f5ff;
        }

        .leadership-badge {
            display: inline-flex;
            align-items: center;
            min-height: 23px;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: .67rem;
            font-weight: 750;
        }

        .leadership-badge.danger { color: #b91c1c; background: #fee2e2; }
        .leadership-badge.warning { color: #92400e; background: #fef3c7; }
        .leadership-badge.info { color: #1d4ed8; background: #dbeafe; }
        .leadership-badge.neutral { color: #475569; background: #f1f5f9; }

        .leadership-compact-stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            padding: 18px;
        }

        .leadership-compact-stat {
            padding: 15px;
            border: 1px solid #e8edf5;
            border-radius: 14px;
            background: #f8fafc;
        }

        .leadership-compact-stat strong {
            display: block;
            margin-bottom: 4px;
            color: #0f172a;
            font-size: 1.3rem;
        }

        .leadership-compact-stat span {
            color: #64748b;
            font-size: .73rem;
        }

        .leadership-period {
            grid-column: 1 / -1;
            color: #475569;
            font-size: .78rem;
            font-weight: 700;
        }

        .leadership-empty {
            padding: 30px 18px;
            color: #94a3b8;
            text-align: center;
            font-size: .8rem;
        }

        @media (max-width: 1199.98px) {
            .leadership-summary-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .approval-breakdown { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        @media (max-width: 991.98px) {
            .leadership-layout { grid-template-columns: 1fr; }
        }

        @media (max-width: 767.98px) {
            .content-header { padding-bottom: 8px; }
            .leadership-page { gap: 12px; }
            .leadership-hero { padding: 19px; border-radius: 16px; }
            .leadership-hero-content { align-items: flex-start; flex-direction: column; }
            .leadership-hero h2 { font-size: 1.15rem; }
            .leadership-hero p { font-size: .78rem; }
            .leadership-hero-actions { width: 100%; }
            .leadership-hero-link { flex: 1; justify-content: center; padding-inline: 10px; }
            .leadership-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 9px; }
            .leadership-summary-card { padding: 13px; border-radius: 14px; }
            .leadership-summary-icon { width: 32px; height: 32px; margin-bottom: 9px; }
            .leadership-summary-value { font-size: 1.3rem; }
            .leadership-summary-label { font-size: .68rem; white-space: normal; }
            .leadership-layout, .leadership-column { gap: 12px; }
            .leadership-panel { border-radius: 15px; }
            .leadership-panel-header { min-height: 56px; padding: 13px 15px; }
            .leadership-panel-subtitle, .leadership-list-description { display: none; }
            .approval-breakdown { grid-template-columns: repeat(2, minmax(0, 1fr)); padding: 13px; }
            .leadership-list-item { padding: 12px 14px; gap: 10px; }
            .leadership-list-title { white-space: normal; }
            .leadership-compact-stats { padding: 14px; gap: 9px; }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="mb-1">Dashboard Pimpinan</h1>
            <small class="text-muted">Ringkasan keputusan dan tindak lanjut lintas modul.</small>
        </div>
    </div>
@endsection

@section('content')
    @php
        $summaryCards = [
            ['label' => 'Approval pending', 'value' => $summary['pending_approvals'] ?? 0, 'icon' => 'fas fa-file-signature', 'class' => 'is-warning'],
            ['label' => 'Disposisi pending', 'value' => $summary['pending_dispositions'] ?? 0, 'icon' => 'fas fa-share-square', 'class' => 'is-warning'],
            ['label' => 'Agenda hari ini', 'value' => $summary['today_agenda'] ?? 0, 'icon' => 'fas fa-calendar-day', 'class' => ''],
            ['label' => 'Pegawai cuti', 'value' => $summary['active_leave'] ?? 0, 'icon' => 'fas fa-user-clock', 'class' => ''],
            ['label' => 'Prioritas tinggi', 'value' => $summary['urgent_actions'] ?? 0, 'icon' => 'fas fa-exclamation-circle', 'class' => 'is-danger'],
            ['label' => 'Terlambat', 'value' => $summary['overdue_actions'] ?? 0, 'icon' => 'fas fa-hourglass-end', 'class' => 'is-danger'],
        ];

        $approvalLabels = [
            'rapat' => 'Rapat',
            'notulensi' => 'Notulensi',
            'cuti' => 'Cuti',
            'surat' => 'Surat keluar',
            'zi' => 'Progress ZI',
        ];
    @endphp

    <div class="container-fluid leadership-page">
        <section class="leadership-hero">
            <div class="leadership-hero-content">
                <div>
                    <h2>{{ auth()->user()->name }}</h2>
                    <p>{{ now('Asia/Jayapura')->translatedFormat('l, d F Y') }} | Monitoring terpadu PTA Papua Barat</p>
                </div>
                <div class="leadership-hero-actions">
                    <a href="{{ route('calendar.integrated.index') }}" class="leadership-hero-link">
                        <i class="far fa-calendar-alt"></i> Kalender
                    </a>
                    <a href="{{ route('action-center.index') }}" class="leadership-hero-link">
                        <i class="fas fa-tasks"></i> Tindak Lanjut
                    </a>
                </div>
            </div>
        </section>

        <section class="leadership-summary-grid">
            @foreach($summaryCards as $card)
                <article class="leadership-summary-card {{ $card['class'] }}">
                    <div class="leadership-summary-icon"><i class="{{ $card['icon'] }}"></i></div>
                    <div class="leadership-summary-value">{{ number_format((int) $card['value'], 0, ',', '.') }}</div>
                    <div class="leadership-summary-label">{{ $card['label'] }}</div>
                </article>
            @endforeach
        </section>

        <div class="leadership-layout">
            <div class="leadership-column">
                <section class="leadership-panel">
                    <header class="leadership-panel-header">
                        <div>
                            <h3 class="leadership-panel-title">Approval Menunggu</h3>
                            <div class="leadership-panel-subtitle">Dokumen yang belum memperoleh keputusan.</div>
                        </div>
                        @if(auth()->user()->canAccessApprovalCenter())
                            <a href="{{ route('approval.index') }}" class="leadership-panel-link">Buka Approval</a>
                        @endif
                    </header>
                    <div class="approval-breakdown">
                        @foreach($approvalLabels as $key => $label)
                            <div class="approval-breakdown-item">
                                <div class="approval-breakdown-value">{{ (int) ($approvalSummary[$key] ?? 0) }}</div>
                                <div class="approval-breakdown-label">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="leadership-panel">
                    <header class="leadership-panel-header">
                        <div>
                            <h3 class="leadership-panel-title">Tindak Lanjut Prioritas</h3>
                            <div class="leadership-panel-subtitle">Item aktif yang memerlukan perhatian pimpinan.</div>
                        </div>
                        <a href="{{ route('action-center.index') }}" class="leadership-panel-link">Lihat Semua</a>
                    </header>
                    <ul class="leadership-list">
                        @forelse($actionItems as $item)
                            @php
                                $priorityClass = data_get($item, 'priority_key') === 'high' ? 'danger' : (data_get($item, 'is_overdue') ? 'danger' : 'neutral');
                                $actionUrl = data_get($item, 'action_url');
                            @endphp
                            <li class="leadership-list-item">
                                <div class="leadership-list-icon"><i class="{{ data_get($item, 'module_icon', 'fas fa-tasks') }}"></i></div>
                                <div class="leadership-list-body">
                                    <div class="leadership-list-title">{{ data_get($item, 'title', '-') }}</div>
                                    <div class="leadership-list-meta">
                                        {{ data_get($item, 'module_label', '-') }} | {{ data_get($item, 'target_label', '-') }}
                                        <span class="leadership-badge {{ $priorityClass }} ml-1">{{ data_get($item, 'priority_label', 'Normal') }}</span>
                                    </div>
                                    <div class="leadership-list-description">{{ data_get($item, 'subtitle', '-') }}</div>
                                </div>
                                @if($actionUrl)
                                    <a href="{{ $actionUrl }}" class="leadership-list-action" title="{{ data_get($item, 'action_text', 'Buka') }}">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                @endif
                            </li>
                        @empty
                            <li class="leadership-empty">Tidak ada tindak lanjut aktif.</li>
                        @endforelse
                    </ul>
                </section>

                <section class="leadership-panel">
                    <header class="leadership-panel-header">
                        <div>
                            <h3 class="leadership-panel-title">Disposisi Tertunda</h3>
                            <div class="leadership-panel-subtitle">Disposisi lintas unit yang masih menunggu tindakan.</div>
                        </div>
                    </header>
                    <ul class="leadership-list">
                        @forelse($pendingDisposisi as $disposisi)
                            @php
                                $surat = $disposisi->suratMasuk;
                                $canOpenSurat = $surat && auth()->user()->canViewSuratMasuk($surat);
                            @endphp
                            <li class="leadership-list-item">
                                <div class="leadership-list-icon"><i class="far fa-envelope-open"></i></div>
                                <div class="leadership-list-body">
                                    <div class="leadership-list-title">{{ optional($surat)->nomor_surat ?: 'Surat masuk' }}</div>
                                    <div class="leadership-list-meta">
                                        {{ optional($disposisi->kepadaUser)->name ?: '-' }} | {{ $disposisi->target_label }}
                                        <span class="leadership-badge {{ $disposisi->priority_level === 'high' ? 'danger' : 'neutral' }} ml-1">
                                            {{ $disposisi->priority_level === 'high' ? 'Tinggi' : 'Normal' }}
                                        </span>
                                    </div>
                                    <div class="leadership-list-description">{{ optional($surat)->perihal ?: ($disposisi->petunjuk ?: '-') }}</div>
                                </div>
                                @if($canOpenSurat)
                                    <a href="{{ route('surat-masuk.show', $surat) }}" class="leadership-list-action" title="Buka surat">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                @endif
                            </li>
                        @empty
                            <li class="leadership-empty">Tidak ada disposisi pending.</li>
                        @endforelse
                    </ul>
                </section>
            </div>

            <aside class="leadership-column">
                <section class="leadership-panel">
                    <header class="leadership-panel-header">
                        <div>
                            <h3 class="leadership-panel-title">Agenda 7 Hari</h3>
                            <div class="leadership-panel-subtitle">Jadwal lintas modul yang akan berlangsung.</div>
                        </div>
                        <a href="{{ route('calendar.integrated.index') }}" class="leadership-panel-link">Kalender</a>
                    </header>
                    <ul class="leadership-list">
                        @forelse($upcomingEvents as $event)
                            @php
                                $eventStart = \Carbon\Carbon::parse(data_get($event, 'start'))->timezone('Asia/Jayapura');
                                $eventUrl = data_get($event, 'url');
                            @endphp
                            <li class="leadership-list-item">
                                <div class="leadership-list-icon"><i class="far fa-calendar"></i></div>
                                <div class="leadership-list-body">
                                    <div class="leadership-list-title">{{ data_get($event, 'title', '-') }}</div>
                                    <div class="leadership-list-meta">
                                        {{ $eventStart->translatedFormat('d M, H:i') }} WIT | {{ data_get($event, 'extendedProps.module_label', '-') }}
                                    </div>
                                    <div class="leadership-list-description">{{ data_get($event, 'extendedProps.location', '-') }}</div>
                                </div>
                                @if($eventUrl)
                                    <a href="{{ $eventUrl }}" class="leadership-list-action" title="Buka agenda">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                @endif
                            </li>
                        @empty
                            <li class="leadership-empty">Tidak ada agenda tujuh hari ke depan.</li>
                        @endforelse
                    </ul>
                </section>

                <section class="leadership-panel">
                    <header class="leadership-panel-header">
                        <div>
                            <h3 class="leadership-panel-title">Progress ZI</h3>
                            <div class="leadership-panel-subtitle">Status periode dan kegiatan yang perlu ditindaklanjuti.</div>
                        </div>
                    </header>
                    <div class="leadership-compact-stats">
                        <div class="leadership-period"><i class="far fa-calendar-check mr-1"></i> {{ $ziStats['period_name'] ?? '-' }}</div>
                        <div class="leadership-compact-stat">
                            <strong>{{ (int) ($ziStats['overdue_count'] ?? 0) }}</strong>
                            <span>Kegiatan terlambat</span>
                        </div>
                        <div class="leadership-compact-stat">
                            <strong>{{ (int) ($ziStats['approval_pending'] ?? 0) }}</strong>
                            <span>Review pending</span>
                        </div>
                    </div>
                </section>

                <section class="leadership-panel">
                    <header class="leadership-panel-header">
                        <div>
                            <h3 class="leadership-panel-title">Perawatan Alat</h3>
                            <div class="leadership-panel-subtitle">Kontrol transaksi dan kelengkapan dokumentasi.</div>
                        </div>
                    </header>
                    <div class="leadership-compact-stats">
                        <div class="leadership-compact-stat">
                            <strong>{{ (int) ($inventoryStats['draft_count'] ?? 0) }}</strong>
                            <span>Transaksi draft</span>
                        </div>
                        <div class="leadership-compact-stat">
                            <strong>{{ (int) ($inventoryStats['attachment_pending_count'] ?? 0) }}</strong>
                            <span>Tanpa lampiran</span>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </div>
@endsection
