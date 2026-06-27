<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111827; }
        .title { font-size: 16px; font-weight: 700; margin-bottom: 8px; }
        .meta { font-size: 10px; color: #4b5563; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 5px 6px; vertical-align: top; }
        th { background: #f3f4f6; font-weight: 700; text-align: left; }
    </style>
</head>
<body>
@php
    $reportYear = (int) ($filters['year'] ?? now()->year);
@endphp
@include('partials.pdf-verification-badge', ['pdfVerification' => $pdfVerification ?? null])
<div class="title">Rekap Saldo Cuti</div>
<div class="meta">Tahun: {{ $filters['year'] ?? now()->year }} | Mengikuti ketentuan SE Sekma Nomor 13 Tahun 2019</div>
<table>
    <thead>
        <tr>
            <th>Pegawai</th>
            <th>Unit</th>
            <th>Jenis Cuti</th>
            <th>Tahun</th>
            <th>{{ $reportYear }}</th>
            <th>{{ $reportYear - 1 }}</th>
            <th>{{ $reportYear - 2 }}</th>
            <th>Total Hak</th>
            <th>Terpakai</th>
            <th>Tertahan</th>
            <th>Sisa</th>
            <th>Ketentuan</th>
        </tr>
    </thead>
    <tbody>
        @foreach($balances as $balance)
            <tr>
                <td>{{ optional($balance->user)->name }}<br>{{ optional($balance->user)->nip ?: '-' }}</td>
                <td>{{ optional(optional($balance->user)->unit)->nama ?: '-' }}</td>
                <td>{{ optional($balance->leaveType)->name }}</td>
                <td>{{ $balance->year }}</td>
                <td>{{ $balance->entitlement }}</td>
                <td>{{ $balance->carry_forward_previous_year }}</td>
                <td>{{ $balance->carry_forward_two_years_ago }}</td>
                <td>{{ $balance->total_balance }}</td>
                <td>{{ $balance->used_days }}</td>
                <td>{{ $balance->reserved_days }}</td>
                <td>{{ $balance->remaining_balance }}</td>
                <td>{{ $balance->rule_note }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
