<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        h1 { font-size: 16pt; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background: #4f46e5; color: white; padding: 6px 8px; font-size: 10pt; }
        td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10pt; }
        tr:nth-child(even) td { background: #f8fafc; }
        .header { text-align: center; margin-bottom: 16px; }
        .date { font-size: 10pt; color: #64748b; text-align: center; }
    </style>
</head>
<body>
<div class="header">
    <h1>Laporan Data Buku Perpustakaan</h1>
    <div class="date">Dicetak pada: {{ now()->format('d M Y H:i') }}</div>
</div>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Judul Buku</th>
            <th>Penulis</th>
            <th>Penerbit</th>
            <th>Kategori</th>
            <th>ISBN</th>
            <th>Eksemplar</th>
        </tr>
    </thead>
    <tbody>
        @foreach($books as $i => $book)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $book->title }}</td>
            <td>{{ $book->author }}</td>
            <td>{{ $book->publisher ?? '—' }}</td>
            <td>{{ $book->category->name }}</td>
            <td>{{ $book->isbn ?? '—' }}</td>
            <td>{{ $book->copies_count }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
