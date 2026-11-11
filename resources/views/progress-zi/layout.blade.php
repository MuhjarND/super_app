@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/progress-zi.css') }}?v={{ filemtime(public_path('css/progress-zi.css')) }}">
@endpush

@section('content')
    <div class="zi-workspace">
        @yield('zi-content')
        @if(request()->routeIs('progress-zi.dashboard', 'progress-zi.index', 'progress-zi.activities.index'))
            @include('progress-zi.partials.member-modal')
        @endif
    </div>
@endsection
