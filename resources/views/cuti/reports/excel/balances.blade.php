@php
    $reportYear = (int) ($filters['year'] ?? now()->year);
@endphp
<table border="1">
    <thead>
        <tr>
            <th>Pegawai</th>
            <th>NIP</th>
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
                <td>{{ optional($balance->user)->name }}</td>
                <td>{{ optional($balance->user)->nip ?: '-' }}</td>
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
