<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>body{font-family:Arial,sans-serif;font-size:11pt;}h1{font-size:14pt;text-align:center;}
table{width:100%;border-collapse:collapse;margin-top:12px;}
th{background:#1e293b;color:white;padding:5px 6px;font-size:9pt;}
td{padding:4px 6px;border-bottom:1px solid #e2e8f0;font-size:9.5pt;}
tr:nth-child(even) td{background:#f8fafc;}.header{text-align:center;margin-bottom:12px;}
</style></head><body>
<div class="header"><h1>Laporan Denda Perpustakaan</h1><div>{{ now()->format('d M Y H:i') }}</div></div>
<table>
<thead><tr><th>#</th><th>Anggota</th><th>Buku</th><th>No. Pinjam</th><th>Terlambat</th><th>Total Denda</th><th>Status</th></tr></thead>
<tbody>
@foreach($fines as $i => $fine)
<tr>
<td>{{ $i+1 }}</td>
<td>{{ $fine->member->name }}</td>
<td>{{ $fine->loanItem->bookCopy->book->title }}</td>
<td>{{ $fine->loanItem->loan->loan_number }}</td>
<td>{{ $fine->days_late }} hari</td>
<td>Rp{{ number_format($fine->total_amount, 0, ',', '.') }}</td>
<td>{{ $fine->status == 'lunas' ? 'Lunas' : 'Belum Dibayar' }}</td>
</tr>
@endforeach
</tbody>
</table>
<div style="text-align:right;margin-top:12px;font-weight:bold;">
Total: Rp{{ number_format($fines->sum('total_amount'), 0, ',', '.') }}
</div>
</body></html>
