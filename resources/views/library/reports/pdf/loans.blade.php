<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; font-size: 10pt; }
h1 { font-size: 14pt; text-align: center; }
table { width: 100%; border-collapse: collapse; margin-top: 12px; }
th { background: #d97706; color: white; padding: 5px 6px; font-size: 9pt; }
td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; font-size: 9.5pt; }
tr:nth-child(even) td { background: #f8fafc; }
.header { text-align: center; margin-bottom: 12px; }
</style></head><body>
<div class="header"><h1>Laporan Peminjaman</h1><div>{{ now()->format('d M Y H:i') }}</div></div>
<table>
<thead><tr><th>#</th><th>No. Pinjam</th><th>Anggota</th><th>Tgl. Pinjam</th><th>Jatuh Tempo</th><th>Jml. Buku</th><th>Status</th></tr></thead>
<tbody>
@foreach($loans as $i => $l)
<tr>
<td>{{ $i+1 }}</td>
<td>{{ $l->loan_number }}</td>
<td>{{ $l->member->name }}</td>
<td>{{ $l->loan_date->format('d/m/Y') }}</td>
<td>{{ $l->due_date->format('d/m/Y') }}</td>
<td>{{ $l->loanItems->count() }}</td>
<td>{{ ucfirst($l->status) }}</td>
</tr>
@endforeach
</tbody>
</table>
</body></html>
