<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        .title { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
        .meta { font-size: 10px; color: #4b5563; margin-bottom: 12px; }
        .summary { width: 100%; margin-bottom: 14px; }
        .summary td { padding: 4px 8px; border: 1px solid #d1d5db; }
        .section-title { font-size: 12px; font-weight: 700; margin: 14px 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; vertical-align: top; }
        th { background: #f3f4f6; font-weight: 700; text-align: left; }
        .small { font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>
@include('partials.pdf-verification-badge', ['pdfVerification' => $pdfVerification ?? null])
    <div class="title">Laporan Progress Zona Integritas</div>
    <div class="meta">Periode: {{ $summary['period_name'] }} | Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIT</div>

    <table class="summary">
        <tr>
            <td><strong>Area</strong><br>{{ $summary['area_count'] }}</td>
            <td><strong>Sub Poin Ditindaklanjuti</strong><br>{{ $summary['sub_point_covered_count'] }}/{{ $summary['sub_point_count'] }}</td>
            <td><strong>Sub Poin Berkala</strong><br>{{ $summary['periodic_sub_point_count'] }}</td>
            <td><strong>Kegiatan</strong><br>{{ $summary['activity_count'] }}</td>
            <td><strong>Indikator</strong><br>{{ $summary['indicator_count'] }}</td>
            <td><strong>Eviden</strong><br>{{ $summary['evidence_count'] }}</td>
            <td><strong>Rata-rata Progress</strong><br>{{ rtrim(rtrim(number_format($summary['avg_progress'], 1), '0'), '.') }}%</td>
            <td><strong>Overdue</strong><br>{{ $summary['overdue_count'] }}</td>
        </tr>
    </table>

    <div class="section-title">Nilai per Area</div>
    <table>
        <thead>
            <tr>
                <th style="width: 18%;">Area</th>
                <th>PIC</th>
                <th style="width: 18%;">Nilai</th>
            </tr>
        </thead>
        <tbody>
            @forelse($areaScores as $area)
                <tr>
                    <td>{{ $area['code'] }} - {{ $area['name'] }}</td>
                    <td>{{ $area['pic'] ?: '-' }}</td>
                    <td>{{ rtrim(rtrim(number_format($area['score'], 1), '0'), '.') }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">Belum ada data area.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Daftar Kegiatan</div>
    <table>
        <thead>
            <tr>
                <th>Kegiatan</th>
                <th style="width: 15%;">PIC</th>
                <th style="width: 14%;">Target</th>
                <th style="width: 10%;">Progress</th>
                <th style="width: 10%;">Indikator</th>
                <th style="width: 10%;">Eviden</th>
                <th style="width: 13%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($activities as $activity)
                @php
                    $indicatorAttention = $activity->indicators->whereIn('status', ['belum_diisi', 'belum_terpenuhi', 'sebagian_terpenuhi', 'ditolak'])->count();
                    $evidenceCount = $activity->realizations->sum(function ($realization) { return $realization->evidences->count(); });
                    $evidenceAttention = $activity->realizations->flatMap->evidences->whereIn('status', ['terupload', 'terhubung', 'revisi', 'tidak_valid'])->count();
                @endphp
                <tr>
                    <td>
                        <strong>{{ $activity->name }}</strong><br>
                        <span class="small">{{ optional($activity->area)->name }}</span>
                    </td>
                    <td>{{ optional($activity->pic)->name ?: '-' }}</td>
                    <td>
                        {{ optional($activity->target_start_date)->translatedFormat('d M Y') ?: '-' }}<br>
                        <span class="small">s/d {{ optional($activity->target_end_date)->translatedFormat('d M Y') ?: '-' }}</span>
                    </td>
                    <td>{{ rtrim(rtrim(number_format($activity->progress_score, 1), '0'), '.') }}%</td>
                    <td>{{ $activity->indicators->count() }}<br><span class="small">{{ $indicatorAttention }} perhatian</span></td>
                    <td>{{ $evidenceCount }}<br><span class="small">{{ $evidenceAttention }} review</span></td>
                    <td>{{ $activity->status_label }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Belum ada data kegiatan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
