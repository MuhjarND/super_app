@extends('layouts.app')

@section('title', 'Virtual Meeting')

@push('styles')
    <style>
        .virtual-meeting-shell { display: grid; gap: 14px; }
        .virtual-meeting-card { border: 1px solid #e2e8f0; border-radius: 16px; background: #fff; padding: 18px; box-shadow: 0 10px 28px rgba(15, 23, 42, .05); }
        .virtual-meeting-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; }
        .virtual-meeting-title { color: #0f172a; font-weight: 800; font-size: 1rem; margin-bottom: 5px; }
        .virtual-meeting-meta { display: flex; flex-wrap: wrap; gap: 8px 16px; color: #64748b; font-size: .8rem; }
        .virtual-meeting-meta span { display: inline-flex; align-items: center; gap: 6px; }
        .virtual-meeting-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 7px; }
        .virtual-meeting-participants { margin-top: 14px; padding-top: 12px; border-top: 1px solid #eef2f7; color: #475569; font-size: .8rem; }
        .virtual-meeting-source { display: inline-flex; align-items: center; gap: 5px; margin-top: 9px; color: #2563eb; font-size: .75rem; font-weight: 700; }
        @media (max-width: 767.98px) {
            .virtual-meeting-card { padding: 14px; }
            .virtual-meeting-head { display: block; }
            .virtual-meeting-actions { justify-content: flex-start; margin-top: 14px; }
            .virtual-meeting-actions .btn { flex: 1 1 auto; }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-1">Virtual Meeting</h1>
                <div class="text-muted" style="font-size:.82rem;">Agenda daring dari surat masuk dan undangan untuk peserta terkait.</div>
            </div>
            @if($canManage)
                <a href="{{ route('surat-masuk.index') }}" class="btn app-create-btn">
                    <i class="fas fa-plus mr-1"></i> Input dari Surat Masuk
                </a>
            @endif
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

    <div class="virtual-meeting-shell">
        @forelse($meetings as $meeting)
            <article class="virtual-meeting-card">
                <div class="virtual-meeting-head">
                    <div>
                        <div class="virtual-meeting-title">{{ $meeting->judul }}</div>
                        <div class="virtual-meeting-meta">
                            <span><i class="far fa-calendar-alt"></i>{{ $meeting->tanggal_formatted }}</span>
                            <span><i class="far fa-clock"></i>{{ $meeting->waktu_mulai_formatted }}{{ $meeting->waktu_selesai ? ' - ' . $meeting->waktu_selesai_formatted : '' }} WIT</span>
                            <span><i class="fas fa-users"></i>{{ $meeting->participants->count() }} peserta</span>
                        </div>
                        @if($meeting->suratMasuk)
                            <a class="virtual-meeting-source" href="{{ route('surat-masuk.show', $meeting->suratMasuk) }}">
                                <i class="fas fa-inbox"></i> Surat {{ $meeting->suratMasuk->nomor_surat }}
                            </a>
                        @endif
                    </div>
                    <div class="virtual-meeting-actions">
                        <a href="{{ $meeting->zoom_link }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm">
                            <i class="fas fa-video mr-1"></i> Buka Zoom
                        </a>
                        @if($canManage)
                            <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#editVirtualMeeting{{ $meeting->id }}">
                                <i class="fas fa-pen mr-1"></i> Edit
                            </button>
                            <form action="{{ route('rapat.virtual-meeting.send-whatsapp', $meeting) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-success btn-sm" onclick="return confirm('Kirim ulang undangan WhatsApp kepada peserta?')">
                                    <i class="fab fa-whatsapp mr-1"></i> Kirim WA
                                </button>
                            </form>
                            <form action="{{ route('rapat.virtual-meeting.destroy', $meeting) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Hapus agenda virtual ini?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                <div class="virtual-meeting-participants">
                    <strong>Peserta:</strong> {{ $meeting->participants->pluck('name')->implode(', ') ?: '-' }}
                    @if($meeting->catatan)
                        <div class="mt-1"><strong>Catatan:</strong> {{ $meeting->catatan }}</div>
                    @endif
                </div>
            </article>

            @if($canManage)
                <div class="modal fade" id="editVirtualMeeting{{ $meeting->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <form action="{{ route('rapat.virtual-meeting.update', $meeting) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-video mr-2"></i>Edit Virtual Meeting</h5>
                                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label>Judul</label>
                                        <input type="text" name="judul" class="form-control" value="{{ $meeting->judul }}" required>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-md-4">
                                            <label>Tanggal</label>
                                            <input type="date" name="tanggal_kegiatan" class="form-control" value="{{ optional($meeting->tanggal_kegiatan)->format('Y-m-d') }}" required>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Mulai (WIT)</label>
                                            <input type="time" name="waktu_mulai" class="form-control" value="{{ $meeting->waktu_mulai_formatted }}" required>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label>Selesai (WIT)</label>
                                            <input type="time" name="waktu_selesai" class="form-control" value="{{ $meeting->waktu_selesai ? $meeting->waktu_selesai_formatted : '' }}">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Link Zoom</label>
                                        <input type="url" name="zoom_link" class="form-control" value="{{ $meeting->zoom_link }}" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Peserta</label>
                                        <select name="participant_ids[]" class="form-control select2" multiple>
                                            @foreach($users as $meetingUser)
                                                <option value="{{ $meetingUser->id }}" {{ $meeting->participants->contains('id', $meetingUser->id) ? 'selected' : '' }}>
                                                    {{ $meetingUser->name }}{{ $meetingUser->jabatan ? ' - ' . $meetingUser->jabatan->nama : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label>Catatan</label>
                                        <textarea name="catatan" class="form-control" rows="3">{{ $meeting->catatan }}</textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @empty
            <div class="card"><div class="card-body text-center text-muted py-5"><i class="fas fa-video fa-2x mb-3 d-block"></i>Belum ada agenda virtual.</div></div>
        @endforelse
    </div>
@endsection

@push('scripts')
    <script>
        $('.modal').on('shown.bs.modal', function () {
            const $modal = $(this);
            $modal.find('.select2').each(function () {
                const $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                $select.select2({ theme: 'bootstrap4', width: '100%', dropdownParent: $modal });
            });
        });
    </script>
@endpush
