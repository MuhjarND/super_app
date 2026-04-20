@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap: 12px;">
                <div>
                    <h1 class="m-0">Audit Trail</h1>
                    <small class="text-muted">Riwayat aktivitas lintas modul untuk kebutuhan monitoring dan pengawasan.</small>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-md-3"><div class="small-box bg-white border"><div class="inner"><h3>{{ $summary['total'] }}</h3><p>Total aktivitas</p></div></div></div>
            <div class="col-md-3"><div class="small-box bg-white border"><div class="inner"><h3>{{ $summary['today'] }}</h3><p>Hari ini</p></div></div></div>
            <div class="col-md-3"><div class="small-box bg-white border"><div class="inner"><h3>{{ $summary['persuratan'] }}</h3><p>Persuratan</p></div></div></div>
            <div class="col-md-3"><div class="small-box bg-white border"><div class="inner"><h3>{{ $summary['approval'] }}</h3><p>Approval dan review</p></div></div></div>
        </div>

        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Filter Audit</h3></div>
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-md-3 form-group">
                        <label>Modul</label>
                        <select name="module" class="form-control">
                            <option value="all">Semua Modul</option>
                            @foreach($moduleOptions as $module)
                                <option value="{{ $module }}" {{ $filters['module'] === $module ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $module)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Event</label>
                        <select name="event" class="form-control">
                            <option value="all">Semua Event</option>
                            @foreach($eventOptions as $event)
                                <option value="{{ $event }}" {{ $filters['event'] === $event ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $event)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Aktor</label>
                        <select name="actor" class="form-control">
                            <option value="all">Semua Aktor</option>
                            @foreach($actorOptions as $actor)
                                <option value="{{ $actor }}" {{ $filters['actor'] === $actor ? 'selected' : '' }}>{{ $actor }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group">
                        <label>Pencarian</label>
                        <input type="text" name="search" value="{{ $filters['search'] }}" class="form-control" placeholder="Judul, catatan, aktor">
                    </div>
                    <div class="col-12 d-flex justify-content-end" style="gap:8px;">
                        <a href="{{ route('audit-trail.index') }}" class="btn btn-light border">Reset</a>
                        <button type="submit" class="btn btn-primary">Terapkan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Riwayat Aktivitas</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Modul</th>
                            <th>Event</th>
                            <th>Subjek</th>
                            <th>Aktor</th>
                            <th>Tujuan</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($audits as $audit)
                            <tr>
                                <td data-label="Waktu">{{ optional($audit->created_at)->translatedFormat('d M Y H:i') ?: '-' }}</td>
                                <td data-label="Modul">{{ ucwords(str_replace('_', ' ', $audit->module)) }}</td>
                                <td data-label="Event">{{ ucwords(str_replace('_', ' ', $audit->event)) }}</td>
                                <td data-label="Subjek">{{ $audit->subject_title ?: '-' }}</td>
                                <td data-label="Aktor">{{ $audit->actor_name ?: '-' }}</td>
                                <td data-label="Tujuan">{{ $audit->target_name ?: '-' }}</td>
                                <td data-label="Catatan">{{ $audit->note ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data audit.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $audits->links() }}</div>
        </div>
    </div>
@endsection
