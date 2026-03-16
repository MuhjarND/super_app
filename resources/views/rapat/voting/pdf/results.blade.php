<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Hasil Voting</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #111827; margin: 28px 32px; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 18px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .kop-small { font-size: 10px; }
        .title { font-size: 16px; font-weight: bold; }
        .meta td { padding: 3px 0; vertical-align: top; }
        .meta td:first-child { width: 120px; }
        .section { margin-top: 16px; }
        .section-title { font-weight: bold; font-size: 12px; margin-bottom: 8px; border-bottom: 1px solid #111827; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <div class="header">
        <div class="kop-small">PENGADILAN TINGGI AGAMA PAPUA BARAT</div>
        <div class="title">HASIL E-VOTING</div>
        <div>{{ $voting->judul }}</div>
    </div>

    <table class="meta">
        <tr><td>Judul</td><td>: {{ $voting->judul }}</td></tr>
        <tr><td>Status</td><td>: {{ ucfirst($voting->status) }}</td></tr>
        <tr><td>Jumlah Peserta</td><td>: {{ $voting->participantPivots->count() }}</td></tr>
        <tr><td>Sudah Voting</td><td>: {{ $voting->participantPivots->whereNotNull('voted_at')->count() }}</td></tr>
    </table>

    @foreach($voting->items as $item)
        <div class="section">
            <div class="section-title">{{ $item->judul }}</div>
            <table>
                <thead>
                    <tr>
                        <th style="width:35px;">No</th>
                        <th>Kandidat</th>
                        <th style="width:100px;">Suara</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($item->candidates->sortByDesc(function ($candidate) { return $candidate->votes->count(); })->values() as $index => $candidate)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $candidate->nama_snapshot }}</td>
                            <td>{{ $candidate->votes->count() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</body>
</html>
