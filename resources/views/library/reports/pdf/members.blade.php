<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
body { font-family: Arial, sans-serif; font-size: 11pt; }
h1 { font-size: 16pt; text-align: center; }
table { width: 100%; border-collapse: collapse; margin-top: 16px; }
th { background: #059669; color: white; padding: 6px 8px; font-size: 10pt; }
td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10pt; }
tr:nth-child(even) td { background: #f8fafc; }
.header { text-align: center; margin-bottom: 16px; }
.date { font-size: 10pt; color: #64748b; }
</style></head><body>
<div class="header">
<h1>Laporan Data Anggota Perpustakaan</h1>
<div class="date">Dicetak pada: {{ now()->format('d M Y H:i') }}</div>
</div>
<table>
<thead><tr><th>#</th><th>Nama</th><th>No. Anggota</th><th>Kelas</th><th>HP</th><th>Status</th><th>Total Pinjam</th></tr></thead>
<tbody>
@foreach($members as $i => $m)
<tr>
<td>{{ $i+1 }}</td>
<td>{{ $m->name }}</td>
<td>{{ $m->member_number }}</td>
<td>{{ $m->class_position ?? '—' }}</td>
<td>{{ $m->phone ?? '—' }}</td>
<td>{{ ucfirst($m->status) }}</td>
<td>{{ $m->loans_count }}</td>
</tr>
@endforeach
</tbody>
</table>
</body></html>
