@extends('layouts.app')

@section('title', 'Absensi Rapat')

@push('styles')
    <style>
        .attendance-card {
            border-radius: 16px;
            border: 1px solid #e5e7eb;
        }

        .attendance-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 14px;
        }

        .attendance-stat {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 14px 16px;
            background: linear-gradient(180deg, #fff, #f8fafc);
        }

        .attendance-stat__label {
            font-size: 0.76rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .attendance-stat__value {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            margin-top: 6px;
        }

        .attendance-table thead th {
            font-size: 0.73rem;
            text-transform: uppercase;
            color: #64748b;
            border-top: none;
        }

        .attendance-table tbody td {
            vertical-align: top;
            font-size: 0.85rem;
        }

        .meeting-action-toggle-col { width: 46px; }
        .meeting-action-toggle {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .meeting-action-toggle.is-open { background: linear-gradient(135deg, #475569, #64748b); }
        .meeting-action-row { display: none; }
        .meeting-action-row td { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 12px 16px; }
        .meeting-action-panel { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .meeting-action-meta { color: #64748b; font-size: 0.82rem; margin-right: 10px; }
        .meeting-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 10px;
            padding: 7px 12px;
            font-size: 0.82rem;
            font-weight: 700;
            border: 1px solid transparent;
            background: #fff;
            color: #1f2937;
        }
        .meeting-action-btn.primary { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .meeting-action-btn.secondary { background: #f8fafc; color: #475569; border-color: #cbd5e1; }

        .attendance-progress {
            margin-top: 6px;
        }

        .attendance-progress-track {
            height: 10px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .attendance-progress-fill {
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, #2563eb, #60a5fa);
        }

        .attendance-progress-meta {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 5px;
            font-size: 0.76rem;
            color: #64748b;
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="mb-1">Absensi Rapat</h1>
            <div class="text-muted" style="font-size: 0.82rem;">Rekap peserta hadir, external, dan tautan absensi publik per rapat.</div>
        </div>
    </div>
@endsection

@section('content')
    @php
        $totalRapat = $rapats->count();
        $totalPeserta = $rapats->sum(function ($rapat) { return $rapat->pesertas->count(); });
        $totalHadir = $rapats->sum(function ($rapat) { return $rapat->internalAttendances->count(); });
        $totalGuest = $rapats->sum(function ($rapat) { return $rapat->guestAttendances->count(); });
    @endphp

    <div class="attendance-summary mb-3">
        <div class="attendance-stat">
            <div class="attendance-stat__label">Total Rapat</div>
            <div class="attendance-stat__value">{{ $totalRapat }}</div>
        </div>
        <div class="attendance-stat">
            <div class="attendance-stat__label">Undangan Internal</div>
            <div class="attendance-stat__value">{{ $totalPeserta }}</div>
        </div>
        <div class="attendance-stat">
            <div class="attendance-stat__label">Sudah Absen</div>
            <div class="attendance-stat__value">{{ $totalHadir }}</div>
        </div>
        <div class="attendance-stat">
            <div class="attendance-stat__label">External</div>
            <div class="attendance-stat__value">{{ $totalGuest }}</div>
        </div>
    </div>

    <div class="card attendance-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 attendance-table">
                    <thead>
                        <tr>
                            <th class="meeting-action-toggle-col"></th>
                            <th>Rapat</th>
                            <th>Waktu WIT</th>
                            <th>Peserta</th>
                            <th>Hadir</th>
                            <th>External</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rapats as $rapat)
                            @php
                                $participantCount = $rapat->pesertas->count();
                                $attendedCount = $rapat->internalAttendances->count();
                                $guestCount = $rapat->guestAttendances->count();
                                $remainingCount = max($participantCount - $attendedCount, 0);
                                $attendancePercent = $participantCount > 0 ? round(($attendedCount / $participantCount) * 100) : 0;
                            @endphp
                            <tr>
                                <td class="meeting-action-toggle-col">
                                    <button type="button" class="meeting-action-toggle" aria-label="Toggle aksi">+</button>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a;">{{ $rapat->judul }}</div>
                                    <div style="font-size: 0.78rem; color: #64748b;">{{ $rapat->nomor_undangan }}</div>
                                </td>
                                <td>
                                    <div>{{ optional($rapat->tanggal)->translatedFormat('d M Y') }}</div>
                                    <div style="font-size: 0.78rem; color: #64748b;">{{ $rapat->waktu_mulai_formatted }} WIT</div>
                                </td>
                                <td>{{ $participantCount }} undangan</td>
                                <td>
                                    <div class="font-weight-bold">{{ $attendedCount }} / {{ $participantCount }}</div>
                                    <div class="attendance-progress">
                                        <div class="attendance-progress-track">
                                            <div class="attendance-progress-fill" style="width: {{ $attendancePercent }}%;"></div>
                                        </div>
                                        <div class="attendance-progress-meta">
                                            <span>{{ $attendancePercent }}% hadir</span>
                                            <span>{{ $remainingCount }} belum hadir</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $guestCount }}</td>
                                <td>{!! $rapat->status_badge !!}</td>
                            </tr>
                            <tr class="meeting-action-row">
                                <td colspan="7">
                                    <div class="meeting-action-panel">
                                        <span class="meeting-action-meta">Tindakan absensi</span>
                                        <a href="{{ route('rapat.absensi.show', $rapat) }}" class="meeting-action-btn primary">
                                            <i class="fas fa-clipboard-list"></i> Rekap
                                        </a>
                                        <a href="{{ route('rapat.absensi.pdf', $rapat) }}" target="_blank" class="meeting-action-btn secondary">
                                            <i class="fas fa-file-pdf"></i> PDF
                                        </a>
                                        <button type="button" class="meeting-action-btn secondary" onclick="copyPublicLink('{{ route('rapat.absensi.public.show', $rapat->public_code) }}')">
                                            <i class="fas fa-link"></i> Link Publik
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada data rapat untuk absensi.</td>
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
        function copyPublicLink(url) {
            navigator.clipboard.writeText(url).then(function () {
                showToast('Tautan absensi publik berhasil disalin.', 'success');
            }).catch(function () {
                showToast('Gagal menyalin tautan absensi publik.', 'error');
            });
        }

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
