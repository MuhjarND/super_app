@extends('layouts.app')

@section('title', $pageTitle)

@push('styles')
    <style>
        .notulensi-form-card {
            border-radius: 16px;
            border: 1px solid #e5e7eb;
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-start">
            <div>
                <h1 class="mb-1">{{ $pageTitle }}</h1>
                <div class="text-muted" style="font-size: 0.82rem;">{{ $rapat->judul }} | {{ $rapat->nomor_undangan }}</div>
            </div>
            <a href="{{ route('rapat.notulensi.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
    </div>
@endsection

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card notulensi-form-card">
                <div class="card-header bg-white">
                    <strong>Form Notulensi</strong>
                </div>
                <form action="{{ $formAction }}" method="POST">
                    @csrf
                    @if($formMethod === 'PUT')
                        @method('PUT')
                    @endif
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Template</label>
                                <select name="mode" class="form-control" required>
                                    <option value="template_a" {{ old('mode', $notulensi->mode) === 'template_a' ? 'selected' : '' }}>Template A</option>
                                    <option value="template_b" {{ old('mode', $notulensi->mode) === 'template_b' ? 'selected' : '' }}>Template B</option>
                                </select>
                            </div>
                            <div class="form-group col-md-8">
                                <label>Notulis</label>
                                <select name="notulis_id" class="form-control">
                                    <option value="">-- Pilih Notulis --</option>
                                    @foreach($notulisOptions as $user)
                                        <option value="{{ $user->id }}" {{ (string) old('notulis_id', $notulensi->notulis_id) === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Judul Notulensi</label>
                            <input type="text" name="judul" class="form-control" value="{{ old('judul', $notulensi->judul ?: $rapat->judul) }}">
                        </div>

                        <div class="form-group">
                            <label>A. Uraian Kegiatan Rapat</label>
                            <textarea name="uraian_kegiatan" class="form-control" rows="4" required>{{ old('uraian_kegiatan', $notulensi->uraian_kegiatan) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>B. Agenda Rapat</label>
                            <textarea name="agenda_rapat" class="form-control" rows="4" required>{{ old('agenda_rapat', $notulensi->agenda_rapat) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>C. Susunan Agenda Rapat</label>
                            <textarea name="susunan_agenda" class="form-control" rows="4">{{ old('susunan_agenda', $notulensi->susunan_agenda) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>D. Hasil Rapat</label>
                            <textarea name="hasil_rapat" class="form-control" rows="5" required>{{ old('hasil_rapat', $notulensi->hasil_rapat) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>E. Rekomendasi</label>
                            <textarea name="rekomendasi" class="form-control" rows="4">{{ old('rekomendasi', $notulensi->rekomendasi) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Dokumentasi Rapat</label>
                            <textarea name="dokumentasi_rapat" class="form-control" rows="4">{{ old('dokumentasi_rapat', $notulensi->dokumentasi_rapat) }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea name="catatan" class="form-control" rows="3">{{ old('catatan', $notulensi->catatan) }}</textarea>
                        </div>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between">
                        <div class="text-muted" style="font-size: 0.78rem;">
                            Status saat ini: {!! $notulensi->exists ? $notulensi->status_badge : '<span class="badge badge-secondary">Draft</span>' !!}
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Notulensi</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card notulensi-form-card mb-3">
                <div class="card-header bg-white">
                    <strong>Informasi Rapat</strong>
                </div>
                <div class="card-body">
                    <div class="mb-2"><strong>Tanggal:</strong> {{ optional($rapat->tanggal)->translatedFormat('d F Y') }}</div>
                    <div class="mb-2"><strong>Waktu:</strong> {{ $rapat->waktu_mulai_formatted }} WIT</div>
                    <div class="mb-2"><strong>Tempat:</strong> {{ $rapat->tempat }}</div>
                    <div class="mb-2"><strong>Kategori Surat:</strong> {{ $rapat->kategori_surat_label }}</div>
                    <div class="mb-0"><strong>Pembuat:</strong> {{ optional($rapat->creator)->name ?: '-' }}</div>
                </div>
            </div>

            @if($notulensi->exists)
                <div class="card notulensi-form-card">
                    <div class="card-header bg-white">
                        <strong>Upload File Notulensi</strong>
                    </div>
                    <form action="{{ route('rapat.notulensi.upload', $notulensi) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="card-body">
                            <div class="form-group">
                                <label>File Notulensi</label>
                                <input type="file" name="notulensi_file" class="form-control-file" accept=".pdf,.doc,.docx" required>
                                <small class="form-text text-muted">PDF, DOC, DOCX maksimal 10MB.</small>
                            </div>
                            <div class="form-group">
                                <label>Catatan Upload</label>
                                <textarea name="catatan_upload" class="form-control" rows="3"></textarea>
                            </div>
                            @if($notulensi->file_path)
                                <div class="alert alert-light border">
                                    File saat ini: <a href="{{ route('rapat.notulensi.file', $notulensi) }}" target="_blank">{{ $notulensi->file_nama }}</a>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer bg-white">
                            <button type="submit" class="btn btn-success btn-block">Upload dan Selesaikan</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
