@extends('layouts.app')

@section('title', 'Agenda Pimpinan')

@push('styles')
    <style>
        .agenda-card { border-radius: 16px; border: 1px solid #e5e7eb; }
        .agenda-table thead th { font-size: 0.72rem; text-transform: uppercase; color: #64748b; border-top: none; }
        .agenda-table tbody td { vertical-align: top; font-size: 0.85rem; }
        .agenda-preview { white-space: pre-line; font-size: 0.8rem; color: #334155; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; }
        .meeting-action-toggle-col { width: 46px; }
        .meeting-action-toggle { width: 28px; height: 28px; border: none; border-radius: 8px; background: linear-gradient(135deg, #2563eb, #3b82f6); color: #fff; font-size: 1rem; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; }
        .meeting-action-toggle.is-open { background: linear-gradient(135deg, #475569, #64748b); }
        .meeting-action-row { display: none; }
        .meeting-action-row td { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 12px 16px; }
        .meeting-action-panel { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
        .meeting-action-meta { color: #64748b; font-size: 0.82rem; margin-right: 10px; }
        .meeting-action-btn { display: inline-flex; align-items: center; gap: 8px; border-radius: 10px; padding: 7px 12px; font-size: 0.82rem; font-weight: 700; border: 1px solid transparent; background: #fff; color: #1f2937; }
        .meeting-action-btn.secondary { background: #f8fafc; color: #475569; border-color: #cbd5e1; }
        .meeting-action-btn.success { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .meeting-action-btn.primary { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .meeting-action-btn.danger { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div>
                <h1 class="mb-1">Agenda Pimpinan</h1>
                <div class="text-muted" style="font-size: 0.82rem;">Agenda protokoler untuk kegiatan pimpinan beserta daftar penerima dan format notifikasi formal.</div>
            </div>
            <button class="btn app-create-btn" data-toggle="modal" data-target="#createAgendaModal">
                <i class="fas fa-plus mr-1"></i> Tambah Agenda
            </button>
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

    <div class="card agenda-card">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0 agenda-table">
                <thead>
                    <tr>
                        <th class="meeting-action-toggle-col"></th>
                        <th>Agenda</th>
                        <th>Waktu WIT</th>
                        <th>Yang Menghadiri</th>
                        <th>Penerima</th>
                        <th>Lampiran</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agendas as $agenda)
                        <tr
                            data-agenda-id="{{ $agenda->id }}"
                            data-action="{{ route('rapat.agenda.update', $agenda) }}"
                            data-tanggal-kegiatan="{{ optional($agenda->tanggal_kegiatan)->format('Y-m-d') }}"
                            data-judul-agenda="{{ $agenda->judul_agenda }}"
                            data-tempat="{{ $agenda->tempat }}"
                            data-waktu="{{ $agenda->waktu_formatted }}"
                            data-yang-menghadiri="{{ $agenda->yang_menghadiri }}"
                            data-seragam-pakaian="{{ $agenda->seragam_pakaian }}"
                            data-nomor-naskah-dinas="{{ $agenda->nomor_naskah_dinas }}"
                            data-lampiran-link="{{ $agenda->lampiran_link }}"
                            data-catatan="{{ $agenda->catatan }}"
                            data-recipient-ids="{{ $agenda->recipients->pluck('id')->implode(',') }}"
                            data-whatsapp-preview="{{ $agenda->whatsapp_preview }}"
                        >
                            <td class="meeting-action-toggle-col">
                                <button type="button" class="meeting-action-toggle" aria-label="Toggle aksi">+</button>
                            </td>
                            <td>
                                <div class="font-weight-bold">{{ $agenda->judul_agenda }}</div>
                                <div class="text-muted" style="font-size: 0.78rem;">{{ $agenda->nomor_naskah_dinas ?: 'Tanpa nomor naskah dinas' }}</div>
                                @if($agenda->seragam_pakaian)
                                    <div class="text-muted" style="font-size: 0.78rem;">Pakaian: {{ $agenda->seragam_pakaian }}</div>
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
                        </tr>
                        <tr class="meeting-action-row">
                            <td colspan="6">
                                <div class="meeting-action-panel">
                                    <span class="meeting-action-meta">Tindakan agenda</span>
                                    <button type="button" class="meeting-action-btn secondary" onclick="previewWhatsapp({{ $agenda->id }})">
                                        <i class="fas fa-comment-dots"></i> Preview WA
                                    </button>
                                    <form action="{{ route('rapat.agenda.send-whatsapp', $agenda) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="meeting-action-btn success" onclick="return confirm('Kirim notifikasi WhatsApp agenda ini sekarang?')">
                                            <i class="fas fa-paper-plane"></i> Kirim WA
                                        </button>
                                    </form>
                                    <button type="button" class="meeting-action-btn primary" onclick="openEditAgenda({{ $agenda->id }})">
                                        <i class="fas fa-pen"></i> Edit
                                    </button>
                                    <form action="{{ route('rapat.agenda.destroy', $agenda) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="meeting-action-btn danger" onclick="return confirm('Hapus agenda pimpinan ini?')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </button>
                                    </form>
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
        'modalId' => 'createAgendaModal',
        'formId' => 'createAgendaForm',
        'title' => 'Tambah Agenda Pimpinan',
        'action' => route('rapat.agenda.store'),
        'method' => 'POST',
        'submitLabel' => 'Simpan Agenda',
        'users' => $users,
    ])

    @include('rapat.agenda.partials.form-modal', [
        'modalId' => 'editAgendaModal',
        'formId' => 'editAgendaForm',
        'title' => 'Edit Agenda Pimpinan',
        'action' => '#',
        'method' => 'PUT',
        'submitLabel' => 'Perbarui Agenda',
        'users' => $users,
    ])

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
            $('#createAgendaModal, #editAgendaModal').on('shown.bs.modal', function () {
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

            $(document).on('click', '.meeting-action-toggle', function () {
                const $button = $(this);
                const $actionRow = $button.closest('tr').next('.meeting-action-row');
                const isOpen = $actionRow.is(':visible');

                $('.meeting-action-row').hide();
                $('.meeting-action-toggle').removeClass('is-open').text('+');

                if (!isOpen) {
                    $actionRow.show();
                    $button.addClass('is-open').text('-');
                }
            });
        });

        function previewWhatsapp(agendaId) {
            const row = $('tr[data-agenda-id="' + agendaId + '"]');
            $('#whatsappPreviewContent').text(row.data('whatsappPreview'));
            $('#whatsappPreviewModal').modal('show');
        }

        function openEditAgenda(agendaId) {
            const row = $('tr[data-agenda-id="' + agendaId + '"]');
            const recipientIds = String(row.data('recipientIds') || '').split(',').filter(Boolean);

            $('#editAgendaForm').attr('action', row.data('action'));
            $('#editTanggalKegiatan').val(row.data('tanggalKegiatan'));
            $('#editJudulAgenda').val(row.data('judulAgenda'));
            $('#editTempat').val(row.data('tempat'));
            $('#editWaktu').val(row.data('waktu'));
            $('#editYangMenghadiri').val(row.data('yangMenghadiri'));
            $('#editSeragamPakaian').val(row.data('seragamPakaian'));
            $('#editNomorNaskahDinas').val(row.data('nomorNaskahDinas'));
            $('#editLampiranLink').val(row.data('lampiranLink'));
            $('#editCatatan').val(row.data('catatan'));
            $('#editRecipientIds').val(recipientIds).trigger('change');

            $('#editAgendaModal').modal('show');
        }
    </script>
@endpush
