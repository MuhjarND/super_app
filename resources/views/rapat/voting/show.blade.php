@extends('layouts.app')
@section('title', 'Monitoring Voting')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/voting-live.css') }}">
<style>
    .voting-stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; margin-bottom:18px; }
    .voting-stat { padding:16px; border:1px solid #e5e7eb; border-radius:14px; background:#fff; }
    .voting-stat span { display:block; color:#64748b; font-size:.85rem; }
    .voting-stat strong { font-size:1.6rem; }
    .voting-sidebar-card { padding:18px; background:#fff; border:1px solid #e5e7eb; border-radius:16px; margin-bottom:16px; }
    .voting-sidebar-card h2 { font-size:1rem; font-weight:800; }
    .voting-sidebar-card svg { max-width:100%; height:auto; }
    .voting-pending { max-height:320px; overflow:auto; }
    @media(max-width:480px) { .voting-stats { gap:8px; } .voting-stat { padding:10px; } .voting-stat strong { font-size:1.3rem; } }
</style>
@endpush
@section('content-header')
<div class="content-header"><div class="container-fluid d-flex justify-content-between flex-wrap align-items-start" style="gap:12px;">
    <div><h1 class="mb-1">{{ $voting->judul }}</h1><p class="text-muted mb-0">{{ $voting->deskripsi }}</p></div>
    <div class="app-action-group">
        <a href="{{ route('rapat.voting.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        @if($canManage)
            <a href="{{ route('rapat.voting.edit', $voting) }}" class="btn btn-outline-primary btn-sm" data-live-ballot-editor {{ $voting->votes_count ? 'hidden' : '' }}>Edit voting</a>
            <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#sendVotingModal">Kirim WhatsApp</button>
        @endif
        <a href="{{ route('rapat.voting.pdf', $voting) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">Hasil PDF</a>
    </div>
</div></div>
@endsection
@section('content')
@foreach(['success'=>'success','error'=>'danger'] as $key=>$tone)
    @if(session($key))<div class="alert alert-{{ $tone }}" role="status">{{ session($key) }}</div>@endif
@endforeach
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<div class="voting-live" data-voting-live data-stats-url="{{ route('rapat.voting.stats', $voting) }}">
    <div class="voting-stats">
        <div class="voting-stat"><span>Peserta terdaftar</span><strong data-live-count="total_participants">{{ $stats['total_participants'] }}</strong></div>
        <div class="voting-stat"><span>Sudah voting</span><strong data-live-count="total_voted">{{ $stats['total_voted'] }}</strong></div>
        <div class="voting-stat"><span>Belum voting</span><strong data-live-count="pending_count">{{ $stats['pending_count'] }}</strong></div>
    </div>
    <div class="row"><div class="col-lg-8">
        @include('rapat.voting.partials.live-results')
    </div><aside class="col-lg-4">
        @if($canManage)
        <section class="voting-sidebar-card">
            <h2>Pengaturan status</h2>
            <form action="{{ route('rapat.voting.status', $voting) }}" method="POST" novalidate>@csrf @method('PATCH')
                <label for="changeVotingStatus">Status voting</label>
                <select id="changeVotingStatus" name="status" class="form-control mb-2">
                    @foreach(['draft'=>'Draft','aktif'=>'Aktif','selesai'=>'Selesai'] as $value=>$label)
                        <option value="{{ $value }}" {{ $voting->status === $value ? 'selected' : '' }} {{ $value === 'draft' && $stats['total_voted'] > 0 ? 'disabled' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary btn-sm" type="submit">Simpan status</button>
            </form>
            <p class="small text-muted mt-2 mb-0" data-live-lock-notice {{ $stats['total_voted'] > 0 ? '' : 'hidden' }}>Pilihan dan peserta dikunci karena suara sudah masuk. Hasil tetap tersimpan saat voting ditutup.</p>
        </section>
        @endif
        <section class="voting-sidebar-card text-center">
            <h2>Akses peserta</h2>
            <div class="mb-2">{!! app('qrcode')->size(200)->generate(route('rapat.voting.public.show', $voting->public_code)) !!}</div>
            <a href="{{ route('rapat.voting.public.show', $voting->public_code) }}" target="_blank" rel="noopener">Buka halaman voting</a>
            <p class="small text-muted mt-2">Bagikan kode QR ini kepada peserta terdaftar. Halaman pengisian tersedia saat status Aktif.</p>
            <a href="{{ route('rapat.voting.public.results', $voting->public_code) }}" target="_blank" rel="noopener">Buka layar hasil langsung</a>
        </section>
        <section class="voting-sidebar-card"><h2>Peserta belum voting</h2><div class="voting-pending" data-live-pending>
            @forelse($stats['pending'] as $person)<div class="voting-pending-name">{{ $person['name'] }}</div>@empty<p>{{ $stats['total_participants'] ? 'Semua peserta sudah voting.' : 'Belum ada peserta.' }}</p>@endforelse
        </div></section>
    </aside></div>
</div>
@if($canManage)
<div class="modal fade" id="sendVotingModal" tabindex="-1" role="dialog" aria-labelledby="sendVotingTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h2 class="modal-title h5" id="sendVotingTitle">Kirim tautan voting</h2><button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button></div>
    <div class="modal-body">Kirim informasi voting “{{ $voting->judul }}” kepada peserta yang terdaftar melalui WhatsApp?</div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Batal</button><form action="{{ route('rapat.voting.send-whatsapp', $voting) }}" method="POST" novalidate>@csrf<button type="submit" class="btn btn-primary">Kirim WhatsApp</button></form></div>
</div></div></div>
@endif
@endsection
@push('scripts')<script src="{{ asset('js/voting-live.js') }}" defer></script>@endpush
