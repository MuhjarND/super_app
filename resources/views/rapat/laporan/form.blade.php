@extends('layouts.app')

@section('title', $pageTitle)

@push('styles')
    <style>
        .laporan-form-card {
            border-radius: 14px;
            border: 1px solid #e8eaed;
        }

        .laporan-topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }

        .laporan-topbar-meta,
        .laporan-hint {
            font-size: 0.82rem;
            color: #64748b;
        }

        .laporan-section-label {
            font-size: 0.9rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .ck-editor__editable_inline {
            min-height: 180px;
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
            <a href="{{ route('rapat.laporan.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
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

    <div class="laporan-topbar">
        <div>
            <div class="laporan-topbar-meta mb-1">
                Tanggal: {{ optional($rapat->tanggal)->translatedFormat('d F Y') }} |
                Waktu: {{ $rapat->waktu_mulai_formatted }} WIT |
                Tempat: {{ $rapat->tempat }}
            </div>
            <div class="laporan-topbar-meta">
                Kategori Surat: {{ $rapat->kategori_surat_label }} | Peserta: {{ $rapat->pesertas->count() }} orang
            </div>
        </div>
        <div class="d-flex" style="gap:8px;">
            @if($laporan->file_path)
                <a href="{{ route('rapat.laporan.preview', $laporan) }}" target="_blank" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf mr-1"></i>Buka PDF Final
                </a>
            @endif
        </div>
    </div>

    <div class="card laporan-form-card">
        <div class="card-header bg-white">
            <strong>Form Laporan Tindak Lanjut</strong>
        </div>
        <form action="{{ $formAction }}" method="POST" novalidate>
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="alert alert-light border">
                    File final akan digenerate otomatis menjadi satu PDF gabungan dengan urutan: undangan, absensi, notulensi, lalu laporan tindak lanjut ini.
                </div>

                <div class="form-group">
                    <label>Judul Laporan</label>
                    <input type="text" name="judul" class="form-control" value="{{ old('judul', $laporan->judul) }}" required>
                </div>

                <div class="form-group">
                    <label class="laporan-section-label">Bab 1 - Latar Belakang</label>
                    <textarea name="bab_1_latar_belakang" id="editor-bab1-latar" class="form-control rich-editor" rows="8">{{ old('bab_1_latar_belakang', $laporan->bab_1_latar_belakang ?: ($defaultSections['bab_1_latar_belakang'] ?? '')) }}</textarea>
                </div>

                <div class="form-group">
                    <label class="laporan-section-label">Bab 1 - Dasar</label>
                    <textarea name="bab_1_dasar" id="editor-bab1-dasar" class="form-control rich-editor" rows="8">{{ old('bab_1_dasar', $laporan->bab_1_dasar ?: ($defaultSections['bab_1_dasar'] ?? '')) }}</textarea>
                </div>

                <div class="form-group">
                    <label class="laporan-section-label">Bab 1 - Tujuan</label>
                    <textarea name="bab_1_tujuan" id="editor-bab1-tujuan" class="form-control rich-editor" rows="8">{{ old('bab_1_tujuan', $laporan->bab_1_tujuan ?: ($defaultSections['bab_1_tujuan'] ?? '')) }}</textarea>
                </div>

                <div class="form-group">
                    <label class="laporan-section-label">Bab 2 - Hasil Monitoring dan Evaluasi</label>
                    <textarea name="bab_2_hasil_monitoring" id="editor-bab2" class="form-control rich-editor" rows="8">{{ old('bab_2_hasil_monitoring', $laporan->bab_2_hasil_monitoring ?: ($defaultSections['bab_2_hasil_monitoring'] ?? '')) }}</textarea>
                </div>

                <div class="form-group mb-0">
                    <label class="laporan-section-label">Bab 3 - Tindak Lanjut dan Rekomendasi</label>
                    <textarea name="bab_3_tindak_lanjut" id="editor-bab3" class="form-control rich-editor" rows="8">{{ old('bab_3_tindak_lanjut', $laporan->bab_3_tindak_lanjut ?: ($defaultSections['bab_3_tindak_lanjut'] ?? '')) }}</textarea>
                    <div class="laporan-hint mt-2">Jika eviden tindak lanjut sudah ada, biarkan tautannya tetap di Bab 3 agar ikut masuk ke PDF final.</div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <div class="text-muted" style="font-size: 0.78rem;">
                    Status saat ini: {!! $laporan->status_badge !!}
                </div>
                <button type="submit" class="btn btn-primary">Simpan dan Generate PDF</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <script>
        (function () {
            const editorIds = [
                'editor-bab1-latar',
                'editor-bab1-dasar',
                'editor-bab1-tujuan',
                'editor-bab2',
                'editor-bab3'
            ];

            function buildToolbar() {
                return [
                    'heading', '|',
                    'bold', 'italic', 'bulletedList', 'numberedList', '|',
                    'blockQuote', 'insertTable', 'link', '|',
                    'undo', 'redo'
                ];
            }

            function initEditor(element) {
                if (!element || element.dataset.editorReady === '1') {
                    return;
                }

                ClassicEditor.create(element, {
                    toolbar: buildToolbar()
                }).then(function () {
                    element.dataset.editorReady = '1';
                }).catch(function (error) {
                    console.error(error);
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                editorIds.forEach(function (id) {
                    initEditor(document.getElementById(id));
                });
            });
        })();
    </script>
@endpush
