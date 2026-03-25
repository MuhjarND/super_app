@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <style>
        .dashboard-shell {
            display: grid;
            gap: 18px;
        }

        .dashboard-hero {
            background: linear-gradient(135deg, #0f3352 0%, #175d8f 52%, #3b82f6 100%);
            color: #fff;
            border-radius: 18px;
            padding: 26px 28px;
            box-shadow: 0 18px 40px rgba(15, 51, 82, 0.18);
        }

        .dashboard-hero-title {
            font-size: 1.55rem;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .dashboard-hero-meta {
            opacity: 0.86;
            font-size: 0.92rem;
        }

        .hero-chip-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .hero-chip {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.16);
            border-radius: 999px;
            padding: 9px 14px;
            min-height: 44px;
        }

        .hero-chip i {
            color: #facc15;
        }

        .hero-chip strong {
            font-size: 1rem;
            line-height: 1;
            display: block;
        }

        .hero-chip span {
            font-size: 0.74rem;
            opacity: 0.82;
            display: block;
            margin-top: 2px;
        }

        .module-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .module-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05);
            display: grid;
            gap: 16px;
        }

        .module-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .module-card-title {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .module-card-subtitle {
            color: #64748b;
            font-size: 0.82rem;
        }

        .module-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 14px;
            font-size: 1.1rem;
            color: #fff;
            flex-shrink: 0;
        }

        .module-pill.persuratan { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
        .module-pill.rapat { background: linear-gradient(135deg, #0f766e, #0d9488); }
        .module-pill.cuti { background: linear-gradient(135deg, #15803d, #16a34a); }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .metric-box {
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 12px 13px;
            min-height: 76px;
        }

        .metric-box .value {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
            margin-bottom: 6px;
        }

        .metric-box .label {
            font-size: 0.78rem;
            color: #64748b;
            line-height: 1.25;
        }

        .module-link-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .module-link-row a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 10px;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 9px 12px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
        }

        .module-link-row a.alt {
            background: #f0fdf4;
            color: #15803d;
        }

        .dashboard-row {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 18px;
        }

        .dash-panel {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .dash-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 20px 14px;
            border-bottom: 1px solid #eef2f7;
        }

        .dash-panel-head h5 {
            margin: 0;
            font-size: 0.98rem;
            font-weight: 800;
            color: #0f172a;
        }

        .dash-panel-head p {
            margin: 3px 0 0;
            font-size: 0.78rem;
            color: #64748b;
        }

        .dash-panel-body {
            padding: 8px 20px 18px;
        }

        .action-list,
        .recent-list,
        .upcoming-list {
            display: grid;
            gap: 10px;
        }

        .action-item,
        .recent-item,
        .upcoming-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 12px;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .recent-item,
        .upcoming-item {
            grid-template-columns: 1fr auto;
        }

        .action-item:last-child,
        .recent-item:last-child,
        .upcoming-item:last-child {
            border-bottom: none;
        }

        .action-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            color: #fff;
            margin-top: 2px;
        }

        .tone-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
        .tone-amber { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .tone-green { background: linear-gradient(135deg, #22c55e, #15803d); }
        .tone-red { background: linear-gradient(135deg, #ef4444, #b91c1c); }
        .tone-purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }

        .item-title {
            font-size: 0.87rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 3px;
        }

        .item-subtitle {
            font-size: 0.78rem;
            color: #334155;
            margin-bottom: 3px;
        }

        .item-description,
        .item-meta {
            font-size: 0.76rem;
            color: #64748b;
            line-height: 1.35;
        }

        .item-link {
            align-self: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #eff6ff;
            color: #1d4ed8;
            text-decoration: none;
            flex-shrink: 0;
        }

        .list-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 64px;
            padding-left: 10px;
            margin-left: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 28px 12px;
            color: #94a3b8;
            font-size: 0.86rem;
        }

        .recent-columns {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        @media (max-width: 1199.98px) {
            .module-grid,
            .recent-columns,
            .dashboard-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .hero-chip-wrap {
                flex-direction: column;
            }

            .metric-grid {
                grid-template-columns: 1fr;
            }

            .dash-panel-head,
            .module-card-head {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header" style="padding-bottom: 0;">
        <div class="container-fluid"></div>
    </div>
@endsection

@section('content')
    <div class="dashboard-shell">
        <section class="dashboard-hero">
            <div class="dashboard-hero-title">{{ auth()->user()->name }}</div>
            <div class="dashboard-hero-meta">{{ now()->translatedFormat('l, d F Y') }} • Ringkasan kerja lintas modul</div>
            <div class="hero-chip-wrap">
                <div class="hero-chip">
                    <i class="fas fa-bell"></i>
                    <div>
                        <strong>{{ $dashboardSummary['action_count'] }}</strong>
                        <span>Tindak lanjut aktif</span>
                    </div>
                </div>
                <div class="hero-chip">
                    <i class="fas fa-inbox"></i>
                    <div>
                        <strong>{{ $dashboardSummary['today_masuk'] }}</strong>
                        <span>Surat masuk hari ini</span>
                    </div>
                </div>
                <div class="hero-chip">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                        <strong>{{ $dashboardSummary['upcoming_meetings'] }}</strong>
                        <span>Rapat / agenda mendatang</span>
                    </div>
                </div>
                <div class="hero-chip">
                    <i class="fas fa-calendar-check"></i>
                    <div>
                        <strong>{{ $dashboardSummary['pending_leave_approvals'] }}</strong>
                        <span>Approval cuti pending</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="module-grid">
            @if($persuratan['enabled'])
                <article class="module-card">
                    <div class="module-card-head">
                        <div>
                            <div class="module-card-title">Persuratan</div>
                            <div class="module-card-subtitle">Surat masuk, surat keluar, dan disposisi yang relevan dengan Anda.</div>
                        </div>
                        <div class="module-pill persuratan"><i class="fas fa-envelope-open-text"></i></div>
                    </div>
                    <div class="metric-grid">
                        <div class="metric-box">
                            <div class="value">{{ $persuratan['stats']['total_masuk'] }}</div>
                            <div class="label">Total surat masuk terlihat</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $persuratan['stats']['surat_baru'] }}</div>
                            <div class="label">Surat masuk baru</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $persuratan['stats']['disposisi_pending'] }}</div>
                            <div class="label">Disposisi menunggu tindak lanjut</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $persuratan['stats']['keluar_draft'] }}</div>
                            <div class="label">Surat keluar draft</div>
                        </div>
                    </div>
                    <div class="module-link-row">
                        <a href="{{ route('surat-masuk.index') }}"><i class="fas fa-inbox"></i> Surat Masuk</a>
                        <a href="{{ route('surat-keluar.index') }}" class="alt"><i class="fas fa-paper-plane"></i> Surat Keluar</a>
                    </div>
                </article>
            @endif

            @if($meeting['enabled'])
                <article class="module-card">
                    <div class="module-card-head">
                        <div>
                            <div class="module-card-title">Rapat / Agenda</div>
                            <div class="module-card-subtitle">Rapat, agenda pimpinan, approval undangan, dan tindak lanjut notulen.</div>
                        </div>
                        <div class="module-pill rapat"><i class="fas fa-calendar-week"></i></div>
                    </div>
                    <div class="metric-grid">
                        <div class="metric-box">
                            <div class="value">{{ $meeting['stats']['total_rapat'] }}</div>
                            <div class="label">Rapat yang bisa Anda lihat</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $meeting['stats']['total_agenda'] }}</div>
                            <div class="label">Agenda pimpinan terkait Anda</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $meeting['stats']['pending_undangan'] + $meeting['stats']['pending_notulensi'] }}</div>
                            <div class="label">Approval undangan dan notulensi</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $meeting['stats']['pending_tindak_lanjut'] }}</div>
                            <div class="label">Tindak lanjut notulen pending</div>
                        </div>
                    </div>
                    <div class="module-link-row">
                        <a href="{{ route('rapat.index') }}"><i class="fas fa-users"></i> Rapat</a>
                        <a href="{{ route('rapat.absensi.index') }}" class="alt"><i class="fas fa-clipboard-check"></i> Absensi</a>
                        <a href="{{ route('rapat.laporan.index') }}"><i class="fas fa-file-pdf"></i> Laporan</a>
                    </div>
                </article>
            @endif

            @if($leave['enabled'])
                <article class="module-card">
                    <div class="module-card-head">
                        <div>
                            <div class="module-card-title">Cuti</div>
                            <div class="module-card-subtitle">Pengajuan cuti Anda, status proses, dan approval yang perlu ditindaklanjuti.</div>
                        </div>
                        <div class="module-pill cuti"><i class="fas fa-calendar-check"></i></div>
                    </div>
                    <div class="metric-grid">
                        <div class="metric-box">
                            <div class="value">{{ $leave['stats']['pengajuan_saya'] }}</div>
                            <div class="label">Total pengajuan saya</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $leave['stats']['diproses'] }}</div>
                            <div class="label">Pengajuan sedang diproses</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $leave['stats']['disetujui'] }}</div>
                            <div class="label">Pengajuan disetujui / selesai</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $leave['stats']['approval_pending'] }}</div>
                            <div class="label">Approval cuti pending</div>
                        </div>
                    </div>
                    <div class="module-link-row">
                        <a href="{{ route('cuti.index') }}"><i class="fas fa-calendar-alt"></i> Pengajuan Cuti</a>
                        <a href="{{ route('cuti.reports.index') }}" class="alt"><i class="fas fa-chart-bar"></i> Laporan Cuti</a>
                    </div>
                </article>
            @endif
        </section>

        <section class="dashboard-row">
            <div class="dash-panel">
                <div class="dash-panel-head">
                    <div>
                        <h5>Yang Perlu Ditindaklanjuti</h5>
                        <p>Daftar tugas terbaru dari persuratan, rapat / agenda, dan cuti.</p>
                    </div>
                </div>
                <div class="dash-panel-body">
                    @if($actionItems->isEmpty())
                        <div class="empty-state">Tidak ada tindak lanjut aktif saat ini.</div>
                    @else
                        <div class="action-list">
                            @foreach($actionItems as $item)
                                <div class="action-item">
                                    <div class="action-icon tone-{{ $item['tone'] }}">
                                        <i class="{{ $item['icon'] }}"></i>
                                    </div>
                                    <div>
                                        <div class="item-title">{{ $item['title'] }}</div>
                                        <div class="item-subtitle">{{ $item['subtitle'] }}</div>
                                        <div class="item-description">{{ $item['description'] }}</div>
                                        <div class="item-meta">{{ $item['module'] }} • {{ $item['time'] }}</div>
                                    </div>
                                    <a href="{{ $item['url'] }}" class="item-link" title="Buka">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="dash-panel">
                <div class="dash-panel-head">
                    <div>
                        <h5>Jadwal Rapat / Agenda Terdekat</h5>
                        <p>Item mendatang yang berada dalam jangkauan akses Anda.</p>
                    </div>
                </div>
                <div class="dash-panel-body">
                    @if(!$meeting['enabled'] || $meeting['upcoming']->isEmpty())
                        <div class="empty-state">Belum ada rapat atau agenda mendatang.</div>
                    @else
                        <div class="upcoming-list">
                            @foreach($meeting['upcoming'] as $item)
                                <div class="upcoming-item">
                                    <div>
                                        <div class="item-title">{{ $item['title'] }}</div>
                                        <div class="item-subtitle">{{ $item['meta'] }}</div>
                                        <div class="item-description">{{ $item['submeta'] }}</div>
                                    </div>
                                    <div class="list-badge">
                                        {!! $item['badge'] !!}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="recent-columns">
            @if($persuratan['enabled'])
                <div class="dash-panel">
                    <div class="dash-panel-head">
                        <div>
                            <h5>Persuratan Terbaru</h5>
                            <p>Surat masuk dan surat keluar terakhir yang relevan.</p>
                        </div>
                    </div>
                    <div class="dash-panel-body">
                        @if($persuratan['recent']->isEmpty())
                            <div class="empty-state">Belum ada data persuratan.</div>
                        @else
                            <div class="recent-list">
                                @foreach($persuratan['recent'] as $item)
                                    <div class="recent-item">
                                        <div>
                                            <div class="item-title">{{ $item['title'] }}</div>
                                            <div class="item-subtitle">{{ $item['type'] }} • {{ $item['subtitle'] }}</div>
                                            <div class="item-meta">{{ $item['meta'] }}</div>
                                        </div>
                                        <div class="list-badge">{!! $item['badge'] !!}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($meeting['enabled'])
                <div class="dash-panel">
                    <div class="dash-panel-head">
                        <div>
                            <h5>Rapat / Agenda Terbaru</h5>
                            <p>Dokumen rapat dan agenda terbaru yang bisa Anda akses.</p>
                        </div>
                    </div>
                    <div class="dash-panel-body">
                        @if($meeting['recent']->isEmpty())
                            <div class="empty-state">Belum ada data rapat atau agenda.</div>
                        @else
                            <div class="recent-list">
                                @foreach($meeting['recent'] as $item)
                                    <div class="recent-item">
                                        <div>
                                            <div class="item-title">{{ $item['title'] }}</div>
                                            <div class="item-subtitle">{{ $item['type'] }} • {{ $item['subtitle'] }}</div>
                                            <div class="item-meta">{{ $item['meta'] }}</div>
                                        </div>
                                        <div class="list-badge">{!! $item['badge'] !!}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($leave['enabled'])
                <div class="dash-panel">
                    <div class="dash-panel-head">
                        <div>
                            <h5>Cuti Terbaru</h5>
                            <p>Pengajuan cuti terbaru yang relevan dengan peran Anda.</p>
                        </div>
                    </div>
                    <div class="dash-panel-body">
                        @if($leave['recent']->isEmpty())
                            <div class="empty-state">Belum ada data cuti.</div>
                        @else
                            <div class="recent-list">
                                @foreach($leave['recent'] as $item)
                                    <div class="recent-item">
                                        <div>
                                            <div class="item-title">{{ $item['title'] }}</div>
                                            <div class="item-subtitle">{{ $item['subtitle'] }}</div>
                                            <div class="item-meta">{{ $item['meta'] }}</div>
                                        </div>
                                        <div class="list-badge">{!! $item['badge'] !!}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
