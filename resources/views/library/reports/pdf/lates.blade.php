<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>body{font-family:Arial,sans-serif;font-size:10pt;}h1{font-size:14pt;text-align:center;}
table{width:100%;border-collapse:collapse;margin-top:12px;}
th{background:#dc2626;color:white;padding:5px 6px;font-size:9pt;}
td{padding:4px 6px;border-bottom:1px solid #e2e8f0;font-size:9.5pt;}
tr:nth-child(even) td{background:#f8fafc;}.header{text-align:center;margin-bottom:12px;}
</style></head><body>
<div class="header"><h1>Laporan Keterlambatan</h1><div>{{ now()->format('d M Y H:i') }}</div></div>
<table>
<thead><tr><th>#</th><th>No. Pinjam</th><th>Anggota</th><th>Jatuh Tempo</th><th>Hari Terlambat</th><th>Buku</th><th>Status</th></tr></thead>
<tbody>
@foreach($loans as $i => $loan)
<tr>
<td>{{ $i+1 }}</td>
<td>{{ $loan->loan_number }}</td>
<td>{{ $loan->member->name }}</td>
<td>{{ $loan->due_date->format('d/m/Y') }}</td>
<td>{{ $loan->due_date->diffInDays(now()) }} hari</td>
<td>{{ $loan->loanItems->count() }}</td>
<td>{{ ucfirst($loan->status) }}</td>
</tr>
@endforeach
</tbody>
</table>
</body></html>
