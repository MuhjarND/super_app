<table border="1">
    <thead>
        <tr>
            <th colspan="7">Laporan Progress Zona Integritas - {{ $summary['period_name'] }}</th>
        </tr>
        <tr>
            <th>Area</th>
            <th>Kegiatan</th>
            <th>PIC</th>
            <th>Progress</th>
            <th>Indikator</th>
            <th>Eviden</th>
            <th>Status</th>
        </tr>
        <tr>
            <th colspan="2">Sub Poin Ditindaklanjuti</th>
            <th colspan="2">{{ $summary['sub_point_covered_count'] }}/{{ $summary['sub_point_count'] }}</th>
            <th colspan="2">Sub Poin Berkala</th>
            <th>{{ $summary['periodic_sub_point_count'] }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($activities as $activity)
            <tr>
                <td>{{ optional($activity->area)->code }} - {{ optional($activity->area)->name }}</td>
                <td>{{ $activity->name }}</td>
                <td>{{ optional($activity->pic)->name ?: '-' }}</td>
                <td>{{ rtrim(rtrim(number_format($activity->progress_score, 1), '0'), '.') }}%</td>
                <td>{{ $activity->indicators->count() }}</td>
                <td>{{ $activity->realizations->sum(function ($realization) { return $realization->evidences->count(); }) }}</td>
                <td>{{ $activity->status_label }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
