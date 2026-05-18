<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Perawatan Alat dan Mesin</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        p { margin: 0 0 12px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #444; padding: 6px; vertical-align: top; }
        th { background: #f1f5f9; }
    </style>
</head>
<body>
@include('partials.pdf-verification-badge', ['pdfVerification' => $pdfVerification ?? null])
    <h1>Laporan Transaksi Perawatan</h1>
    <p>Periode: {{ $filters['from'] ?: '-' }} s/d {{ $filters['to'] ?: '-' }}</p>

    <table>
        <thead><tr><th>Tanggal</th><th>Barang</th><th>Sub Barang</th><th>Nominal</th><th>Deskripsi</th></tr></thead>
        <tbody>
            @foreach($transactions as $transaction)
                <tr>
                    <td>{{ optional($transaction->transaction_date)->format('d-m-Y') }}</td>
                    <td>{{ optional($transaction->item)->name ?: '-' }}</td>
                    <td>{{ optional($transaction->detail)->sub_code ?: '-' }}</td>
                    <td>Rp {{ number_format($transaction->amount, 0, ',', '.') }}</td>
                    <td>{{ $transaction->description }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot><tr><th colspan="3">Total</th><th colspan="2">Rp {{ number_format($totalAmount, 0, ',', '.') }}</th></tr></tfoot>
    </table>
</body>
</html>
