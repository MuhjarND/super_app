@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Selamat datang, ' . auth()->user()->name)

@push('styles')
<style>
    .chart-container { position: relative; height: 280px; }
    .due-soon-badge { font-size: 10.5px; }
    .recent-loan-row td { padding: 10px 16px; }
</style>
@endpush

@section('content')
<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-2">
        <div class="stat-card stat-card-primary h-100">
            <div class="stat-icon" style="background:rgba(255,255,255,.15);">
                <i class="bi bi-book-fill"></i>
            </div>
            <div class="stat-value">{{ number_format($totalBooks) }}</div>
            <div class="stat-label">Total Buku</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="stat-card stat-card-info h-100">
            <div class="stat-icon" style="background:rgba(255,255,255,.15);">
                <i class="bi bi-collection-fill"></i>
            </div>
            <div class="stat-value">{{ number_format($totalCopies) }}</div>
            <div class="stat-label">Total Eksemplar</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="stat-card stat-card-success h-100">
            <div class="stat-icon" style="background:rgba(255,255,255,.15);">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="stat-value">{{ number_format($totalMembers) }}</div>
            <div class="stat-label">Total Anggota</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="stat-card stat-card-warning h-100">
            <div class="stat-icon" style="background:rgba(255,255,255,.15);">
                <i class="bi bi-arrow-left-right"></i>
            </div>
            <div class="stat-value">{{ number_format($activeLoans) }}</div>
            <div class="stat-label">Dipinjam Aktif</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="stat-card stat-card-danger h-100">
            <div class="stat-icon" style="background:rgba(255,255,255,.15);">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stat-value">{{ number_format($lateLoanCount) }}</div>
            <div class="stat-label">Terlambat</div>
        </div>
    </div>
    <div class="col-6 col-lg-2">
        <div class="stat-card stat-card-dark h-100">
            <div class="stat-icon" style="background:rgba(255,255,255,.15);">
                <i class="bi bi-cash-coin"></i>
            </div>
            <div class="stat-value" style="font-size:20px;">Rp{{ number_format($totalFines, 0, ',', '.') }}</div>
            <div class="stat-label">Denda Belum Bayar</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Chart -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div>
                    <i class="bi bi-bar-chart-fill text-primary me-2"></i>
                    Grafik Peminjaman Bulanan
                </div>
                <small class="text-muted">12 bulan terakhir</small>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="loanChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Due Soon -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-alarm-fill text-warning me-2"></i>
                Hampir/Sudah Jatuh Tempo
            </div>
            <div class="card-body p-0">
                @forelse($dueSoonLoans as $loan)
                <div class="d-flex align-items-start gap-3 p-3 border-bottom">
                    <div class="avatar-text-sm bg-{{ $loan->due_date->isPast() ? 'danger' : 'warning' }} bg-opacity-10
                        text-{{ $loan->due_date->isPast() ? 'danger' : 'warning' }}" style="font-size:11px;flex-shrink:0;">
                        <i class="bi bi-{{ $loan->due_date->isPast() ? 'exclamation' : 'clock' }}"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="fw-600" style="font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $loan->member->name }}
                        </div>
                        <div class="text-muted" style="font-size:12px;">{{ $loan->loan_number }}</div>
                        <div class="mt-1">
                            @if($loan->due_date->isPast())
                                <span class="badge bg-danger due-soon-badge">
                                    Terlambat {{ $loan->due_date->diffInDays(now()) }} hari
                                </span>
                            @else
                                <span class="badge bg-warning text-dark due-soon-badge">
                                    Jatuh tempo {{ $loan->due_date->diffForHumans() }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('library.loans.show', $loan) }}" class="btn btn-sm btn-icon btn-outline-primary">
                        <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                @empty
                <div class="empty-state" style="padding:30px;">
                    <i class="bi bi-check-circle-fill text-success" style="font-size:32px;opacity:1;margin-bottom:8px;display:block;"></i>
                    <div style="font-size:13px;color:#64748b;">Tidak ada yang jatuh tempo</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Recent Loans -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div>
            <i class="bi bi-clock-history text-primary me-2"></i>
            Peminjaman Terbaru
        </div>
        <a href="{{ route('library.loans.index') }}" class="btn btn-sm btn-outline-primary">
            Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-modern mb-0">
                <thead>
                    <tr>
                        <th>No. Pinjam</th>
                        <th>Anggota</th>
                        <th>Jumlah Buku</th>
                        <th>Tanggal Pinjam</th>
                        <th>Jatuh Tempo</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentLoans as $loan)
                    <tr class="recent-loan-row">
                        <td><code style="font-size:12px;">{{ $loan->loan_number }}</code></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-text-sm bg-primary bg-opacity-10 text-primary" style="font-size:11px;">
                                    {{ strtoupper(substr($loan->member->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:13px;">{{ $loan->member->name }}</div>
                                    <div style="font-size:11.5px;color:#94a3b8;">{{ $loan->member->member_number }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary">
                                {{ $loan->loanItems->count() }} buku
                            </span>
                        </td>
                        <td style="font-size:13px;">{{ $loan->loan_date->format('d M Y') }}</td>
                        <td style="font-size:13px;">{{ $loan->due_date->format('d M Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $loan->status_badge }} badge-status">
                                {{ ucfirst($loan->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('library.loans.show', $loan) }}" class="btn btn-sm btn-icon btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox me-2"></i>Belum ada data peminjaman
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const ctx = document.getElementById('loanChart').getContext('2d');
const loanChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: @json($chartLabels),
        datasets: [{
            label: 'Peminjaman',
            data: @json($chartData),
            backgroundColor: 'rgba(79,70,229,0.15)',
            borderColor: '#4f46e5',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#1e293b',
                titleColor: '#fff',
                bodyColor: '#94a3b8',
                padding: 12,
                borderRadius: 10,
                callbacks: {
                    label: ctx => `${ctx.parsed.y} peminjaman`
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 11 }, color: '#94a3b8' }
            },
            y: {
                grid: { color: '#f1f5f9' },
                ticks: { font: { size: 11 }, color: '#94a3b8', stepSize: 1 },
                beginAtZero: true
            }
        }
    }
});
</script>
@endpush
