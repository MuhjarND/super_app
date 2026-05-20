@extends('layouts.app')

@section('title', 'Agenda Pimpinan')

@push('styles')
    <style>
        .agenda-card { border-radius: 14px; border: 1px solid #e8eaed; }
        .agenda-table thead th { font-size: 0.72rem; text-transform: uppercase; color: #64748b; border-top: none; }
        .agenda-table tbody td { vertical-align: top; font-size: 0.85rem; }
        .agenda-preview { white-space: pre-line; font-size: 0.8rem; color: #334155; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; }
        .agenda-action-cell { min-width: 260px; }
        .meeting-action-panel { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .agenda-action-cell .meeting-action-panel { gap: 8px; }
        .agenda-action-cell form { margin: 0; }
        .meeting-action-btn { display: inline-flex; align-items: center; gap: 8px; border-radius: 10px; padding: 7px 12px; font-size: 0.82rem; font-weight: 700; border: 1px solid transparent; background: #fff; color: #1f2937; }
        .agenda-action-cell .meeting-action-btn { padding: 6px 10px; white-space: nowrap; }
        .meeting-action-btn.secondary { background: #f8fafc; color: #475569; border-color: #cbd5e1; }
        .meeting-action-btn.success { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .meeting-action-btn.primary { background: #eef2ff; color: #4338ca; border-color: #c7d2fe; }
        .meeting-action-btn.danger { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
        .agenda-source-badge { display: inline-flex; align-items: center; gap: 5px; border-radius: 999px; padding: 3px 8px; background: #ecfdf5; color: #047857; font-size: 0.68rem; font-weight: 800; margin-top: 6px; }
        .agenda-source-link { color: #2563eb; font-size: 0.76rem; font-weight: 700; }
    </style>
@endpush

@section('content-header')
    @php($canManageAgendaDetails = auth()->user()->isSuperAdmin() || auth()->user()->isMeetingAdmin())
    <div class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-1">Agenda Pimpinan</h1>
                <div class="text-muted" style="font-size: 0.82rem;">Agenda dibuat dari Surat Masuk. Protokoler mengatur peserta kegiatan dan mengirim notifikasi formal.</div>
            </div>
            <a href="{{ route('surat-masuk.index') }}" class="btn app-create-btn">
                <i class="fas fa-inbox mr-1"></i> Input dari Surat Masuk
            </a>
        </div>
    </div>
@endsection

@section('content')
    @php($canManageAgendaDetails = auth()->user()->isSuperAdmin() || auth()->user()->isMeetingAdmin())
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card agenda-card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0 agenda-table">
                <thead>
                    <tr>
                        <th>Agenda</th>
                        <th>Waktu WIT</th>
                        <th>Peserta Kegiatan</th>
                        <th>Penerima</th>
                        <th>Lampiran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agendas as $agenda)
                        <tr
                            data-agenda-id="{{ $agenda->id }}"
                            data-action="{{ route('rapat.agenda.update', $agenda) }}"
                            data-participants-action="{{ route('rapat.agenda.participants', $agenda) }}"
                            data-tanggal-kegiatan="{{ optional($agenda->tanggal_kegiatan)->format('Y-m-d') }}"
                            data-judul-agenda="{{ $agenda->judul_agenda }}"
                            data-tempat="{{ $agenda->tempat }}"
                            data-waktu="{{ $agenda->waktu_formatted }}"
                            data-seragam-pakaian="{{ $agenda->seragam_pakaian }}"
                            data-nomor-naskah-dinas="{{ $agenda->nomor_naskah_dinas }}"
                            data-lampiran-link="{{ $agenda->lampiran_link }}"
                            data-catatan="{{ $agenda->catatan }}"
                            data-recipient-ids="{{ $agenda->recipients->pluck('id')->implode(',') }}"
                            data-whatsapp-preview="{{ $agenda->whatsapp_preview }}"
                        >
                            <td>
                                <div class="font-weight-bold">{{ $agenda->judul_agenda }}</div>
                                <div class="text-muted" style="font-size: 0.78rem;">{{ $agenda->nomor_naskah_dinas ?: 'Tanpa nomor naskah dinas' }}</div>
                                @if($agenda->seragam_pakaian)
                                    <div class="text-muted" style="font-size: 0.78rem;">Pakaian: {{ $agenda->seragam_pakaian }}</div>
                                @endif
                                @if($agenda->suratMasuk)
                                    <div class="agenda-source-badge">
                                        <i class="fas fa-inbox"></i> Dari Surat Masuk
                                    </div>
                                    <div>
                                        <a href="{{ route('surat-masuk.show', $agenda->suratMasuk) }}" class="agenda-source-link">
                                            {{ $agenda->suratMasuk->nomor_surat }}
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $agenda->tanggal_formatted }}</div>
                                <div class="text-muted" style="font-size: 0.78rem;">{{ $agenda->waktu_formatted }} WIT</div>
                                <div class="text-muted" style="font-size: 0.78rem;">{{ $agenda->tempat }}</div>
                            </td>
                            <td>{{ $agenda->yang_menghadiri ?: '-' }}</td>
                            <td>
                                <div>{{ $agenda->recipients->count() }} penerima</div>
                                <div class="text-muted" style="font-size: 0.78rem;">{{ $agenda->recipients->take(2)->pluck('name')->implode(', ') }}{{ $agenda->recipients->count() > 2 ? '...' : '' }}</div>
                            </td>
                            <td>
                                @if($agenda->lampiran_link)
                                    <a href="{{ $agenda->lampiran_link }}" target="_blank">Buka Link</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="agenda-action-cell">
                                <div class="meeting-action-panel">
                                    <button type="button" class="meeting-action-btn secondary" onclick="previewWhatsapp({{ $agenda->id }})">
                                        <i class="fas fa-comment-dots"></i> Preview WA
                                    </button>
                                    <form action="{{ route('rapat.agenda.send-whatsapp', $agenda) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="meeting-action-btn success" onclick="return confirm('Kirim notifikasi WhatsApp agenda ini sekarang?')">
                                            <i class="fas fa-paper-plane"></i> Kirim WA
                                        </button>
                                    </form>
                                    <button type="button" class="meeting-action-btn primary" onclick="openParticipantsAgenda({{ $agenda->id }})">
                                        <i class="fas fa-user-plus"></i> Atur Peserta
                                    </button>
                                    @if($canManageAgendaDetails)
                                        <button type="button" class="meeting-action-btn secondary" onclick="openEditAgenda({{ $agenda->id }})">
                                            <i class="fas fa-pen"></i> Edit Detail
                                        </button>
                                        <form action="{{ route('rapat.agenda.destroy', $agenda) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="meeting-action-btn danger" onclick="return confirm('Hapus agenda pimpinan ini?')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada agenda pimpinan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('rapat.agenda.partials.form-modal', [
        'modalId' => 'editAgendaModal',
        'formId' => 'editAgendaForm',
        'title' => 'Edit Agenda Pimpinan',
        'action' => '#',
        'method' => 'PUT',
        'submitLabel' => 'Perbarui Agenda',
        'users' => $users,
    ])

    <div class="modal fade" id="participantsAgendaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Atur Peserta Agenda Pimpinan</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <form id="participantsAgendaForm" action="#" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-body">
                        <div class="alert alert-info" style="border-radius: 12px;">
                            Pilih pegawai yang mengikuti kegiatan. Urutan peserta akan mengikuti hirarki user pada sistem.
                        </div>
                        <div class="form-group">
                            <label>Seragam / Pakaian</label>
                            <input type="text" name="seragam_pakaian" id="participantsSeragamPakaian" class="form-control" placeholder="Contoh: PSL, Batik Korpri, atau menyesuaikan">
                        </div>
                        <div class="form-group mb-0">
                            <label>Peserta Kegiatan</label>
                            <select name="recipient_ids[]" id="participantsRecipientIds" class="form-control select2" multiple>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">
                                        {{ $user->name }}{{ $user->jabatan ? ' - ' . $user->jabatan->nama : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Peserta yang dipilih otomatis menjadi yang menghadiri sekaligus penerima notifikasi WhatsApp.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Simpan Peserta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="whatsappPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview Notifikasi WhatsApp</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="whatsappPreviewContent" class="agenda-preview"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            $('#editAgendaModal, #participantsAgendaModal').on('shown.bs.modal', function () {
                $(this).find('.select2').each(function () {
                    const $select = $(this);
                    if ($select.hasClass('select2-hidden-accessible')) {
                        $select.select2('destroy');
                    }
                    $select.select2({
                        theme: 'bootstrap4',
                        width: '100%',
                        dropdownParent: $select.closest('.modal')
                    });
                });
            });

        });

        function previewWhatsapp(agendaId) {
            const row = $('tr[data-agenda-id="' + agendaId + '"]');
            $('#whatsappPreviewContent').text(row.data('whatsappPreview'));
            $('#whatsappPreviewModal').modal('show');
        }

        function openParticipantsAgenda(agendaId) {
            const row = $('tr[data-agenda-id="' + agendaId + '"]');
            const recipientIds = String(row.data('recipientIds') || '').split(',').filter(Boolean);

            $('#participantsAgendaForm').attr('action', row.data('participantsAction'));
            $('#participantsSeragamPakaian').val(row.data('seragamPakaian'));
            $('#participantsRecipientIds').val(recipientIds).trigger('change');

            $('#participantsAgendaModal').modal('show');
        }

        function openEditAgenda(agendaId) {
            const row = $('tr[data-agenda-id="' + agendaId + '"]');
            const recipientIds = String(row.data('recipientIds') || '').split(',').filter(Boolean);

            $('#editAgendaForm').attr('action', row.data('action'));
            $('#editTanggalKegiatan').val(row.data('tanggalKegiatan'));
            $('#editJudulAgenda').val(row.data('judulAgenda'));
            $('#editTempat').val(row.data('tempat'));
            $('#editWaktu').val(row.data('waktu'));
            $('#editNomorNaskahDinas').val(row.data('nomorNaskahDinas'));
            $('#editLampiranLink').val(row.data('lampiranLink'));
            $('#editCatatan').val(row.data('catatan'));
            $('#editRecipientIds').val(recipientIds).trigger('change');

            $('#editAgendaModal').modal('show');
        }
    </script>
@endpush
