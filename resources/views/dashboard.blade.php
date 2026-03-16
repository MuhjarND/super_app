@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <style>
        /* ======================== MODERN DASHBOARD STYLES ======================== */

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, #1a3a5c 0%, #2c5282 50%, #3182ce 100%);
            border-radius: 16px;
            padding: 32px 36px;
            color: white;
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -40%;
            right: -15%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(232, 168, 56, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .welcome-banner::after {
            content: '';
            position: absolute;
            bottom: -50%;
            right: 10%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 70%);
            border-radius: 50%;
        }

        .welcome-banner h2 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 6px;
            position: relative;
            z-index: 1;
        }

        .welcome-banner h2 span {
            color: #e8a838;
        }

        .welcome-banner p {
            opacity: 0.8;
            font-size: 0.9rem;
            margin-bottom: 0;
            position: relative;
            z-index: 1;
        }

        .welcome-banner .today-stats {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 20px;
            margin-top: 16px;
        }

        .welcome-banner .today-stat {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 10px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .welcome-banner .today-stat i {
            color: #e8a838;
            font-size: 1.1rem;
        }

        .welcome-banner .today-stat .num {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .welcome-banner .today-stat .lbl {
            font-size: 0.72rem;
            opacity: 0.7;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Stat Cards */
        .stat-card {
            border-radius: 16px;
            border: none;
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .stat-card .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            margin-bottom: 16px;
        }

        .stat-card .stat-number {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 4px;
        }

        .stat-card .stat-label {
            font-size: 0.82rem;
            opacity: 0.7;
            font-weight: 500;
        }

        .stat-card .stat-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            font-size: 0.7rem;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            bottom: -20px;
            right: -20px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            opacity: 0.08;
        }

        /* Card variants */
        .stat-card.card-blue {
            background: linear-gradient(135deg, #ebf8ff 0%, #bee3f8 100%);
            color: #1a365d;
        }

        .stat-card.card-blue .stat-icon {
            background: linear-gradient(135deg, #3182ce, #2b6cb0);
            color: white;
        }

        .stat-card.card-blue::after {
            background: #2c5282;
        }

        .stat-card.card-green {
            background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
            color: #22543d;
        }

        .stat-card.card-green .stat-icon {
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
        }

        .stat-card.card-green::after {
            background: #276749;
        }

        .stat-card.card-amber {
            background: linear-gradient(135deg, #fffff0 0%, #fefcbf 100%);
            color: #744210;
        }

        .stat-card.card-amber .stat-icon {
            background: linear-gradient(135deg, #ecc94b, #d69e2e);
            color: white;
        }

        .stat-card.card-amber::after {
            background: #b7791f;
        }

        .stat-card.card-red {
            background: linear-gradient(135deg, #fff5f5 0%, #fed7d7 100%);
            color: #742a2a;
        }

        .stat-card.card-red .stat-icon {
            background: linear-gradient(135deg, #fc8181, #e53e3e);
            color: white;
        }

        .stat-card.card-red::after {
            background: #c53030;
        }

        /* Chart Cards */
        .chart-card {
            border-radius: 16px;
            border: none;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .chart-card .card-header {
            background: white;
            border-bottom: 1px solid #edf2f7;
            padding: 20px 24px;
        }

        .chart-card .card-header h5 {
            font-weight: 700;
            color: #1a202c;
            font-size: 1rem;
            margin: 0;
        }

        .chart-card .card-header .subtitle {
            font-size: 0.78rem;
            color: #a0aec0;
            margin: 0;
        }

        .chart-card .card-body {
            padding: 24px;
        }

        /* Activity Timeline */
        .activity-timeline {
            position: relative;
            padding-left: 24px;
        }

        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(180deg, #e2e8f0 0%, transparent 100%);
        }

        .activity-item {
            position: relative;
            padding-bottom: 20px;
            padding-left: 16px;
        }

        .activity-item:last-child {
            padding-bottom: 0;
        }

        .activity-item::before {
            content: '';
            position: absolute;
            left: -20px;
            top: 6px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #e2e8f0;
        }

        .activity-item.type-masuk::before {
            background: #4299e1;
            box-shadow: 0 0 0 2px #bee3f8;
        }

        .activity-item.type-disposisi::before {
            background: #ed8936;
            box-shadow: 0 0 0 2px #feebc8;
        }

        .activity-item.type-keluar::before {
            background: #48bb78;
            box-shadow: 0 0 0 2px #c6f6d5;
        }

        .activity-item .activity-title {
            font-weight: 600;
            font-size: 0.875rem;
            color: #2d3748;
        }

        .activity-item .activity-desc {
            font-size: 0.8rem;
            color: #718096;
            margin-top: 2px;
        }

        .activity-item .activity-time {
            font-size: 0.72rem;
            color: #a0aec0;
            margin-top: 4px;
        }

        /* Quick Action Buttons */
        .quick-action {
            border-radius: 12px;
            border: 2px solid #edf2f7;
            padding: 20px;
            text-align: center;
            transition: all 0.2s ease;
            background: white;
            text-decoration: none !important;
            display: block;
            color: #4a5568;
        }

        .quick-action:hover {
            border-color: #4299e1;
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(66, 153, 225, 0.15);
            color: #2b6cb0;
        }

        .quick-action .qa-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 1.2rem;
        }

        .quick-action .qa-label {
            font-weight: 600;
            font-size: 0.82rem;
        }

        /* Disposisi items */
        .disposisi-item {
            padding: 14px 0;
            border-bottom: 1px solid #f7fafc;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .disposisi-item:last-child {
            border-bottom: none;
        }

        .disposisi-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        /* Status pips */
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        .status-dot.pending {
            background: #ecc94b;
        }

        .status-dot.done {
            background: #48bb78;
        }

        body.theme-dark .welcome-banner {
            background: linear-gradient(135deg, #0f172a 0%, #172554 45%, #1d4ed8 100%);
        }

        body.theme-dark .chart-card,
        body.theme-dark .chart-card .card-header {
            background: #111827;
            border-color: #1f2937;
        }

        body.theme-dark .chart-card .card-header h5,
        body.theme-dark .activity-item .activity-title,
        body.theme-dark .quick-action .qa-label {
            color: #e5e7eb;
        }

        body.theme-dark .chart-card .card-header .subtitle,
        body.theme-dark .activity-item .activity-desc,
        body.theme-dark .activity-item .activity-time {
            color: #94a3b8;
        }

        body.theme-dark .activity-timeline::before {
            background: linear-gradient(180deg, #334155 0%, transparent 100%);
        }

        body.theme-dark .activity-item::before {
            border-color: #111827;
            box-shadow: 0 0 0 2px #334155;
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header" style="padding-bottom: 0;">
        <div class="container-fluid"></div>
    </div>
@endsection

@section('content')
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h2>Selamat Datang, <span>{{ auth()->user()->name }}</span></h2>
                <p>{{ now()->translatedFormat('l, d F Y') }} | Pengadilan Tinggi Agama Papua Barat</p>
                <div class="today-stats">
                    <div class="today-stat">
                        <i class="fas fa-inbox"></i>
                        <div>
                            <div class="num">{{ $todayMasuk }}</div>
                            <div class="lbl">Masuk Hari Ini</div>
                        </div>
                    </div>
                    <div class="today-stat">
                        <i class="fas fa-paper-plane"></i>
                        <div>
                            <div class="num">{{ $todayKeluar }}</div>
                            <div class="lbl">Keluar Hari Ini</div>
                        </div>
                    </div>
                    @if($disposisiPending > 0)
                        <div class="today-stat" style="border-color: rgba(237,137,54,0.4); background: rgba(237,137,54,0.15);">
                            <i class="fas fa-bell" style="color: #fbd38d;"></i>
                            <div>
                                <div class="num">{{ $disposisiPending }}</div>
                                <div class="lbl">Disposisi Menunggu</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <a href="{{ route('surat-masuk.index') }}" style="text-decoration: none;">
                <div class="stat-card card-blue">
                    <div class="stat-icon"><i class="fas fa-inbox"></i></div>
                    <div class="stat-number">{{ $totalSuratMasuk }}</div>
                    <div class="stat-label">Total Surat Masuk</div>
                    @if($suratMasukBaru > 0)
                        <span class="stat-badge" style="background: #4299e1; color: white;">{{ $suratMasukBaru }} Baru</span>
                    @endif
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <a href="{{ route('surat-keluar.index') }}" style="text-decoration: none;">
                <div class="stat-card card-green">
                    <div class="stat-icon"><i class="fas fa-paper-plane"></i></div>
                    <div class="stat-number">{{ $totalSuratKeluar }}</div>
                    <div class="stat-label">Total Surat Keluar</div>
                    @if($suratKeluarDraft > 0)
                        <span class="stat-badge" style="background: #ed8936; color: white;">{{ $suratKeluarDraft }} Draft</span>
                    @endif
                </div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card card-amber">
                <div class="stat-icon"><i class="fas fa-envelope-open-text"></i></div>
                <div class="stat-number">{{ $suratMasukBaru }}</div>
                <div class="stat-label">Surat Belum Diproses</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card card-red">
                <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                <div class="stat-number">{{ $disposisiPending }}</div>
                <div class="stat-label">Disposisi Menunggu</div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4" style="align-items: flex-start;">
        <!-- Bar Chart: Monthly Stats -->
        <div class="col-lg-8 mb-3">
            <div class="card chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5><i class="fas fa-chart-bar mr-2" style="color: #4299e1;"></i>Statistik Persuratan</h5>
                        <p class="subtitle">Data 6 bulan terakhir</p>
                    </div>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 280px;">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doughnut Chart: Status Distribution -->
        <div class="col-lg-4 mb-3">
            <div class="card chart-card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie mr-2" style="color: #ed8936;"></i>Status Surat Masuk</h5>
                    <p class="subtitle">Distribusi status saat ini</p>
                </div>
                <div class="card-body" style="text-align: center;">
                    <div style="position: relative; width: 180px; height: 180px; margin: 0 auto;">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="d-flex justify-content-center gap-3 mt-3" style="gap: 16px;">
                        <div class="text-center">
                            <span class="status-dot" style="background: #4299e1;"></span>
                            <small class="text-muted">Baru ({{ $statusData['baru'] }})</small>
                        </div>
                        <div class="text-center">
                            <span class="status-dot" style="background: #ed8936;"></span>
                            <small class="text-muted">Proses ({{ $statusData['didisposisi'] }})</small>
                        </div>
                        <div class="text-center">
                            <span class="status-dot" style="background: #48bb78;"></span>
                            <small class="text-muted">Selesai ({{ $statusData['selesai'] }})</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row -->
    <div class="row">
        <!-- Activity Timeline -->
        <div class="col-lg-5 mb-3">
            <div class="card chart-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5><i class="fas fa-stream mr-2" style="color: #9f7aea;"></i>Aktivitas Terbaru</h5>
                        <p class="subtitle">Surat masuk dan disposisi</p>
                    </div>
                    <a href="{{ route('surat-masuk.index') }}" class="btn btn-sm"
                        style="background: #edf2f7; color: #4a5568; font-weight: 600; border-radius: 8px;">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <div class="activity-timeline">
                        @forelse($recentSuratMasuk as $surat)
                            <div class="activity-item type-masuk">
                                <div class="activity-title">{{ $surat->nomor_surat }}</div>
                                <div class="activity-desc">
                                    <span
                                        style="background: {{ $surat->opsi_pengirim == 'mahkamah_agung' ? '#48bb78' : '#ed8936' }}; color: white; padding: 1px 8px; border-radius: 4px; font-size: 0.7rem;">
                                        {{ $surat->opsi_pengirim == 'mahkamah_agung' ? 'MA' : 'Non-MA' }}
                                    </span>
                                    {{ $surat->pengirim }} | {{ Str::limit($surat->perihal, 40) }}
                                </div>
                                <div class="activity-time"><i
                                        class="far fa-clock mr-1"></i>{{ $surat->created_at->diffForHumans() }}</div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-inbox mb-2 d-block" style="font-size: 1.5rem; opacity: 0.3;"></i>
                                Belum ada aktivitas
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Disposisi for current user -->
        <div class="col-lg-4 mb-3">
            <div class="card chart-card">
                <div class="card-header">
                    <h5><i class="fas fa-bell mr-2" style="color: #ed8936;"></i>Disposisi Anda</h5>
                    <p class="subtitle">Surat yang perlu ditindaklanjuti</p>
                </div>
                <div class="card-body">
                    @forelse($recentDisposisi as $disposisi)
                        <div class="disposisi-item">
                            <div class="disposisi-avatar"
                                style="background: linear-gradient(135deg, #ebf8ff, #bee3f8); color: #2c5282;">
                                {{ strtoupper(substr($disposisi->dariUser->name, 0, 2)) }}
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 600; font-size: 0.85rem; color: #2d3748;">
                                    {{ $disposisi->suratMasuk->nomor_surat }}
                                </div>
                                <div style="font-size: 0.78rem; color: #718096;">
                                    Dari: {{ $disposisi->dariUser->name }}
                                </div>
                                @if($disposisi->catatan)
                                    <div style="font-size: 0.75rem; color: #a0aec0; margin-top: 2px;">
                                        <i class="fas fa-comment-alt mr-1"></i>{{ Str::limit($disposisi->catatan, 50) }}
                                    </div>
                                @endif
                                <div style="font-size: 0.7rem; color: #cbd5e0; margin-top: 4px;">
                                    {{ $disposisi->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <div>
                                @if($disposisi->status == 'pending')
                                    <span
                                        style="background: #fefcbf; color: #975a16; padding: 3px 10px; border-radius: 20px; font-size: 0.68rem; font-weight: 600;">Pending</span>
                                @else
                                    <span
                                        style="background: #c6f6d5; color: #276749; padding: 3px 10px; border-radius: 20px; font-size: 0.68rem; font-weight: 600;">Done</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-check-circle d-block mb-2" style="font-size: 2rem; color: #c6f6d5;"></i>
                            <span style="font-size: 0.85rem;">Tidak ada disposisi pending</span>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-3 mb-3">
            <div class="card chart-card">
                <div class="card-header">
                    <h5><i class="fas fa-bolt mr-2" style="color: #ecc94b;"></i>Aksi Cepat</h5>
                    <p class="subtitle">Pintasan menu</p>
                </div>
                <div class="card-body">
                    <div class="row" style="row-gap: 12px;">
                        <div class="col-6">
                            <a href="{{ route('surat-masuk.index') }}" class="quick-action">
                                <div class="qa-icon"
                                    style="background: linear-gradient(135deg, #ebf8ff, #bee3f8); color: #2b6cb0;">
                                    <i class="fas fa-inbox"></i>
                                </div>
                                <div class="qa-label">Surat Masuk</div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('surat-keluar.index') }}" class="quick-action">
                                <div class="qa-icon"
                                    style="background: linear-gradient(135deg, #f0fff4, #c6f6d5); color: #276749;">
                                    <i class="fas fa-paper-plane"></i>
                                </div>
                                <div class="qa-label">Surat Keluar</div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('arsip.index') }}" class="quick-action">
                                <div class="qa-icon"
                                    style="background: linear-gradient(135deg, #faf5ff, #e9d8fd); color: #6b46c1;">
                                    <i class="fas fa-archive"></i>
                                </div>
                                <div class="qa-label">Arsip</div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="#" class="quick-action">
                                <div class="qa-icon"
                                    style="background: linear-gradient(135deg, #fffff0, #fefcbf); color: #975a16;">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="qa-label">Rapat</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        $(document).ready(function () {
            // Chart.js defaults
            Chart.defaults.font.family = "'Inter', sans-serif";
            Chart.defaults.color = '#718096';

            // Monthly Bar Chart
            var monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            var gradient1 = monthlyCtx.createLinearGradient(0, 0, 0, 280);
            gradient1.addColorStop(0, 'rgba(66, 153, 225, 0.8)');
            gradient1.addColorStop(1, 'rgba(66, 153, 225, 0.2)');
            var gradient2 = monthlyCtx.createLinearGradient(0, 0, 0, 280);
            gradient2.addColorStop(0, 'rgba(72, 187, 120, 0.8)');
            gradient2.addColorStop(1, 'rgba(72, 187, 120, 0.2)');

            new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: {!! json_encode(array_column($monthlyData, 'month')) !!},
                    datasets: [
                        {
                            label: 'Surat Masuk',
                            data: {!! json_encode(array_column($monthlyData, 'masuk')) !!},
                            backgroundColor: gradient1,
                            borderColor: '#4299e1',
                            borderWidth: 2,
                            borderRadius: 8,
                            borderSkipped: false,
                        },
                        {
                            label: 'Surat Keluar',
                            data: {!! json_encode(array_column($monthlyData, 'keluar')) !!},
                            backgroundColor: gradient2,
                            borderColor: '#48bb78',
                            borderWidth: 2,
                            borderRadius: 8,
                            borderSkipped: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'rectRounded',
                                padding: 16,
                                font: { weight: '600', size: 12 }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1a202c',
                            titleFont: { weight: '600' },
                            padding: 12,
                            cornerRadius: 8,
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { weight: '500' } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f0f4f8', drawBorder: false },
                            ticks: {
                                stepSize: 1,
                                font: { weight: '500' }
                            }
                        }
                    }
                }
            });

            // Doughnut Chart
            var statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Baru', 'Diproses', 'Selesai'],
                    datasets: [{
                        data: [{{ $statusData['baru'] }}, {{ $statusData['didisposisi'] }}, {{ $statusData['selesai'] }}],
                        backgroundColor: [
                            '#4299e1',
                            '#ed8936',
                            '#48bb78'
                        ],
                        borderWidth: 0,
                        hoverOffset: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1a202c',
                            padding: 12,
                            cornerRadius: 8,
                            titleFont: { weight: '600' },
                        }
                    }
                }
            });
        });
    </script>
@endpush
