<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $voting->judul }} | Hasil Voting</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body{margin:0;font-family:'Inter',sans-serif;background:#f8fafc;color:#0f172a;}
        .container{max-width:1080px;margin:0 auto;padding:20px 16px 40px;}
        .hero{background:linear-gradient(135deg,#111827,#4338ca);color:#fff;border-radius:24px;padding:22px;}
        .hero h1{margin:10px 0 6px;font-size:1.6rem;}
        .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-top:16px;}
        .stat{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.16);border-radius:16px;padding:12px 14px;}
        .stat .label{font-size:.74rem;color:rgba(255,255,255,.76);}
        .stat .value{font-size:1.2rem;font-weight:800;margin-top:4px;}
        .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:14px;margin-top:18px;}
        .card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;padding:16px;box-shadow:0 8px 24px rgba(15,23,42,.05);}
        .bar{width:100%;background:#e2e8f0;height:16px;border-radius:999px;overflow:hidden;}
        .fill{height:100%;background:linear-gradient(90deg,#4f46e5,#38bdf8);transition:width .45s ease;}
        .fill.leading{background:linear-gradient(90deg,#f97316,#ef4444);}
        .pending{margin-top:18px;}
    </style>
</head>
<body>
    <div class="container">
        <div class="hero">
            <div style="font-size:.72rem;font-weight:800;">HASIL VOTING PUBLIK</div>
            <h1>{{ $voting->judul }}</h1>
            <div>{{ $voting->deskripsi }}</div>
            <div class="stats">
                <div class="stat"><div class="label">Peserta</div><div class="value" id="totalParticipants">{{ $voting->participantPivots->count() }}</div></div>
                <div class="stat"><div class="label">Sudah Voting</div><div class="value" id="totalVoted">{{ $voting->participantPivots->whereNotNull('voted_at')->count() }}</div></div>
                <div class="stat"><div class="label">Belum Voting</div><div class="value" id="pendingCount">{{ $voting->participantPivots->whereNull('voted_at')->count() }}</div></div>
            </div>
        </div>

        <div class="grid" id="resultsGrid"></div>
        <div class="card pending">
            <strong>Peserta Belum Voting</strong>
            <div id="pendingParticipants" class="mt-2"></div>
        </div>
    </div>

    <script>
        function renderStats(data) {
            document.getElementById('totalParticipants').textContent = data.total_participants;
            document.getElementById('totalVoted').textContent = data.total_voted;
            document.getElementById('pendingCount').textContent = data.pending_count;

            document.getElementById('pendingParticipants').innerHTML = data.pending.length
                ? data.pending.map(p => '<div class="mb-1">' + p.name + '</div>').join('')
                : '<div style="color:#166534;font-weight:700;">Semua peserta sudah voting.</div>';

            const grid = document.getElementById('resultsGrid');
            grid.innerHTML = data.items.map(function (item) {
                const topCount = item.candidates.length ? item.candidates[0].count : 0;
                const bars = item.candidates.map(function (candidate) {
                    const leading = candidate.count === topCount && candidate.count > 0 ? 'leading' : '';
                    return '<div style="margin-bottom:12px;">'
                        + '<div style="display:flex;justify-content:space-between;font-size:.88rem;font-weight:700;"><span>' + candidate.nama + '</span><span>' + candidate.count + ' (' + candidate.percentage + '%)</span></div>'
                        + '<div class="bar"><div class="fill ' + leading + '" style="width:' + candidate.percentage + '%"></div></div>'
                        + '</div>';
                }).join('');
                return '<div class="card"><div style="font-size:1rem;font-weight:800;margin-bottom:6px;">' + item.judul + '</div><div style="font-size:.8rem;color:#64748b;margin-bottom:12px;">Total suara: ' + item.total_votes + '</div>' + bars + '</div>';
            }).join('');
        }

        function fetchStats() {
            fetch('{{ route('rapat.voting.public.stats', $voting->public_code) }}')
                .then(response => response.json())
                .then(renderStats);
        }

        fetchStats();
        setInterval(fetchStats, 5000);
    </script>
</body>
</html>
