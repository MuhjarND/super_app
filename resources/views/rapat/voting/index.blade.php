@extends('layouts.app')

@section('title', 'E-Voting')

@push('styles')
    <style>
        .meeting-action-toggle-col { width: 46px; }
        .meeting-action-toggle { width: 28px; height: 28px; border: none; border-radius: 8px; background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; font-size: 1rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
        .meeting-action-toggle.is-open { background: linear-gradient(135deg, #475569, #64748b); }
        .meeting-action-row { display: none; }
        .meeting-action-row td { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 12px 16px; }
        .meeting-action-panel { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .meeting-action-meta { color: #64748b; font-size: 0.82rem; margin-right: 10px; }
        .meeting-action-btn { display: inline-flex; align-items: center; gap: 8px; border-radius: 10px; padding: 7px 12px; font-size: 0.82rem; font-weight: 700; border: 1px solid transparent; background: #fff; color: #1f2937; }
        .meeting-action-btn.primary { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .meeting-action-btn.secondary { background: #f8fafc; color: #475569; border-color: #cbd5e1; }
        .meeting-action-btn.danger { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-1">E-Voting</h1>
                <div class="text-muted" style="font-size: 0.82rem;">Kelola voting, link publik, QR code, dan monitoring hasil realtime.</div>
            </div>
            <a href="{{ route('rapat.voting.create') }}" class="btn app-create-btn">
                <i class="fas fa-plus mr-1"></i> Buat Voting
            </a>
        </div>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card" style="border-radius:16px; border:1px solid #e5e7eb;">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="meeting-action-toggle-col"></th>
                        <th>Voting</th>
                        <th>Status</th>
                        <th>Peserta</th>
                        <th>Sudah Vote</th>
                        <th>Link Publik</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($votings as $voting)
                        <tr>
                            <td class="meeting-action-toggle-col">
                                <button type="button" class="meeting-action-toggle" aria-label="Toggle aksi">+</button>
                            </td>
                            <td>
                                <div class="font-weight-bold">{{ $voting->judul }}</div>
                                <div class="text-muted" style="font-size:0.78rem;">{{ \Illuminate\Support\Str::limit($voting->deskripsi, 100) }}</div>
                            </td>
                            <td>{!! $voting->status_badge !!}</td>
                            <td>{{ $voting->participantPivots->count() }}</td>
                            <td>{{ $voting->participantPivots->whereNotNull('voted_at')->count() }}</td>
                            <td><a href="{{ route('rapat.voting.public.show', $voting->public_code) }}" target="_blank">Buka Link</a></td>
                        </tr>
                        <tr class="meeting-action-row">
                            <td colspan="6">
                                <div class="meeting-action-panel">
                                    <span class="meeting-action-meta">Tindakan voting</span>
                                    <a href="{{ route('rapat.voting.show', $voting) }}" class="meeting-action-btn primary">
                                        <i class="fas fa-chart-bar"></i> Detail
                                    </a>
                                    <a href="{{ route('rapat.voting.edit', $voting) }}" class="meeting-action-btn secondary">
                                        <i class="fas fa-pen"></i> Edit
                                    </a>
                                    <form action="{{ route('rapat.voting.destroy', $voting) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="meeting-action-btn danger" onclick="return confirm('Hapus voting ini?')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada voting.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            $(document).on('click', '.meeting-action-toggle', function () {
                const $button = $(this);
                const $actionRow = $button.closest('tr').next('.meeting-action-row');
                const isOpen = $actionRow.is(':visible');

                $('.meeting-action-row').hide();
                $('.meeting-action-toggle').removeClass('is-open').text('+');

                if (!isOpen) {
                    $actionRow.show();
                    $button.addClass('is-open').text('-');
                }
            });
        });
    </script>
@endpush
