@extends('layouts.app')

@section('title', 'Rapat')

@push('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <style>
        .rapat-card {
            border-radius: 16px;
            border: 1px solid #e5e7eb;
        }

        .rapat-card .card-body {
            padding: 16px;
        }

        .rapat-card .table thead th {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #64748b;
            border-top: none;
            border-bottom: 1px solid #e2e8f0;
        }

        .rapat-card .table tbody td {
            vertical-align: top;
            font-size: 0.86rem;
            color: #0f172a;
        }

        .rapat-title {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .rapat-meta {
            color: #64748b;
            font-size: 0.76rem;
        }

        .rapat-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.72rem;
            padding: 5px 10px;
            border-radius: 999px;
            font-weight: 600;
            margin-right: 6px;
            margin-bottom: 6px;
        }

        .rapat-chip.virtual {
            background: #ede9fe;
            color: #6d28d9;
        }

        .rapat-chip.pakaian {
            background: #fff7ed;
            color: #c2410c;
        }

        .rapat-chip.recurring {
            background: #ecfeff;
            color: #0f766e;
        }

        .form-hint {
            font-size: 0.74rem;
            color: #64748b;
        }

        .row-toggle-col {
            width: 46px;
        }

        .row-toggle-btn {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #fff;
            font-size: 1rem;
            font-weight: 700;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.18);
        }

        .row-toggle-btn.is-open {
            background: linear-gradient(135deg, #475569, #64748b);
            box-shadow: none;
        }

        .rapat-action-panel {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .rapat-action-meta {
            color: #64748b;
            font-size: 0.82rem;
            margin-right: 10px;
        }

        .action-chip-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 10px;
            padding: 7px 12px;
            font-size: 0.82rem;
            font-weight: 700;
            border: 1px solid transparent;
            background: #fff;
            color: #1f2937;
        }

        .action-chip-btn.action-lampiran {
            background: #f1f5f9;
            color: #334155;
            border-color: #cbd5e1;
        }

        .action-chip-btn.action-edit {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }

        .action-chip-btn.action-delete {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }

        .status-trigger-btn {
            border: none;
            background: transparent;
            padding: 0;
            text-align: left;
        }

        .status-trigger-btn .badge {
            cursor: pointer;
        }

        .status-trigger-btn small {
            display: block;
            color: #64748b;
            font-size: 0.72rem;
            margin-top: 4px;
        }

        .status-modal-section {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 16px;
            background: #f8fafc;
            margin-bottom: 14px;
        }

        .status-modal-title {
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            color: #334155;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .status-step {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .status-step:last-child {
            border-bottom: none;
        }

        .status-step-main {
            font-size: 0.88rem;
            font-weight: 700;
            color: #0f172a;
        }

        .status-step-sub {
            font-size: 0.77rem;
            color: #64748b;
            margin-top: 2px;
        }

        .status-history-item {
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .status-history-item:last-child {
            border-bottom: none;
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-1">Rapat</h1>
                    <div class="text-muted" style="font-size: 0.82rem;">Jadwal rapat, undangan, peserta, dan lampiran tambahan.</div>
                </div>
                @if(auth()->user()->canManageRapat())
                    <button class="btn btn-primary" data-toggle="modal" data-target="#createRapatModal">
                        <i class="fas fa-plus mr-1"></i> Tambah Rapat
                    </button>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="card rapat-card">
        <div class="card-body">
            <table id="rapatTable" class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="row-toggle-col"></th>
                        <th>Nomor / Judul</th>
                        <th>Kategori Surat</th>
                        <th>Waktu WIT</th>
                        <th>Tempat</th>
                        <th>Peserta</th>
                        <th>Approver</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rapats as $rapat)
                        <tr
                            data-rapat-id="{{ $rapat->id }}"
                            data-update-url="{{ route('rapat.update', $rapat) }}"
                            data-delete-url="{{ route('rapat.destroy', $rapat) }}"
                            data-lampiran-url="{{ $rapat->lampiran_tambahan_path ? route('rapat.lampiran', $rapat) : '' }}"
                            data-undangan-url="{{ route('rapat.undangan.preview', $rapat) }}"
                            data-nomor-undangan="{{ $rapat->nomor_undangan }}"
                            data-judul="{{ $rapat->judul }}"
                            data-deskripsi="{{ $rapat->deskripsi }}"
                            data-kategori-surat-kode="{{ $rapat->kategori_surat_kode_id }}"
                            data-nomenklatur-jabatan="{{ $rapat->nomenklatur_jabatan }}"
                            data-tanggal="{{ optional($rapat->tanggal)->format('Y-m-d') }}"
                            data-waktu-mulai="{{ $rapat->waktu_mulai_formatted }}"
                            data-tempat="{{ $rapat->tempat }}"
                            data-peserta-ids="{{ $rapat->pesertas->pluck('id')->implode(',') }}"
                            data-approver-1="{{ $rapat->approver_1_id }}"
                            data-approver-2="{{ $rapat->approver_2_id }}"
                            data-approval1-jabatan-manual="{{ $rapat->approval1_jabatan_manual }}"
                            data-detail-tambahan="{{ $rapat->detail_tambahan }}"
                            data-include-detail-tambahan="{{ $rapat->detail_tambahan ? 1 : 0 }}"
                            data-tujuan-surat="{{ $rapat->tujuan_surat }}"
                            data-jenis-pakaian="{{ $rapat->jenis_pakaian }}"
                            data-include-pakaian="{{ $rapat->jenis_pakaian ? 1 : 0 }}"
                            data-is-virtual="{{ $rapat->is_virtual ? 1 : 0 }}"
                            data-meeting-id="{{ $rapat->meeting_id }}"
                            data-meeting-passcode="{{ $rapat->meeting_passcode }}"
                            data-status="{{ $rapat->status }}"
                            data-is-recurring="{{ $rapat->is_recurring ? 1 : 0 }}"
                            data-recurring-pattern="{{ $rapat->recurring_pattern }}"
                            data-recurring-until="{{ optional($rapat->recurring_until)->format('Y-m-d') }}"
                            data-has-lampiran="{{ $rapat->lampiran_tambahan_path ? 1 : 0 }}"
                        >
                            <td class="row-toggle-col">
                                <button type="button" class="row-toggle-btn rapat-row-toggle" aria-label="Toggle aksi">+</button>
                            </td>
                            <td>
                                <div class="rapat-title">{{ $rapat->judul }}</div>
                                <div class="rapat-meta">{{ $rapat->nomor_undangan }}</div>
                                @if($rapat->deskripsi)
                                    <div class="rapat-meta mt-1">{{ \Illuminate\Support\Str::limit($rapat->deskripsi, 80) }}</div>
                                @endif
                            </td>
                            <td>
                                <div>{{ $rapat->kategori_surat_label }}</div>
                                <div class="rapat-meta">{{ $rapat->kategori_surat_kode_label }}</div>
                            </td>
                            <td>
                                <div>{{ $rapat->tanggal_wit_formatted }}</div>
                                <div class="rapat-meta">{{ $rapat->waktu_mulai_formatted }} WIT</div>
                            </td>
                            <td>{{ $rapat->tempat }}</td>
                            <td>
                                <div>{{ $rapat->pesertas->count() }} peserta</div>
                                <div class="rapat-meta">{{ $rapat->pesertas->take(2)->pluck('name')->implode(', ') }}{{ $rapat->pesertas->count() > 2 ? '...' : '' }}</div>
                            </td>
                            <td>
                                <div class="rapat-meta">{{ optional($rapat->approver1)->name ?? '-' }}</div>
                                <div class="rapat-meta">{{ optional($rapat->approver2)->name ?? '-' }}</div>
                            </td>
                            <td>
                                <button type="button" class="status-trigger-btn" onclick="openStatusModal({{ $rapat->id }})">
                                    {!! $rapat->status_badge !!}
                                    <small>Lihat riwayat approval</small>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div id="rapatActionTemplates" class="d-none">
        @foreach($rapats as $rapat)
            <div data-template-id="{{ $rapat->id }}">
                <div class="rapat-action-panel">
                    @if($rapat->is_virtual)
                        <span class="rapat-chip virtual"><i class="fas fa-video"></i> Virtual</span>
                    @endif
                    @if($rapat->jenis_pakaian)
                        <span class="rapat-chip pakaian"><i class="fas fa-tshirt"></i> {{ $rapat->jenis_pakaian }}</span>
                    @endif
                    @if($rapat->is_recurring)
                        <span class="rapat-chip recurring"><i class="fas fa-sync-alt"></i> {{ ucfirst($rapat->recurring_pattern) }}</span>
                    @endif
                    <span class="rapat-action-meta">Tindakan rapat</span>
                    @if($rapat->lampiran_tambahan_path)
                        <button type="button" class="action-chip-btn action-lampiran" onclick="previewLampiran('{{ route('rapat.lampiran', $rapat) }}')">
                            <i class="fas fa-paperclip"></i> Lampiran
                        </button>
                    @endif
                    <button type="button" class="action-chip-btn action-lampiran" onclick="previewLampiran('{{ route('rapat.undangan.preview', $rapat) }}')">
                        <i class="fas fa-file-pdf"></i> Undangan
                    </button>
                    @if(auth()->user()->canManageRapat())
                        <button type="button" class="action-chip-btn action-edit" onclick="openEditModal({{ $rapat->id }})">
                            <i class="fas fa-pen"></i> Edit
                        </button>
                        <button type="button" class="action-chip-btn action-delete" onclick="deleteRapat({{ $rapat->id }})">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div id="rapatStatusTemplates" class="d-none">
        @foreach($rapats as $rapat)
            <div data-status-template-id="{{ $rapat->id }}">
                <div class="status-modal-section">
                    <div class="status-modal-title">Informasi Status</div>
                    <div><strong>{{ $rapat->status_label }}</strong></div>
                    <div class="rapat-meta mt-1">{{ $rapat->nomor_undangan }}</div>
                    <div class="rapat-meta">{{ $rapat->judul }}</div>
                </div>

                <div class="status-modal-section">
                    <div class="status-modal-title">Urutan Approval</div>
                    @forelse($rapat->approvals->sortBy('step_order') as $step)
                        @php
                            $stepBadge = [
                                'pending' => ['warning', 'Pending'],
                                'waiting' => ['secondary', 'Waiting'],
                                'approved' => ['success', 'Approved'],
                                'rejected' => ['danger', 'Rejected'],
                            ][$step->status] ?? ['secondary', ucfirst($step->status)];
                        @endphp
                        <div class="status-step">
                            <div>
                                <div class="status-step-main">{{ $step->stage_label }} - {{ $step->approver_name_snapshot }}</div>
                                <div class="status-step-sub">{{ $step->approver_jabatan_snapshot ?: 'Tanpa jabatan' }}</div>
                                @if($step->acted_at)
                                    <div class="status-step-sub">{{ $step->acted_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') }} WIT</div>
                                @endif
                                @if($step->catatan)
                                    <div class="status-step-sub">Catatan: {{ $step->catatan }}</div>
                                @endif
                            </div>
                            <div><span class="badge badge-{{ $stepBadge[0] }}">{{ $stepBadge[1] }}</span></div>
                        </div>
                    @empty
                        <div class="rapat-meta">Belum ada workflow approval untuk rapat ini.</div>
                    @endforelse
                </div>

                <div class="status-modal-section">
                    <div class="status-modal-title">Riwayat Approval</div>
                    @forelse($rapat->approvalHistories->sortByDesc('acted_at') as $entry)
                        <div class="status-history-item">
                            <div class="status-step-main">{{ ucfirst($entry->action) }} - {{ $entry->approver_name_snapshot }}</div>
                            <div class="status-step-sub">{{ $entry->acted_at ? $entry->acted_at->copy()->timezone('Asia/Jayapura')->format('d/m/Y H:i') . ' WIT' : '-' }}</div>
                            @if($entry->catatan)
                                <div class="status-step-sub">Catatan: {{ $entry->catatan }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="rapat-meta">Belum ada riwayat approval.</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    @if(auth()->user()->canManageRapat())
        @include('rapat.partials.form-modal', ['modalId' => 'createRapatModal', 'formId' => 'createRapatForm', 'title' => 'Tambah Rapat', 'submitLabel' => 'Simpan', 'action' => route('rapat.store'), 'method' => 'POST'])
        @include('rapat.partials.form-modal', ['modalId' => 'editRapatModal', 'formId' => 'editRapatForm', 'title' => 'Edit Rapat', 'submitLabel' => 'Perbarui', 'action' => '#', 'method' => 'PUT'])
    @endif

    <div class="modal fade" id="lampiranModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Preview Lampiran</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="lampiranViewer" style="width: 100%; height: 75vh; border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Status dan Riwayat Approval</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body" id="statusModalBody"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(function () {
            const rapatTable = $('#rapatTable').DataTable({
                pageLength: 10,
                order: [[3, 'desc']],
                language: {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'No entries found',
                    paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
                }
            });

            $('#rapatTable tbody').on('click', '.rapat-row-toggle', function () {
                const tr = $(this).closest('tr');
                const row = rapatTable.row(tr);
                const rapatId = tr.data('rapatId');
                const $btn = $(this);
                const template = $('#rapatActionTemplates').find('[data-template-id="' + rapatId + '"]').html() || '';

                if (row.child.isShown()) {
                    row.child.hide();
                    tr.removeClass('shown');
                    $btn.removeClass('is-open').text('+');
                    return;
                }

                rapatTable.rows().every(function () {
                    const currentNode = $(this.node());
                    currentNode.find('.rapat-row-toggle').removeClass('is-open').text('+');
                    if (this.child.isShown()) {
                        this.child.hide();
                        currentNode.removeClass('shown');
                    }
                });

                row.child(template).show();
                tr.addClass('shown');
                $btn.addClass('is-open').text('-');
            });

            function toggleKategoriDependentFields(prefix) {}

            function togglePakaianFields(prefix) {
                const checked = $('#' + prefix + 'IncludePakaian').is(':checked');
                $('#' + prefix + 'PakaianGroup').toggle(checked);
                if (!checked) {
                    $('#' + prefix + 'JenisPakaian').val('');
                }
            }

            function toggleDetailTambahan(prefix) {
                const checked = $('#' + prefix + 'IncludeDetailTambahan').is(':checked');
                $('#' + prefix + 'DetailTambahanGroup').toggle(checked);
                if (!checked) {
                    $('#' + prefix + 'DetailTambahan').val('');
                }
            }

            function toggleVirtualFields(prefix) {
                const checked = $('#' + prefix + 'IsVirtual').is(':checked');
                $('#' + prefix + 'VirtualGroup').toggle(checked);
                if (!checked) {
                    $('#' + prefix + 'MeetingId').val('');
                    $('#' + prefix + 'MeetingPasscode').val('');
                }
            }

            function toggleRecurringFields(prefix) {
                const checked = $('#' + prefix + 'IsRecurring').is(':checked');
                $('#' + prefix + 'RecurringGroup').toggle(checked);
                if (!checked) {
                    $('#' + prefix + 'RecurringPattern').val('');
                    $('#' + prefix + 'RecurringUntil').val('');
                }
            }

            function toggleLampiranFields(prefix) {
                const checked = $('#' + prefix + 'GunakanLampiran').is(':checked');
                $('#' + prefix + 'LampiranGroup').toggle(checked);
                $('#' + prefix + 'TujuanSurat').prop('required', checked);
                $('#' + prefix + 'TujuanSuratRequired').toggle(checked);
                if (!checked) {
                    $('#' + prefix + 'Lampiran').val('');
                }
            }

            function bindFormBehavior(prefix) {
                $('#' + prefix + 'KategoriSuratKode').on('change', function () {
                    toggleKategoriDependentFields(prefix);
                    updateNomorPreview(prefix);
                });
                $('#' + prefix + 'IsVirtual').on('change', function () { toggleVirtualFields(prefix); });
                $('#' + prefix + 'IsRecurring').on('change', function () { toggleRecurringFields(prefix); });
                $('#' + prefix + 'GunakanLampiran').on('change', function () { toggleLampiranFields(prefix); });
                $('#' + prefix + 'IncludeDetailTambahan').on('change', function () { toggleDetailTambahan(prefix); });
                $('#' + prefix + 'IncludePakaian').on('change', function () { togglePakaianFields(prefix); });
                $('#' + prefix + 'Tanggal, #' + prefix + 'NomenklaturJabatan').on('change', function () {
                    updateNomorPreview(prefix);
                });
                toggleKategoriDependentFields(prefix);
                togglePakaianFields(prefix);
                toggleDetailTambahan(prefix);
                toggleVirtualFields(prefix);
                toggleRecurringFields(prefix);
                toggleLampiranFields(prefix);
                updateNomorPreview(prefix);
            }

            function updateNomorPreview(prefix) {
                if (!$('#' + prefix + 'NomorUndangan').length) {
                    return;
                }

                const kategoriSuratKodeId = $('#' + prefix + 'KategoriSuratKode').val();
                const tanggal = $('#' + prefix + 'Tanggal').val();
                const nomenklatur = $('#' + prefix + 'NomenklaturJabatan').val();

                if (!kategoriSuratKodeId) {
                    $('#' + prefix + 'NomorUndangan').val('');
                    return;
                }

                $.get('{{ route('rapat.preview-nomor') }}', {
                    kategori_surat_kode_id: kategoriSuratKodeId,
                    tanggal: tanggal,
                    nomenklatur_jabatan: nomenklatur
                }).done(function (response) {
                    $('#' + prefix + 'NomorUndangan').val(response.nomor || '');
                }).fail(function () {
                    $('#' + prefix + 'NomorUndangan').val('');
                });
            }

            @if(auth()->user()->canManageRapat())
                bindFormBehavior('create');
                bindFormBehavior('edit');

                $('#createRapatModal').on('shown.bs.modal', function () {
                    $(this).find('.select2').each(function () {
                        const $select = $(this);
                        if ($select.hasClass('select2-hidden-accessible')) {
                            $select.select2('destroy');
                        }
                        $select.select2({
                            theme: 'bootstrap4',
                            width: '100%',
                            dropdownParent: $('#createRapatModal')
                        });
                    });
                });

                $('#editRapatModal').on('shown.bs.modal', function () {
                    $(this).find('.select2').each(function () {
                        const $select = $(this);
                        if ($select.hasClass('select2-hidden-accessible')) {
                            $select.select2('destroy');
                        }
                        $select.select2({
                            theme: 'bootstrap4',
                            width: '100%',
                            dropdownParent: $('#editRapatModal')
                        });
                    });
                });

                $('#createRapatForm').on('submit', function (e) {
                    e.preventDefault();
                    submitRapatForm($(this), '{{ route('rapat.store') }}');
                });

                $('#editRapatForm').on('submit', function (e) {
                    e.preventDefault();
                    const action = $('#editRapatForm').data('action');
                    submitRapatForm($(this), action, true);
                });
            @endif

            window.previewLampiran = function (url) {
                $('#lampiranViewer').attr('src', url);
                $('#lampiranModal').modal('show');
            };

            window.openStatusModal = function (rapatId) {
                const html = $('#rapatStatusTemplates').find('[data-status-template-id="' + rapatId + '"]').html() || '<div class="text-muted">Status belum tersedia.</div>';
                $('#statusModalBody').html(html);
                $('#statusModal').modal('show');
            };

            window.deleteRapat = function (rapatId) {
                const row = $('tr[data-rapat-id="' + rapatId + '"]');
                const url = row.data('deleteUrl');

                if (!confirm('Hapus rapat ini?')) {
                    return;
                }

                $.ajax({
                    url: url,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        showToast(res.message, 'success');
                        location.reload();
                    },
                    error: function (xhr) {
                        showToast(xhr.responseJSON?.message || 'Gagal menghapus rapat.', 'error');
                    }
                });
            };

            @if(auth()->user()->canManageRapat())
                window.openEditModal = function (rapatId) {
                    const row = $('tr[data-rapat-id="' + rapatId + '"]');
                    const pesertaIds = String(row.data('pesertaIds') || '').split(',').filter(Boolean);

                    $('#editRapatForm').data('action', row.data('updateUrl'));
                    $('#editJudul').val(row.data('judul'));
                    $('#editDeskripsi').val(row.data('deskripsi'));
                    $('#editKategoriSuratKode').val(row.data('kategoriSuratKode')).trigger('change');
                    $('#editNomenklaturJabatan').val(row.data('nomenklaturJabatan'));
                    $('#editTanggal').val(row.data('tanggal'));
                    $('#editWaktuMulai').val(row.data('waktuMulai'));
                    $('#editTempat').val(row.data('tempat'));
                    $('#editPesertaIds').val(pesertaIds).trigger('change');
                    $('#editApprover1Id').val(row.data('approver1')).trigger('change');
                    $('#editApprover2Id').val(row.data('approver2')).trigger('change');
                    $('#editApproval1JabatanManual').val(row.data('approval1JabatanManual'));
                    $('#editIncludeDetailTambahan').prop('checked', Number(row.data('includeDetailTambahan')) === 1).trigger('change');
                    $('#editDetailTambahan').val(row.data('detailTambahan'));
                    $('#editTujuanSurat').val(row.data('tujuanSurat'));
                    $('#editIncludePakaian').prop('checked', Number(row.data('includePakaian')) === 1).trigger('change');
                    $('#editJenisPakaian').val(row.data('jenisPakaian'));
                    $('#editIsVirtual').prop('checked', Number(row.data('isVirtual')) === 1).trigger('change');
                    $('#editMeetingId').val(row.data('meetingId'));
                    $('#editMeetingPasscode').val(row.data('meetingPasscode'));
                    $('#editStatus').val(row.data('status'));
                    $('#editIsRecurring').prop('checked', Number(row.data('isRecurring')) === 1).trigger('change');
                    $('#editRecurringPattern').val(row.data('recurringPattern'));
                    $('#editRecurringUntil').val(row.data('recurringUntil'));
                    $('#editGunakanLampiran').prop('checked', Number(row.data('hasLampiran')) === 1).trigger('change');
                    $('#editHapusLampiranTambahan').prop('checked', false);
                    $('#editLampiranInfo').toggle(Number(row.data('hasLampiran')) === 1);

                    $('#editRapatModal').modal('show');
                };

                function submitRapatForm($form, url, usePostOverride) {
                    const formData = new FormData($form[0]);
                    if (usePostOverride) {
                        formData.append('_method', 'PUT');
                    }

                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function (res) {
                            showToast(res.message, 'success');
                            location.reload();
                        },
                        error: function (xhr) {
                            const errors = xhr.responseJSON?.errors;
                            let message = xhr.responseJSON?.message || 'Terjadi kesalahan.';
                            if (errors) {
                                message = Object.values(errors).flat().join('<br>');
                            }
                            showToast(message, 'error');
                        }
                    });
                }
            @endif
        });
    </script>
@endpush
