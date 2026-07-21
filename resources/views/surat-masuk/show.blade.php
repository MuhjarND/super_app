@extends('layouts.app')

@section('title', 'Detail Surat Masuk')

@push('styles')
    <style>
        .surat-masuk-detail-table td {
            padding: 0.72rem 0.5rem;
            vertical-align: top;
        }

        .surat-masuk-detail-table td:first-child {
            width: 180px;
            font-weight: 600;
            color: #718096;
        }

        .surat-masuk-disposisi-card,
        .surat-masuk-history-card {
            height: 100%;
        }

        @media (max-width: 767.98px) {
            .content-header .breadcrumb {
                display: none;
            }

            .surat-masuk-header-title {
                font-size: 1.15rem;
                line-height: 1.25;
                gap: 8px !important;
            }

            .surat-masuk-header-title > div {
                width: 32px !important;
                height: 32px !important;
                border-radius: 9px !important;
            }

            .surat-masuk-detail-card .card-header,
            .surat-masuk-disposisi-card .card-header,
            .surat-masuk-history-card .card-header {
                align-items: flex-start !important;
                gap: 8px;
            }

            .surat-masuk-detail-table,
            .surat-masuk-detail-table tbody,
            .surat-masuk-detail-table tr,
            .surat-masuk-detail-table td {
                display: block;
                width: 100%;
            }

            .surat-masuk-detail-table tr {
                padding: 10px 0;
                border-bottom: 1px solid #edf2f7;
            }

            .surat-masuk-detail-table tr:last-child {
                border-bottom: 0;
            }

            .surat-masuk-detail-table td {
                padding: 0;
            }

            .surat-masuk-detail-table td:first-child {
                width: auto;
                margin-bottom: 4px;
                font-size: 0.74rem;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                color: #94a3b8;
            }

            .surat-masuk-detail-table td:last-child {
                font-size: 0.92rem;
                line-height: 1.5;
            }

            .surat-masuk-history-card .card-body {
                padding: 0 !important;
            }

            .surat-masuk-history-entry {
                padding: 14px 14px 14px 16px !important;
            }

            .surat-masuk-history-entry .d-flex.justify-content-between {
                flex-wrap: wrap;
                gap: 8px;
            }

        .surat-masuk-back-btn {
                margin-top: 14px;
            }
        }

        .surat-delegation-context {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
            padding: 12px 14px;
            border: 1px solid #fed7aa;
            border-radius: 11px;
            background: #fff7ed;
            color: #9a3412;
            font-size: .82rem;
            line-height: 1.45;
        }

        .surat-delegation-context.direct {
            border-color: #c7d2fe;
            background: #eef2ff;
            color: #3730a3;
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="surat-masuk-header-title" style="display: flex; align-items: center; gap: 10px;">
                        <div
                            style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #eef2ff, #e0e7ff); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-envelope-open-text" style="font-size: 0.9rem; color: #6366f1;"></i>
                        </div>
                        Detail Surat Masuk
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('surat-masuk.index') }}">Surat Masuk</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <!-- Detail Card -->
        <div class="col-lg-7">
            <div class="card surat-masuk-detail-card" style="border-top: 4px solid var(--primary);">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informasi Surat</h3>
                    {!! $suratMasuk->status_badge !!}
                </div>
                <div class="card-body">
                    @if(auth()->user()->hasActiveJabatanDelegation() && $assignmentContext)
                        <div class="surat-delegation-context {{ data_get($assignmentContext, 'mode') === 'direct' ? 'direct' : '' }}">
                            <i class="fas {{ data_get($assignmentContext, 'mode') === 'delegated' ? 'fa-user-shield' : 'fa-user-check' }} mt-1"></i>
                            <div>
                                <strong>{{ data_get($assignmentContext, 'badge') }}</strong>
                                <div>{{ data_get($assignmentContext, 'description') }}</div>
                            </div>
                        </div>
                    @endif
                    <table class="table table-borderless surat-masuk-detail-table mb-0">
                        <tr>
                            <td>Nomor Surat</td>
                            <td><strong class="text-primary">{{ $suratMasuk->nomor_surat }}</strong></td>
                        </tr>
                        <tr>
                            <td>Opsi Pengirim</td>
                            <td>
                                <span
                                    class="badge badge-{{ $suratMasuk->opsi_pengirim == 'mahkamah_agung' ? 'primary' : 'secondary' }}">
                                    {{ $suratMasuk->opsi_pengirim == 'mahkamah_agung' ? 'Mahkamah Agung' : 'Non Mahkamah Agung' }}
                                </span>
                            </td>
                        </tr>
                        @if($suratMasuk->klasifikasiKode)
                            <tr>
                                <td>Kode Klasifikasi</td>
                                <td>{{ $suratMasuk->klasifikasiKode->kode }} - {{ $suratMasuk->klasifikasiKode->nama }}</td>
                            </tr>
                        @endif
                        @if($suratMasuk->kategoriSurat)
                            <tr>
                                <td>Kategori Surat</td>
                                <td>{{ $suratMasuk->kategoriSurat->kode }} - {{ $suratMasuk->kategoriSurat->nama }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td>Pengirim</td>
                            <td>{{ $suratMasuk->pengirim }}</td>
                        </tr>
                        <tr>
                            <td>Perihal / Isi Ringkas</td>
                            <td>{{ $suratMasuk->perihal }}</td>
                        </tr>
                        <tr>
                            <td>Tanggal Surat</td>
                            <td>{{ $suratMasuk->tanggal_surat->translatedFormat('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td>Sifat</td>
                            <td>
                                @php
                                    $sifatColors = ['biasa' => 'info', 'rahasia' => 'warning', 'sangat_rahasia' => 'danger'];
                                    $sifatLabels = ['biasa' => 'Biasa', 'rahasia' => 'Rahasia', 'sangat_rahasia' => 'Sangat Rahasia'];
                                @endphp
                                <span class="badge badge-{{ $sifatColors[$suratMasuk->sifat] }}">
                                    {{ $sifatLabels[$suratMasuk->sifat] }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>File Lampiran</td>
                            <td>
                                <a href="{{ route('surat-masuk.download', $suratMasuk) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download mr-1"></i> Download
                                </a>
                            </td>
                        </tr>
                        @if($suratMasuk->agendaPimpinan)
                            <tr>
                                <td>Agenda Pimpinan</td>
                                <td>
                                    <div class="font-weight-bold">{{ $suratMasuk->agendaPimpinan->judul_agenda }}</div>
                                    <div class="text-muted small">
                                        {{ $suratMasuk->agendaPimpinan->tanggal_formatted }}
                                        {{ $suratMasuk->agendaPimpinan->waktu_formatted }} WIT
                                    </div>
                                    <div class="text-muted small">Tempat: {{ $suratMasuk->agendaPimpinan->tempat }}</div>
                                    @if($suratMasuk->agendaPimpinan->seragam_pakaian)
                                        <div class="text-muted small">Pakaian: {{ $suratMasuk->agendaPimpinan->seragam_pakaian }}</div>
                                    @endif
                                    @if(auth()->user()->canAccessAgendaPimpinan())
                                        <a href="{{ route('rapat.agenda.index') }}" class="btn btn-sm btn-outline-primary mt-2">
                                            <i class="fas fa-calendar-day mr-1"></i> Buka Agenda
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endif
                        <tr>
                            <td>Di-input Oleh</td>
                            <td>{{ $suratMasuk->creator->name }} <small
                                    class="text-muted">{{ $suratMasuk->created_at->format('d/m/Y H:i') }}</small></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Disposition Action -->
            @if($canDisposisi)
                <div class="card surat-masuk-disposisi-card" style="border-top: 4px solid var(--accent);">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-share mr-2"></i>Tindak Lanjut Surat</h3>
                    </div>
                    <div class="card-body">
                        @if(data_get($assignmentContext, 'mode') === 'delegated')
                            <div class="surat-delegation-context mb-3">
                                <i class="fas fa-user-shield mt-1"></i>
                                <div>{{ data_get($assignmentContext, 'action_label') }}</div>
                            </div>
                        @endif
                        <form id="disposisiForm">
                            @csrf
                            <input type="hidden" name="surat_masuk_id" value="{{ $suratMasuk->id }}">

                            <div class="form-group">
                                <label>Tujuan Disposisi <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="kepada_user_id" id="disposisiTargetShow" required>
                                    <option value="">Memuat tujuan...</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Tipe</label>
                                <select class="form-control" name="tipe" id="tipeDisposisi">
                                    <option value="disposisi">Disposisi</option>
                                    @if($canNaikanSurat)
                                        <option value="naikan">Naikkan Surat</option>
                                    @endif
                                </select>
                            </div>

                            @if($showPetunjuk)
                                <div class="form-group">
                                    <label>Petunjuk <span class="text-danger">*</span></label>
                                    <select class="form-control" name="petunjuk" required>
                                        <option value="">-- Pilih Petunjuk --</option>
                                        @foreach($petunjukOptions as $petunjuk)
                                            <option value="{{ $petunjuk }}">{{ $petunjuk }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="form-group">
                                <label>Catatan</label>
                                <textarea class="form-control" name="catatan" rows="3"
                                    placeholder="Catatan disposisi (opsional)"></textarea>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label>Prioritas <span class="text-danger">*</span></label>
                                    <select class="form-control" name="priority_level" required>
                                        <option value="normal">Normal</option>
                                        <option value="high">Tinggi</option>
                                        <option value="low">Rendah</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Target Tindak Lanjut</label>
                                    <input type="datetime-local" class="form-control" name="target_tindak_lanjut_at">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-accent" id="btnDisposisi">
                                <i class="fas fa-paper-plane mr-1"></i> Kirim Disposisi
                            </button>
                        </form>
                    </div>
                </div>
            @elseif($suratMasuk->status !== 'selesai')
                <div class="card surat-masuk-disposisi-card" style="border-top: 4px solid #d1d5db;">
                    <div class="card-body">
                        <span class="text-muted">Surat ini tidak memiliki tindak lanjut yang tersedia untuk akun Anda saat ini.</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Disposition History -->
        <div class="col-lg-5">
            <div class="card surat-masuk-history-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history mr-2"></i>Riwayat Disposisi</h3>
                </div>
                <div class="card-body p-0">
                    @forelse($suratMasuk->disposisis->sortByDesc('created_at') as $disposisi)
                        <div class="p-3 border-bottom surat-masuk-history-entry"
                            style="border-left: 3px solid {{ $disposisi->tipe == 'naikan' ? 'var(--primary)' : 'var(--accent)' }};">
                            <div class="d-flex justify-content-between mb-1">
                                {!! $disposisi->tipe_badge !!}
                                {!! $disposisi->status_badge !!}
                            </div>
                            <div class="mb-2">
                                {!! $disposisi->priority_badge !!}
                                <small class="text-muted d-block mt-1">Target tindak lanjut: {{ $disposisi->target_label }}</small>
                            </div>
                            <div class="mb-1">
                                <strong>{{ $disposisi->dariUser->name }}</strong>
                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                <strong>{{ $disposisi->kepadaUser->name }}</strong>
                            </div>
                            @if($disposisi->kepadaJabatan)
                                <small class="text-muted d-block">{{ $disposisi->kepadaJabatan->nama }}</small>
                            @endif
                            @php
                                $historyAssignmentContext = $disposisi->assignmentContextFor(auth()->user());
                            @endphp
                            @if(data_get($historyAssignmentContext, 'mode') === 'delegated')
                                <div class="surat-delegation-context mt-2 mb-0 py-2">
                                    <i class="fas fa-user-shield mt-1"></i>
                                    <div>{{ data_get($historyAssignmentContext, 'description') }}</div>
                                </div>
                            @endif
                            @if($disposisi->catatan)
                                <div class="mt-2 p-2" style="background: #f7fafc; border-radius: 6px;">
                                    <small><i class="fas fa-comment text-muted mr-1"></i>{{ $disposisi->catatan }}</small>
                                </div>
                            @endif
                            @if($disposisi->petunjuk)
                                <div class="mt-2 p-2" style="background: #eef2ff; border-radius: 6px;">
                                    <small><i
                                            class="fas fa-hand-point-right text-primary mr-1"></i>{{ $disposisi->petunjuk }}</small>
                                </div>
                            @endif
                            @if($disposisi->catatan_tindak_lanjut)
                                <div class="mt-2 p-2" style="background: #fef2f2; border-radius: 6px;">
                                    <small><i
                                            class="fas fa-flag text-danger mr-1"></i>{{ $disposisi->catatan_tindak_lanjut }}</small>
                                </div>
                            @endif
                            @if($disposisi->tautan_tindak_lanjut)
                                <div class="mt-2 p-2" style="background: #f0fdf4; border-radius: 6px;">
                                    <a href="{{ $disposisi->tautan_tindak_lanjut }}" target="_blank"
                                        rel="noopener noreferrer" class="small font-weight-bold">
                                        <i class="fas fa-link text-success mr-1"></i>Buka link dokumentasi
                                    </a>
                                </div>
                            @endif
                            @if($disposisi->dokumentasis->isNotEmpty())
                                <div class="mt-2 p-2" style="background: #eff6ff; border-radius: 6px;">
                                    <small class="d-block font-weight-bold mb-1">
                                        <i class="fas fa-paperclip text-primary mr-1"></i>Dokumentasi
                                    </small>
                                    @foreach($disposisi->dokumentasis as $dokumentasi)
                                        <a href="{{ route('disposisi.dokumentasi.preview', $dokumentasi) }}"
                                            target="_blank" rel="noopener" class="btn btn-xs btn-outline-primary mr-1 mb-1">
                                            <i class="fas fa-file mr-1"></i>{{ $dokumentasi->original_name }}
                                            <span class="text-muted">({{ $dokumentasi->formatted_size }})</span>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                            <small class="text-muted mt-1 d-block">
                                <i class="fas fa-clock mr-1"></i>{{ $disposisi->created_at->format('d/m/Y H:i') }}
                                ({{ $disposisi->created_at->diffForHumans() }})
                            </small>

                            @if(auth()->user()->canFollowUpDisposisi($disposisi) && $disposisi->status == 'pending')
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-primary update-status" data-id="{{ $disposisi->id }}"
                                        data-status="dibaca">
                                        <i class="fas fa-book-reader mr-1"></i> Tandai Dibaca
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger update-status" data-id="{{ $disposisi->id }}"
                                        data-status="ditindaklanjuti">
                                        <i class="fas fa-check mr-1"></i> Tandai Ditindaklanjuti
                                    </button>
                                </div>
                            @endif
                            @if((auth()->user()->isSuperAdmin() || auth()->id() == $disposisi->dari_user_id) && $disposisi->status !== 'ditindaklanjuti')
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-secondary remind-disposisi" data-id="{{ $disposisi->id }}">
                                        <i class="fab fa-whatsapp mr-1"></i> Kirim Pengingat
                                    </button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-clock fa-2x mb-2 d-block" style="opacity: 0.3;"></i>
                            Belum ada disposisi
                        </div>
                    @endforelse
                </div>
            </div>

            <a href="{{ route('surat-masuk.index') }}" class="btn btn-secondary btn-block surat-masuk-back-btn">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    <div class="modal fade" id="followUpDokumentasiModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="followUpDokumentasiForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="followUpDisposisiId">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-clipboard-check mr-2 text-primary"></i>Selesaikan Tindak Lanjut</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="followUpCatatan">Catatan Tindak Lanjut <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="followUpCatatan" rows="4"
                                placeholder="Jelaskan hasil tindak lanjut" required></textarea>
                        </div>
                        <div class="form-group mb-0">
                            <label for="followUpTautan">Link Dokumentasi</label>
                            <input type="url" class="form-control" id="followUpTautan"
                                name="tautan_tindak_lanjut" maxlength="2048"
                                placeholder="https://contoh.go.id/dokumentasi">
                            <small class="form-text text-muted">Opsional. Gunakan link yang diawali http:// atau https://.</small>
                        </div>
                        <div class="form-group mt-3 mb-0">
                            <label for="followUpDokumentasi">File Dokumentasi</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="followUpDokumentasi"
                                    name="dokumentasi[]" accept=".jpg,.jpeg,.png,.webp,.pdf,.docx" multiple>
                                <label class="custom-file-label" for="followUpDokumentasi">Pilih file</label>
                            </div>
                            <small class="form-text text-muted">Maksimal 5 file, masing-masing 10 MB. Format: gambar, PDF, atau DOCX.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnFollowUpDokumentasi">
                            <i class="fas fa-check mr-1"></i>Simpan Tindak Lanjut
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            function loadDisposisiTargetsShow() {
                var tipe = $('#tipeDisposisi').val();
                var $target = $('#disposisiTargetShow');

                $target.html('<option value="">Memuat tujuan...</option>').trigger('change.select2');

                $.get('{{ route("api.disposisi.targets") }}', {
                    surat_masuk_id: '{{ $suratMasuk->id }}',
                    tipe: tipe
                }, function (res) {
                    var options = '<option value="">-- Pilih Tujuan --</option>';
                    if (res && res.length) {
                        res.forEach(function (item) {
                            options += '<option value="' + item.id + '">' + item.name + ' (' + (item.jabatan || '-') + ')</option>';
                        });
                    } else {
                        options = '<option value="">Tidak ada pegawai tujuan untuk tipe ini</option>';
                    }
                    $target.html(options).trigger('change.select2');
                }).fail(function () {
                    $target.html('<option value="">Gagal memuat tujuan</option>').trigger('change.select2');
                });
            }

            $('#tipeDisposisi').on('change', loadDisposisiTargetsShow);
            loadDisposisiTargetsShow();

            $('select[name="kepada_user_id"]').on('change', function () {
                if ($('#tipeDisposisi').val() === 'naikan') {
                    return;
                }
                let isNaikan = $(this).find('option:selected').data('is-naikan');
                if (isNaikan === 1 || isNaikan === '1') {
                    $('#tipeDisposisi').val('naikan').trigger('change');
                } else {
                    $('#tipeDisposisi').val('disposisi');
                }
            });

            // Submit disposisi
            $('#disposisiForm').on('submit', function (e) {
                e.preventDefault();
                let btn = $('#btnDisposisi');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Mengirim...');

                $.ajax({
                    url: '{{ route("disposisi.store") }}',
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function (res) {
                        showToast(res.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    },
                    error: function (xhr) {
                        let msg = xhr.responseJSON?.message || 'Terjadi kesalahan.';
                        showToast(msg, 'error');
                    },
                    complete: function () {
                        btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i> Kirim Disposisi');
                    }
                });
            });

            // Update disposition status
            $('.update-status').on('click', function () {
                let id = $(this).data('id');
                let status = $(this).data('status');
                let btn = $(this);

                if (status === 'ditindaklanjuti') {
                    $('#followUpDokumentasiForm')[0].reset();
                    $('#followUpDokumentasi').next('.custom-file-label').text('Pilih file');
                    $('#followUpDisposisiId').val(id);
                    $('#followUpDokumentasiModal').modal('show');
                    return;
                }

                btn.prop('disabled', true);

                $.ajax({
                    url: '/disposisi/' + id + '/status',
                    method: 'PATCH',
                    data: {
                        _token: '{{ csrf_token() }}',
                        status: status
                    },
                    success: function (res) {
                        showToast(res.message, 'success');
                        setTimeout(() => location.reload(), 1000);
                    },
                    error: function (xhr) {
                        showToast('Gagal memperbarui status.', 'error');
                        btn.prop('disabled', false);
                    }
                });
            });

            $('#followUpDokumentasi').on('change', function () {
                const count = this.files.length;
                const label = count === 0 ? 'Pilih file' : (count === 1 ? this.files[0].name : count + ' file dipilih');
                $(this).next('.custom-file-label').text(label);
            });

            $('#followUpDokumentasiForm').on('submit', function (event) {
                event.preventDefault();

                const disposisiId = $('#followUpDisposisiId').val();
                const btn = $('#btnFollowUpDokumentasi');
                const formData = new FormData(this);
                formData.append('_method', 'PATCH');
                formData.append('status', 'ditindaklanjuti');
                formData.append('catatan_tindak_lanjut', $('#followUpCatatan').val());

                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');

                $.ajax({
                    url: '/disposisi/' + disposisiId + '/status',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (res) {
                        showToast(res.message, 'success');
                        $('#followUpDokumentasiModal').modal('hide');
                        setTimeout(() => location.reload(), 1000);
                    },
                    error: function (xhr) {
                        const errors = xhr.responseJSON?.errors;
                        const message = errors
                            ? Object.values(errors).flat().join('<br>')
                            : (xhr.responseJSON?.message || 'Gagal menyimpan tindak lanjut.');
                        showToast(message, 'error');
                    },
                    complete: function () {
                        btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i>Simpan Tindak Lanjut');
                    }
                });
            });

            $('.remind-disposisi').on('click', function () {
                let id = $(this).data('id');
                let btn = $(this);
                btn.prop('disabled', true);

                $.ajax({
                    url: '/disposisi/' + id + '/remind',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function (res) {
                        showToast(res.message, 'success');
                    },
                    error: function (xhr) {
                        showToast(xhr.responseJSON?.message || 'Gagal mengirim pengingat.', 'error');
                    },
                    complete: function () {
                        btn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush
