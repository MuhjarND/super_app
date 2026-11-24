<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Barang Keluar</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111827; }
        .report-header { text-align: center; margin-bottom: 18px; }
        h1 { font-size: 16pt; margin: 6px 0; }
        p { margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #64748b; padding: 6px; vertical-align: top; }
        th { background: #e2e8f0; text-align: left; }
        .nowrap { white-space: nowrap; }
        .empty { text-align: center; }
    </style>
</head>
<body>
    <div class="report-header">
        <p>Pengadilan Tinggi Agama Papua Barat</p>
        <h1>Laporan Barang Keluar</h1>
        <p>Periode {{ \Carbon\Carbon::parse($filters['from'])->format('d/m/Y') }} s.d. {{ \Carbon\Carbon::parse($filters['to'])->format('d/m/Y') }}</p>
        <p>{{ $pickups->count() }} catatan pengambilan</p>
        @if(($filters['search'] ?? '') !== '')
            <p>Kata kunci: {{ $filters['search'] }}</p>
        @endif
    </div>
    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Tanggal</th>
                <th>No. Pengajuan</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Pegawai</th>
                <th>Keperluan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pickups as $pickup)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="nowrap">{{ $pickup->pickup_date->format('d/m/Y') }}</td>
                    <td>{{ optional($pickup->request)->request_number ?: '-' }}</td>
                    <td>{{ $pickup->item_name_snapshot }}</td>
                    <td>{{ $pickup->quantity_label }}</td>
                    <td>{{ optional($pickup->user)->name ?: '-' }}</td>
                    <td>{{ $pickup->purpose ?: '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Tidak ada pengambilan barang yang sesuai dengan filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
