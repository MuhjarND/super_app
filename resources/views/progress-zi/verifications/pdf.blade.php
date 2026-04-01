<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        .title { font-size: 16px; font-weight: 700; margin-bottom: 4px; }
        .meta { font-size: 10px; color: #4b5563; margin-bottom: 12px; }
        .section-title { font-size: 12px; font-weight: 700; margin: 14px 0 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; vertical-align: top; }
        th { background: #f3f4f6; font-weight: 700; text-align: left; }
        .small { font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="title">Rekap Verifikasi Progress ZI</div>
    <div class="meta">Periode: {{ optional($period)->name ?: 'Semua Periode' }} | Dicetak: {{ now()->translatedFormat('d F Y H:i') }} WIT</div>

    <div class="section-title">Indikator Perlu Review</div>
    <table>
        <thead>
            <tr>
                <th>Indikator</th>
                <th>Kegiatan</th>
                <th>Area</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($indicators as $indicator)
                <tr>
                    <td>{{ $indicator->name }}</td>
                    <td>{{ optional($indicator->activity)->name }}</td>
                    <td>{{ optional(optional($indicator->activity)->area)->name }}</td>
                    <td>{{ $indicator->status_label }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Tidak ada indikator yang perlu review.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Eviden Perlu Review</div>
    <table>
        <thead>
            <tr>
                <th>Eviden</th>
                <th>Kegiatan</th>
                <th>Area</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($evidences as $evidence)
                <tr>
                    <td>{{ $evidence->title }}</td>
                    <td>{{ optional(optional($evidence->realization)->activity)->name }}</td>
                    <td>{{ optional(optional(optional($evidence->realization)->activity)->area)->name }}</td>
                    <td>{{ $evidence->status_label }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Tidak ada eviden yang perlu review.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
