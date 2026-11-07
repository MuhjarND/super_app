<!DOCTYPE html>
<html lang="id"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $voting->judul }} | Hasil Voting Langsung</title>
    @include('partials.app-icons')
    <link rel="stylesheet" href="{{ asset('css/voting-live.css') }}">
    <style>
        *{box-sizing:border-box}body{margin:0;font-family:Inter,system-ui,sans-serif;background:#f8fafc;color:#0f172a}.results-shell{max-width:1080px;margin:auto;padding:28px 18px}.results-heading{margin-bottom:24px}.results-heading h1{font-size:clamp(1.5rem,4vw,2rem);line-height:1.3;overflow-wrap:anywhere}.results-heading p{color:#64748b}.results-eyebrow{color:#4338ca;font-size:.8rem;font-weight:800;letter-spacing:.08em}.results-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin:20px 0}.results-stat{padding:16px;border:1px solid #e5e7eb;border-radius:14px;background:#fff}.results-stat span{display:block;font-size:.85rem;color:#64748b}.results-stat strong{font-size:1.7rem}.results-pending{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:18px}.results-pending h2{font-size:1rem}.results-pending-list{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:0 18px}@media(max-width:480px){.results-shell{padding:18px 12px}.results-stat{padding:10px}.results-stat strong{font-size:1.4rem}}
    </style>
</head><body>
<main class="results-shell voting-live" data-voting-live data-stats-url="{{ route('rapat.voting.public.stats', $voting->public_code) }}">
    <header class="results-heading"><span class="results-eyebrow">HASIL E-VOTING LANGSUNG</span><h1>{{ $voting->judul }}</h1><p>{{ $voting->deskripsi }}</p></header>
    <div class="results-stats">
        <div class="results-stat"><span>Peserta terdaftar</span><strong data-live-count="total_participants">{{ $stats['total_participants'] }}</strong></div>
        <div class="results-stat"><span>Sudah voting</span><strong data-live-count="total_voted">{{ $stats['total_voted'] }}</strong></div>
        <div class="results-stat"><span>Belum voting</span><strong data-live-count="pending_count">{{ $stats['pending_count'] }}</strong></div>
    </div>
    @include('rapat.voting.partials.live-results')
    <section class="results-pending"><h2>Peserta belum voting</h2><div class="results-pending-list" data-live-pending>
        @forelse($stats['pending'] as $person)<div class="voting-pending-name">{{ $person['name'] }}</div>@empty<p>{{ $stats['total_participants'] ? 'Semua peserta sudah voting.' : 'Belum ada peserta.' }}</p>@endforelse
    </div></section>
</main>
<script src="{{ asset('js/voting-live.js') }}" defer></script>
</body></html>
