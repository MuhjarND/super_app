<table border="1">
    <thead>
        <tr>
            <th colspan="4">Rekap Verifikasi Progress ZI - {{ optional($period)->name ?: 'Semua Periode' }}</th>
        </tr>
        <tr>
            <th>Jenis</th>
            <th>Nama</th>
            <th>Kegiatan</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($indicators as $indicator)
            <tr>
                <td>Indikator</td>
                <td>{{ $indicator->name }}</td>
                <td>{{ optional($indicator->activity)->name }}</td>
                <td>{{ $indicator->status_label }}</td>
            </tr>
        @endforeach
        @foreach($evidences as $evidence)
            <tr>
                <td>Eviden</td>
                <td>{{ $evidence->title }}</td>
                <td>{{ optional(optional($evidence->realization)->activity)->name }}</td>
                <td>{{ $evidence->status_label }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
