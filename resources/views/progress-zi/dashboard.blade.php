@extends('progress-zi.layout')
@section('title', 'Rekapan ZI')
@section('zi-content')
@php
    $summary = $dashboard['summary'];
    $score = rtrim(rtrim(number_format($summary['period_score'], 1), '0'), '.');
    $reviews = $dashboard['review_summary'];
    $selectedGroup = array_key_exists(request('group_type'), \App\ZiArea::groupOptions()) ? request('group_type') : 'pengungkit';
    $groupAreas = $dashboard['group_area_progress']->get($selectedGroup, collect());
@endphp
<div class="zi-page-head">
    <div>
        <h1>Zona Integritas</h1>
        <p>Pantau pemenuhan eviden dan tindak lanjut setiap area.</p>
    </div>
    <div class="zi-head-actions">
        <form method="GET">
            <input type="hidden" name="group_type" value="{{ $selectedGroup }}">
            <label for="ziDashboardPeriod" class="sr-only">Periode evaluasi</label>
            <select id="ziDashboardPeriod" name="period_id" class="form-control">
                <option value="">Semua Periode</option>
                @foreach($periods as $period)
                    <option value="{{ $period->id }}" {{ optional($selectedPeriod)->id === $period->id ? 'selected' : '' }}>{{ $period->name }} ({{ $period->year }})</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary">Terapkan</button>
        </form>
        <a href="{{ route('progress-zi.activities.index', ['period_id' => optional($selectedPeriod)->id, 'group_type' => $selectedGroup]) }}" class="btn btn-primary"><i class="fas fa-chart-bar mr-2" aria-hidden="true"></i>Monitoring Kegiatan</a>
    </div>
</div>
<div class="zi-panel zi-summary-band">
    <div class="zi-summary-cell">
        <span class="text-muted">Progres eviden disetujui</span>
        <strong class="zi-score">{{ $score }}%</strong>
        <div class="zi-progress-track" role="progressbar" aria-label="Progres eviden disetujui" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $summary['period_score'] }}"><span style="width:{{ min(100, max(0, $summary['period_score'])) }}%"></span></div>
        <small>{{ $summary['sub_point_covered_count'] }} dari {{ $summary['sub_point_count'] }} poin memiliki eviden</small>
    </div>
    <div class="zi-summary-cell zi-tone-1"><i class="fas fa-layer-group" aria-hidden="true"></i><strong>{{ $summary['area_count'] }} Area</strong></div>
    <div class="zi-summary-cell zi-tone-2"><i class="far fa-file-alt" aria-hidden="true"></i><strong>{{ $summary['sub_point_count'] }} Poin Eviden</strong></div>
    <div class="zi-summary-cell zi-tone-3"><i class="fas fa-users" aria-hidden="true"></i><strong>{{ $summary['group_count'] }} Kelompok</strong></div>
</div>
@include('progress-zi.partials.group-tabs', ['tabsRoute' => 'progress-zi.dashboard', 'tabsPeriod' => $selectedPeriod, 'tabsSelected' => $selectedGroup, 'tabsAreaCount' => $groupAreas->count()])
<div class="zi-dashboard-grid">
    <section class="zi-panel" aria-labelledby="ziAreaProgressTitle">
        <h2 class="zi-panel-heading" id="ziAreaProgressTitle">Progres per area</h2>
        <table class="zi-dashboard-area-table">
            <colgroup><col style="width:28%"><col style="width:9%"><col style="width:27%"><col style="width:30%"><col style="width:6%"></colgroup>
            <thead><tr><th scope="col">Area</th><th scope="col">Poin eviden</th><th scope="col">Penanggung jawab</th><th scope="col">Progres</th><th scope="col">Aksi</th></tr></thead>
            <tbody>
            @forelse($groupAreas as $item)
                <tr class="zi-tone-{{ $loop->index % 6 }}">
                    <td><div class="zi-area-name"><span class="zi-area-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><div><a href="{{ route('progress-zi.activities.index', ['group_type' => $selectedGroup, 'area_id' => $item['id'], 'period_id' => optional($selectedPeriod)->id]) }}">{{ $item['name'] }}</a>@if($item['review_summary']['point_count'])<span class="zi-review-note"><i class="far fa-clock" aria-hidden="true"></i> {{ $item['review_summary']['point_count'] }} poin belum direview</span>@endif</div></div></td>
                    <td data-label="Poin memiliki eviden">{{ $item['coverage']['covered'] }}/{{ $item['coverage']['total'] }}</td>
                    <td data-label="Penanggung jawab">@include('progress-zi.partials.member-list', ['members' => $item['pic_members'] ?? [], 'variant' => 'compact', 'memberListId' => 'ziDashboardMembers' . $item['id'], 'areaTitle' => $item['name']])</td>
                    <td data-label="Progres">
                        @include('progress-zi.partials.area-progress', ['areaProgress' => $item['evidence_progress'], 'areaName' => $item['name']])
                    </td>
                    <td><a class="btn btn-primary btn-sm" href="{{ route('progress-zi.activities.index', ['group_type' => $selectedGroup, 'area_id' => $item['id'], 'period_id' => optional($selectedPeriod)->id]) }}" aria-label="Buka monitoring {{ $item['name'] }}"><i class="fas fa-chevron-right" aria-hidden="true"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-muted p-4">Belum ada data area pada kelompok ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </section>
    <aside class="zi-panel" id="ziAwaitingReviewList" tabindex="-1">
        <h2 class="zi-panel-heading">Perlu ditindaklanjuti</h2>
        <div class="zi-followup">
            @forelse($reviews['items']->take(4) as $reviewItem)
                @include('progress-zi.partials.review-item', ['reviewItem' => $reviewItem, 'reviewPeriod' => $selectedPeriod])
            @empty
                <div class="zi-empty-state"><i class="far fa-file-alt" aria-hidden="true"></i><strong>Belum ada eviden yang perlu ditindaklanjuti</strong><p>Eviden yang membutuhkan perhatian akan muncul di sini.</p></div>
            @endforelse
            @if($reviews['items']->count() > 4)
                <details class="zi-more-reviews"><summary>Lihat {{ $reviews['items']->count() - 4 }} poin lainnya</summary>
                    @foreach($reviews['items']->slice(4) as $reviewItem)
                        @include('progress-zi.partials.review-item', ['reviewItem' => $reviewItem, 'reviewPeriod' => $selectedPeriod])
                    @endforeach
                </details>
            @endif
            <a class="btn zi-btn-teal my-3" href="{{ route('progress-zi.activities.index', ['period_id' => optional($selectedPeriod)->id]) }}">Buka Monitoring</a>
            <div class="zi-flow">
                <h3>Alur eviden</h3>
                <ol>
                    <li class="zi-tone-0"><span class="zi-area-number">1</span>Siapkan sesuai panduan</li>
                    <li class="zi-tone-1"><span class="zi-area-number">2</span>Tautkan Google Drive</li>
                    <li class="zi-tone-3"><span class="zi-area-number">3</span>Ajukan review eviden</li>
                </ol>
            </div>
        </div>
    </aside>
</div>
<details class="zi-panel zi-extra-details">
    <summary>Ringkasan kegiatan dan tindak lanjut</summary>
    <div class="zi-extra-metrics">
        <div><strong>{{ $summary['activity_count'] }}</strong>Kegiatan</div>
        <div><strong>{{ $summary['evidence_count'] }}</strong>Total eviden</div>
        <div><strong>{{ $summary['overdue_count'] }}</strong>Melewati target</div>
        @foreach(['belum_mulai' => 'Belum mulai', 'berjalan' => 'Sedang berjalan', 'selesai' => 'Selesai', 'perlu_perbaikan' => 'Perlu perbaikan'] as $key => $label)
            <div><strong>{{ $dashboard['status_chart'][$key] }}</strong>{{ $label }}</div>
        @endforeach
    </div>
    @foreach($dashboard['overdue_activities'] as $activity)
        <div class="zi-followup-item"><a href="{{ route('progress-zi.activities.show', $activity) }}">{{ $activity->name }}</a><div class="small text-muted">Target {{ optional($activity->target_end_date)->format('d/m/Y') }}</div></div>
    @endforeach
    @if(auth()->user()->isSuperAdmin() || auth()->user()->canManageProgressZiMasterData())
        <h3 class="mt-4">Tren periode</h3>
        @foreach($dashboard['period_trends'] as $trend)
            <div class="zi-followup-item d-flex justify-content-between"><span>{{ $trend['name'] }}</span><strong>{{ rtrim(rtrim(number_format($trend['score'], 1), '0'), '.') }}%</strong></div>
        @endforeach
    @endif
</details>
@endsection
