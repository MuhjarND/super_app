@extends('layouts.app')
@section('title', 'Laporan')
@section('page-title', 'Laporan Perpustakaan')
@section('page-subtitle', 'Pilih jenis laporan')

@section('content')
<div class="row g-3">
    @php
    $reports = [
        ['route' => 'reports.books', 'title' => 'Laporan Buku', 'desc' => 'Daftar seluruh koleksi buku perpustakaan', 'icon' => 'bi-book-fill', 'color' => 'primary'],
        ['route' => 'reports.members', 'title' => 'Laporan Anggota', 'desc' => 'Data seluruh anggota perpustakaan', 'icon' => 'bi-people-fill', 'color' => 'success'],
        ['route' => 'reports.loans', 'title' => 'Laporan Peminjaman', 'desc' => 'Transaksi peminjaman buku', 'icon' => 'bi-arrow-left-right', 'color' => 'warning'],
        ['route' => 'reports.returns', 'title' => 'Laporan Pengembalian', 'desc' => 'Riwayat pengembalian buku', 'icon' => 'bi-arrow-return-left', 'color' => 'info'],
        ['route' => 'reports.lates', 'title' => 'Laporan Keterlambatan', 'desc' => 'Data peminjaman yang terlambat dikembalikan', 'icon' => 'bi-clock-history', 'color' => 'danger'],
        ['route' => 'reports.fines', 'title' => 'Laporan Denda', 'desc' => 'Rekap denda keterlambatan anggota', 'icon' => 'bi-cash-coin', 'color' => 'dark'],
    ];
    @endphp

    @foreach($reports as $r)
    <div class="col-md-4">
        <a href="{{ route($r['route']) }}" class="text-decoration-none">
            <div class="card h-100" style="transition:all .2s;" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 30px rgba(0,0,0,.12)';" onmouseout="this.style.transform='';this.style.boxShadow='';">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="stat-icon bg-{{ $r['color'] }} bg-opacity-10 text-{{ $r['color'] }}" style="width:52px;height:52px;">
                            <i class="bi {{ $r['icon'] }}" style="font-size:22px;"></i>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:15px;color:#1e293b;">{{ $r['title'] }}</div>
                        </div>
                    </div>
                    <p style="font-size:13.5px;color:#64748b;margin:0;">{{ $r['desc'] }}</p>
                    <div class="mt-3 d-flex align-items-center gap-1" style="font-size:13px;color:#4f46e5;font-weight:600;">
                        Buka Laporan <i class="bi bi-arrow-right"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>
@endsection
