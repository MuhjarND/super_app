@extends('layouts.app')

@section('title', 'Detail Voting')

@push('styles')
    <style>
        .voting-stat-grid { display:grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap:12px; }
        .voting-stat-card { border:1px solid #e5e7eb; border-radius:14px; padding:14px; background:#fff; }
        .result-item-card { border:1px solid #e5e7eb; border-radius:16px; padding:16px; margin-bottom:16px; background:#fff; }
        .bar-row { margin-bottom: 12px; }
        .bar-track { width:100%; background:#e5e7eb; border-radius:999px; overflow:hidden; height:16px; }
        .bar-fill { height:100%; background:linear-gradient(90deg, #4338ca, #0ea5e9); transition: width .45s ease; }
        .bar-fill.leading { background:linear-gradient(90deg, #f97316, #ef4444); }
        .result-candidate-head { display:flex; justify-content:space-between; gap:12px; align-items:center; }
        .result-candidate-name { display:flex; align-items:center; gap:10px; min-width:0; }
        .result-candidate-img { width:42px; height:42px; border-radius:10px; object-fit:cover; background:#eef2ff; border:1px solid #dbe4ff; flex:0 0 auto; }
        .pending-list { max-height: 240px; overflow:auto; }
        .qr-box svg { max-width: 100%; height: auto; }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-start">
            <div>
                <h1 class="mb-1">{{ $voting->judul }}</h1>
                <div class="text-muted" style="font-size:0.82rem;">Monitoring realtime, QR publik, hasil admin, dan peserta belum voting.</div>
            </div>
            <div class="app-action-group">
                @if($canManage)
                    <a href="{{ route('rapat.voting.edit', $voting) }}" class="app-icon-btn edit"><i class="fas fa-pen"></i></a>
                    <form action="{{ route('rapat.voting.send-whatsapp', $voting) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="app-icon-btn send" onclick="return confirm('Kirim link voting ke peserta sekarang?')"><i class="fas fa-paper-plane"></i></button>
                    </form>
                @endif
                <a href="{{ route('rapat.voting.pdf', $voting) }}" target="_blank" class="app-icon-btn pdf"><i class="fas fa-file-pdf"></i></a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="voting-stat-grid mb-3">
        <div class="voting-stat-card"><div class="text-muted" style="font-size:0.75rem;">Status</div><div class="font-weight-bold">{!! $voting->status_badge !!}</div></div>
        <div class="voting-stat-card"><div class="text-muted" style="font-size:0.75rem;">Peserta</div><div class="font-weight-bold" id="totalParticipants">{{ $voting->participantPivots->count() }}</div></div>
        <div class="voting-stat-card"><div class="text-muted" style="font-size:0.75rem;">Sudah Voting</div><div class="font-weight-bold" id="totalVoted">{{ $voting->participantPivots->whereNotNull('voted_at')->count() }}</div></div>
        <div class="voting-stat-card"><div class="text-muted" style="font-size:0.75rem;">Belum Voting</div><div class="font-weight-bold text-danger" id="pendingCount">{{ $voting->participantPivots->whereNull('voted_at')->count() }}</div></div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            @foreach($voting->items as $item)
                <div class="result-item-card" data-item-id="{{ $item->id }}">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <div style="font-size:1rem; font-weight:800;">{{ $item->judul }}</div>
                            <div class="text-muted" style="font-size:0.8rem;">{{ $item->deskripsi }}</div>
                        </div>
                        <span class="badge badge-light border total-votes">{{ $item->votes->count() }} suara</span>
                    </div>
                    <div class="candidate-results"></div>
                </div>
            @endforeach
        </div>
        <div class="col-lg-4">
            <div class="card" style="border-radius:16px; border:1px solid #e5e7eb;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>QR Link Publik</strong>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#qrModal">Perbesar</button>
                </div>
                <div class="card-body text-center">
                    <div class="qr-box mb-2">{!! app('qrcode')->size(180)->generate(route('rapat.voting.public.show', $voting->public_code)) !!}</div>
                    <div><a href="{{ route('rapat.voting.public.show', $voting->public_code) }}" target="_blank">Link Voting Publik</a></div>
                    <div class="text-muted mt-2" style="font-size:0.76rem;">Satu link publik yang sama untuk semua peserta.</div>
                </div>
            </div>

            <div class="card mt-3" style="border-radius:16px; border:1px solid #e5e7eb;">
                <div class="card-header bg-white">
                    <strong>Belum Voting</strong>
                </div>
                <div class="card-body pending-list" id="pendingParticipants"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="qrModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">QR Voting Publik</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body text-center">
                    {!! app('qrcode')->size(320)->generate(route('rapat.voting.public.show', $voting->public_code)) !!}
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="allVotedModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:20px;">
                <div class="modal-body text-center py-5">
                    <div style="font-size:3rem;">Selesai</div>
                    <h3 class="mt-2">Semua peserta sudah voting</h3>
                    <div class="text-muted">Pemantauan realtime sudah lengkap untuk voting ini.</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let allVotedModalShown = false;

        function renderStats(data) {
            $('#totalParticipants').text(data.total_participants);
            $('#totalVoted').text(data.total_voted);
            $('#pendingCount').text(data.pending_count);

            const pendingHtml = data.pending.length
                ? data.pending.map(item => '<div class="mb-2">' + item.name + '</div>').join('')
                : '<div class="text-success font-weight-bold">Semua peserta sudah voting.</div>';
            $('#pendingParticipants').html(pendingHtml);

            data.items.forEach(function (item) {
                const $card = $('[data-item-id=\"' + item.id + '\"]');
                $card.find('.total-votes').text(item.total_votes + ' suara');

                const topCount = item.candidates.length ? item.candidates[0].count : 0;
                const html = item.candidates.map(function (candidate) {
                    const leading = candidate.count === topCount && candidate.count > 0 ? 'leading' : '';
                    const image = candidate.image_url ? '<img src=\"' + candidate.image_url + '\" class=\"result-candidate-img\" alt=\"\">' : '';
                    return '<div class=\"bar-row\">'
                        + '<div class=\"result-candidate-head\"><div class=\"result-candidate-name\">' + image + '<strong>' + candidate.nama + '</strong></div><span>' + candidate.count + ' (' + candidate.percentage + '%)</span></div>'
                        + '<div class=\"bar-track\"><div class=\"bar-fill ' + leading + '\" style=\"width:' + candidate.percentage + '%\"></div></div>'
                        + '</div>';
                }).join('');

                $card.find('.candidate-results').html(html || '<div class=\"text-muted\">Belum ada suara.</div>');
            });

            if (data.all_voted && !allVotedModalShown) {
                allVotedModalShown = true;
                $('#allVotedModal').modal('show');
            }
        }

        function fetchStats() {
            $.get('{{ route('rapat.voting.stats', $voting) }}', function (data) {
                renderStats(data);
            });
        }

        $(function () {
            fetchStats();
            setInterval(fetchStats, 5000);
        });
    </script>
@endpush
