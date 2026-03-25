@extends('layouts.app')

@section('title', 'Approval Surat Keluar')

@section('content')
@include('admin._alerts')

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h3 class="mb-1">Approval Surat Keluar</h3>
        <p class="text-muted mb-0">{{ optional($suratKeluarApproval->suratKeluar)->nomor_surat_formatted ?: '-' }}</p>
    </div>
    <div class="app-action-group">
        <a href="{{ route('surat-keluar.approval.preview', $suratKeluarApproval) }}" target="_blank" class="app-icon-btn preview"><i class="fas fa-eye"></i></a>
        <a href="{{ route('approval.index', ['category' => 'surat_keluar']) }}" class="app-icon-btn cancel"><i class="fas fa-arrow-left"></i></a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6"><strong>Template:</strong> {{ $suratKeluarApproval->template_name ?: '-' }}</div>
            <div class="col-md-6"><strong>Status:</strong> <span class="badge badge-{{ $suratKeluarApproval->status_badge_class }}">{{ $suratKeluarApproval->status_label }}</span></div>
            <div class="col-md-6 mt-2"><strong>Penanda Tangan:</strong> {{ $suratKeluarApproval->signer_name_snapshot ?: '-' }}</div>
            <div class="col-md-6 mt-2"><strong>Jabatan TTD:</strong> {{ $suratKeluarApproval->signer_title_snapshot ?: '-' }}</div>
            <div class="col-md-6 mt-2"><strong>Diajukan Oleh:</strong> {{ optional($suratKeluarApproval->requester)->name ?: optional(optional($suratKeluarApproval->suratKeluar)->creator)->name ?: '-' }}</div>
            <div class="col-md-6 mt-2"><strong>Perihal:</strong> {{ optional($suratKeluarApproval->suratKeluar)->perihal ?: '-' }}</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <strong>Preview Dokumen</strong>
        <a href="{{ route('surat-keluar.approval.preview', $suratKeluarApproval) }}" target="_blank" class="app-icon-btn file"><i class="fas fa-file-pdf"></i></a>
    </div>
    <div class="card-body p-0">
        <iframe src="{{ route('surat-keluar.approval.preview', $suratKeluarApproval) }}" style="width:100%;height:860px;border:0;"></iframe>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white"><strong>Riwayat Approval</strong></div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>Waktu</th><th>Aktor</th><th>Aksi</th><th>Catatan</th></tr></thead>
            <tbody>
                @forelse($suratKeluarApproval->histories as $history)
                    <tr>
                        <td>{{ optional($history->acted_at)->translatedFormat('d F Y H:i') }} WIT</td>
                        <td>{{ optional($history->approver)->name ?: $history->signer_name_snapshot ?: '-' }}</td>
                        <td>{{ ucfirst($history->action) }}</td>
                        <td>{{ $history->note ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">Belum ada riwayat approval.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($canAct)
    <div class="d-flex flex-wrap" style="gap:12px;">
        <form action="{{ route('surat-keluar.approval.approve', $suratKeluarApproval) }}" method="POST" class="d-inline-block">
            @csrf
            <textarea name="note" class="form-control mb-2" rows="3" placeholder="Catatan approval"></textarea>
            <button type="submit" class="btn btn-success btn-sm">Approve</button>
        </form>
        <form action="{{ route('surat-keluar.approval.reject', $suratKeluarApproval) }}" method="POST" class="d-inline-block">
            @csrf
            <textarea name="note" class="form-control mb-2" rows="3" placeholder="Catatan penolakan" required></textarea>
            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
        </form>
    </div>
@endif
@endsection
