<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 portrait; margin: 1.6cm; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        .title { font-size: 18px; font-weight: 700; margin-bottom: 6px; }
        .subtitle { font-size: 12px; color: #475569; margin-bottom: 16px; }
        .box { border: 1px solid #cbd5e1; border-radius: 8px; padding: 14px 16px; margin-bottom: 14px; }
        .label { font-size: 10px; color: #64748b; margin-bottom: 2px; }
        .value { font-size: 12px; font-weight: 700; margin-bottom: 10px; }
        .section { font-size: 13px; font-weight: 700; margin: 18px 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #dbe2ea; padding: 7px 8px; vertical-align: top; }
        th { background: #f8fafc; text-align: left; font-weight: 700; }
        .muted { color: #64748b; font-size: 10px; }
    </style>
</head>
<body>
    <div class="title">Bundel Eviden Progress ZI</div>
    <div class="subtitle">Daftar tautan Google Drive dan lampiran eviden lama untuk sub poin monitoring Zona Integritas. Isi dokumen Google Drive diperiksa melalui tautannya sesuai izin akses yang diberikan.</div>

    <div class="box">
        <div class="label">Area</div>
        <div class="value">{{ optional($activity->area)->code }} - {{ optional($activity->area)->name }}</div>

        <div class="label">Sub Poin Pedoman</div>
        <div class="value">{{ optional(optional($activity->guidelineSubPoint)->point)->code }}.{{ optional($activity->guidelineSubPoint)->display_code }} {{ optional($activity->guidelineSubPoint)->title }}</div>

        <div class="label">Kegiatan</div>
        <div class="value">{{ $activity->name }}</div>
    </div>

    @php $guide = $activity->guidelineSubPoint ? $activity->guidelineSubPoint->evidence_guide : null; @endphp
    @if($guide)
        <div class="box">
            <div class="section" style="margin-top:0;">Panduan Penyusunan Eviden</div>
            @if($guide['notes'] ?? $guide['guidance'])
                <div class="label">Catatan/Keterangan/Penjelasan</div>
                <div style="white-space:pre-wrap;">{{ $guide['notes'] ?? $guide['guidance'] }}</div>
            @endif
            @if($guide['explanation'])
                <div class="label" style="margin-top:8px;">Penjelasan pemenuhan</div>
                <div style="white-space:pre-wrap;">{{ $guide['explanation'] }}</div>
            @endif
        </div>
    @endif
    <div class="section">Daftar Eviden</div>
    <table>
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th>Judul Eviden</th>
                <th style="width: 22%;">Sumber</th>
                <th style="width: 16%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($evidences as $index => $evidence)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $evidence->title }}</strong>
                        @if($evidence->drive_url && $evidence->preview_url)
                            <div class="muted" style="word-wrap:break-word;"><a href="{{ $evidence->preview_url }}">{{ $evidence->drive_url }}</a></div>
                        @endif
                        @if($evidence->description)
                            <div class="muted"><strong>Catatan Eviden:</strong></div>
                            <div class="muted" style="white-space:pre-wrap; word-wrap:break-word;">{{ $evidence->description }}</div>
                        @endif
                    </td>
                    <td>{{ $evidence->source_reference_label }}</td>
                    <td>{{ $evidence->status_label }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">Belum ada eviden.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
