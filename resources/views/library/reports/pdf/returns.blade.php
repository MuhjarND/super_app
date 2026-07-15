<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>body{font-family:Arial,sans-serif;font-size:10pt;}h1{font-size:14pt;text-align:center;}
table{width:100%;border-collapse:collapse;margin-top:12px;}
th{background:#0284c7;color:white;padding:5px 6px;font-size:9pt;}
td{padding:4px 6px;border-bottom:1px solid #e2e8f0;font-size:9.5pt;}
tr:nth-child(even) td{background:#f8fafc;}.header{text-align:center;margin-bottom:12px;}
</style></head><body>
<div class="header"><h1>Laporan Pengembalian</h1><div>{{ now()->format('d M Y H:i') }}</div></div>
<table>
<thead><tr><th>#</th><th>No. Pinjam</th><th>Anggota</th><th>Tgl. Kembali</th><th>Jml. Buku</th><th>Terlambat</th><th>Denda</th></tr></thead>
<tbody>
@foreach($returns as $i => $ret)
@php
$days = max(0, \Carbon\Carbon::parse($ret->loan->due_date)->diffInDays(\Carbon\Carbon::parse($ret->return_date), false) * -1);
$fine = $ret->loan->loanItems->sum(fn($it) => optional($it->fine)->total_amount ?? 0);
@endphp
<tr>
<td>{{ $i+1 }}</td>
<td>{{ $ret->loan->loan_number }}</td>
<td>{{ $ret->loan->member->name }}</td>
<td>{{ $ret->return_date->format('d/m/Y') }}</td>
<td>{{ $ret->loan->loanItems->count() }}</td>
<td>{{ $days > 0 ? $days . ' hari' : 'Tepat waktu' }}</td>
<td>{{ $fine > 0 ? 'Rp' . number_format($fine, 0, ',', '.') : '—' }}</td>
</tr>
@endforeach
</tbody>
</table>
</body></html>
