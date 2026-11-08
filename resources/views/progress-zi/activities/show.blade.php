@extends('progress-zi.layout')
@section('title', 'Eviden Kegiatan ZI')
@section('zi-content')
<div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <h4>{{ $activity->name }}</h4>
        <p class="text-muted">{{ optional($activity->area)->name }}</p>
        <a href="{{ route('progress-zi.activities.index', ['period_id' => $activity->zi_period_id, 'area_id' => $activity->zi_area_id]) }}" class="btn btn-primary">Monitoring dan Pengumpulan Eviden</a>
    </div>
</div>
@if($activity->guidelineSubPoint)
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white"><strong>Panduan Penyusunan Eviden</strong></div>
        <div class="card-body">@include('progress-zi.partials.evidence-guide', ['guide' => $activity->guidelineSubPoint->evidence_guide])</div>
    </div>
@endif
@foreach($activity->realizations as $realization)
    @foreach($realization->evidences as $evidence)
        <div class="card shadow-sm border-0 mb-3"><div class="card-body">
            <strong>{{ $evidence->title }}</strong>
            <div class="small text-muted">{{ $evidence->source_reference_label }} · {{ $evidence->status_label }}</div>
            @if($evidence->description)
                <div class="mt-2"><strong>Catatan Eviden:</strong><div style="white-space:pre-wrap; overflow-wrap:anywhere;">{{ $evidence->description }}</div></div>
            @endif
            @if($evidence->preview_url)
                <a href="{{ $evidence->preview_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm mt-3">Buka Eviden</a>
            @endif
        </div></div>
    @endforeach
@endforeach
@endsection
