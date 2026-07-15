@extends('layouts.app')
@section('title', 'Laporan Anggota')
@section('page-title', 'Laporan Data Anggota')

@section('content')
<div class="page-header">
    <div><h1>Laporan Data Anggota</h1><p>Total {{ count($members) }} anggota</p></div>
    <div class="d-flex gap-2">
        <a href="{{ route('library.reports.members', array_merge(request()->query(), ['export' => 'pdf'])) }}" class="btn btn-danger btn-sm">
            <i class="bi bi-file-pdf me-1"></i> Export PDF
        </a>
        <a href="{{ route('library.reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="aktif" {{ request('status')=='aktif'?'selected':'' }}>Aktif</option>
                    <option value="nonaktif" {{ request('status')=='nonaktif'?'selected':'' }}>Non-Aktif</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-modern mb-0">
                <thead>
                    <tr><th>#</th><th>Nama</th><th>No. Anggota</th><th>Kelamin</th><th>Kelas</th><th>HP</th><th>Status</th><th>Total Pinjam</th><th>Aktif</th></tr>
                </thead>
                <tbody>
                    @forelse($members as $i => $m)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td style="font-weight:600;font-size:13.5px;">{{ $m->name }}</td>
                        <td><code style="font-size:12px;">{{ $m->member_number }}</code></td>
                        <td>{{ $m->gender == 'L' ? 'L' : 'P' }}</td>
                        <td>{{ $m->class_position ?? '—' }}</td>
                        <td>{{ $m->phone ?? '—' }}</td>
                        <td><span class="badge bg-{{ $m->status == 'aktif' ? 'success' : 'secondary' }}">{{ ucfirst($m->status) }}</span></td>
                        <td>{{ $m->loans_count }}</td>
                        <td>{{ $m->active_loans_count }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">Tidak ada data</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
