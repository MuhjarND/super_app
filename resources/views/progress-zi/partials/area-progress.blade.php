<div class="zi-area-progress-pair">
    @foreach(['approved' => ['Sudah direview', 'telah disetujui'], 'in_progress' => ['Dalam proses pengerjaan', 'memiliki eviden dan belum disetujui']] as $key => $labels)
        <div class="zi-progress-block zi-progress-block--{{ $key }}">
            <div class="zi-progress-heading">{{ $labels[0] }}</div>
            <div class="zi-progress-value-row">
                <div class="zi-progress-track" role="progressbar" aria-label="{{ $labels[0] }} {{ $areaName }}"
                     aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $areaProgress[$key . '_percent'] }}"
                     aria-valuetext="{{ $areaProgress[$key] }} dari {{ $areaProgress['total'] }} poin {{ $labels[1] }}">
                    <span style="width:{{ $areaProgress[$key . '_percent'] }}%"></span>
                </div>
                <span class="zi-progress-percent" aria-hidden="true">{{ rtrim(rtrim(number_format($areaProgress[$key . '_percent'], 1), '0'), '.') }}%</span>
            </div>
        </div>
    @endforeach
</div>
