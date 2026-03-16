@extends('layouts.app')

@section('title', 'Detail Surat Masuk')

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 style="display: flex; align-items: center; gap: 10px;">
                        <div
                            style="width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, #eff6ff, #dbeafe); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-envelope-open-text" style="font-size: 0.9rem; color: #3b82f6;"></i>
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
            <div class="card" style="border-top: 4px solid var(--primary);">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informasi Surat</h3>
                    {!! $suratMasuk->status_badge !!}
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td style="width: 180px; font-weight: 600; color: #718096;">Nomor Surat</td>
                            <td><strong class="text-primary">{{ $suratMasuk->nomor_surat }}</strong></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; color: #718096;">Opsi Pengirim</td>
                            <td>
                                <span
                                    class="badge badge-{{ $suratMasuk->opsi_pengirim == 'mahkamah_agung' ? 'primary' : 'secondary' }}">
                                    {{ $suratMasuk->opsi_pengirim == 'mahkamah_agung' ? 'Mahkamah Agung' : 'Non Mahkamah Agung' }}
                                </span>
                            </td>
                        </tr>
                        @if($suratMasuk->klasifikasiKode)
                            <tr>
                                <td style="font-weight: 600; color: #718096;">Kode Klasifikasi</td>
                                <td>{{ $suratMasuk->klasifikasiKode->kode }} - {{ $suratMasuk->klasifikasiKode->nama }}</td>
                            </tr>
                        @endif
                        @if($suratMasuk->kategoriSurat)
                            <tr>
                                <td style="font-weight: 600; color: #718096;">Kategori Surat</td>
                                <td>{{ $suratMasuk->kategoriSurat->kode }} - {{ $suratMasuk->kategoriSurat->nama }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td style="font-weight: 600; color: #718096;">Pengirim</td>
                            <td>{{ $suratMasuk->pengirim }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; color: #718096;">Perihal / Isi Ringkas</td>
                            <td>{{ $suratMasuk->perihal }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; color: #718096;">Tanggal Surat</td>
                            <td>{{ $suratMasuk->tanggal_surat->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; color: #718096;">Sifat</td>
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
                            <td style="font-weight: 600; color: #718096;">File Lampiran</td>
                            <td>
                                <a href="{{ route('surat-masuk.download', $suratMasuk) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download mr-1"></i> Download
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; color: #718096;">Di-input Oleh</td>
                            <td>{{ $suratMasuk->creator->name }} <small
                                    class="text-muted">{{ $suratMasuk->created_at->format('d/m/Y H:i') }}</small></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Disposition Action -->
            @if($canDisposisi)
                <div class="card" style="border-top: 4px solid var(--accent);">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-share mr-2"></i>Tindak Lanjut Surat</h3>
                    </div>
                    <div class="card-body">
                        <form id="disposisiForm">
                            @csrf
                            <input type="hidden" name="surat_masuk_id" value="{{ $suratMasuk->id }}">

                            <div class="form-group">
                                <label>Tujuan Disposisi <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="kepada_user_id" required>
                                    <option value="">-- Pilih Tujuan --</option>
                                    @foreach($targetDisposisi as $jabatan)
                                        @foreach($jabatan->users as $user)
                                            <option value="{{ $user->id }}"
                                                data-is-naikan="{{ in_array($jabatan->kode, ['KPTA', 'WKPTA']) ? '1' : '0' }}">
                                                {{ $user->name }} ({{ $jabatan->nama }})
                                            </option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Tipe</label>
                                <select class="form-control" name="tipe" id="tipeDisposisi">
                                    <option value="disposisi">Disposisi</option>
                                    <option value="naikan">Naikkan Surat</option>
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

                            <button type="submit" class="btn btn-accent" id="btnDisposisi">
                                <i class="fas fa-paper-plane mr-1"></i> Kirim Disposisi
                            </button>
                        </form>
                    </div>
                </div>
            @elseif($suratMasuk->status !== 'selesai')
                <div class="card" style="border-top: 4px solid #d1d5db;">
                    <div class="card-body">
                        <span class="text-muted">Surat ini tidak memiliki tindak lanjut yang tersedia untuk akun Anda saat ini.</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Disposition History -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history mr-2"></i>Riwayat Disposisi</h3>
                </div>
                <div class="card-body p-0">
                    @forelse($suratMasuk->disposisis->sortByDesc('created_at') as $disposisi)
                        <div class="p-3 border-bottom"
                            style="border-left: 3px solid {{ $disposisi->tipe == 'naikan' ? 'var(--primary)' : 'var(--accent)' }};">
                            <div class="d-flex justify-content-between mb-1">
                                {!! $disposisi->tipe_badge !!}
                                {!! $disposisi->status_badge !!}
                            </div>
                            <div class="mb-1">
                                <strong>{{ $disposisi->dariUser->name }}</strong>
                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                <strong>{{ $disposisi->kepadaUser->name }}</strong>
                            </div>
                            @if($disposisi->kepadaJabatan)
                                <small class="text-muted d-block">{{ $disposisi->kepadaJabatan->nama }}</small>
                            @endif
                            @if($disposisi->catatan)
                                <div class="mt-2 p-2" style="background: #f7fafc; border-radius: 6px;">
                                    <small><i class="fas fa-comment text-muted mr-1"></i>{{ $disposisi->catatan }}</small>
                                </div>
                            @endif
                            @if($disposisi->petunjuk)
                                <div class="mt-2 p-2" style="background: #eff6ff; border-radius: 6px;">
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
                            <small class="text-muted mt-1 d-block">
                                <i class="fas fa-clock mr-1"></i>{{ $disposisi->created_at->format('d/m/Y H:i') }}
                                ({{ $disposisi->created_at->diffForHumans() }})
                            </small>

                            @if($disposisi->kepada_user_id == auth()->id() && $disposisi->status == 'pending')
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-danger update-status" data-id="{{ $disposisi->id }}"
                                        data-status="ditindaklanjuti">
                                        <i class="fas fa-check mr-1"></i> Tandai Ditindaklanjuti
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

            <a href="{{ route('surat-masuk.index') }}" class="btn btn-secondary btn-block">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            // Auto-detect tipe based on selected target
            $('select[name="kepada_user_id"]').on('change', function () {
                let isNaikan = $(this).find('option:selected').data('is-naikan');
                if (isNaikan === 1 || isNaikan === '1') {
                    $('#tipeDisposisi').val('naikan');
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
                let catatanTindakLanjut = '';

                if (status === 'ditindaklanjuti') {
                    catatanTindakLanjut = window.prompt('Masukkan catatan tindak lanjut:', '');
                    if (catatanTindakLanjut === null) {
                        return;
                    }
                    if (!catatanTindakLanjut.trim()) {
                        showToast('Catatan tindak lanjut wajib diisi.', 'error');
                        return;
                    }
                }

                btn.prop('disabled', true);

                $.ajax({
                    url: '/disposisi/' + id + '/status',
                    method: 'PATCH',
                    data: {
                        _token: '{{ csrf_token() }}',
                        status: status,
                        catatan_tindak_lanjut: catatanTindakLanjut
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
        });
    </script>
@endpush
