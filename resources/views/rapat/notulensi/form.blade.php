@extends('layouts.app')

@section('title', $pageTitle)

@push('styles')
    <style>
        .notulensi-form-card {
            border-radius: 14px;
            border: 1px solid #e8eaed;
        }

        .notulensi-topbar {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
        }

        .notulensi-topbar-meta {
            font-size: 0.82rem;
            color: #64748b;
        }

        .notulensi-hint {
            font-size: 0.78rem;
            color: #64748b;
        }

        .notulensi-section-label {
            font-size: 0.84rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .notulensi-doc-item,
        .recommendation-item {
            border: 1px solid #e8eaed;
            border-radius: 12px;
            padding: 12px;
            background: #f8fafc;
        }

        .notulensi-doc-item + .notulensi-doc-item,
        .recommendation-item + .recommendation-item {
            margin-top: 10px;
        }

        .notulensi-doc-meta {
            font-size: 0.78rem;
            color: #64748b;
        }

        .ck-editor__editable_inline {
            min-height: 180px;
        }

        .recommendation-editor .ck-editor__editable_inline {
            min-height: 120px;
        }

        .recommendation-item-title {
            font-size: 0.82rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 10px;
        }

        .recommendation-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: flex-start;
        }

        .recommendation-row__editor {
            flex: 1 1 60%;
            min-width: 320px;
        }

        .recommendation-row__assignee {
            flex: 1 1 30%;
            min-width: 260px;
        }

        .recommendation-item .select2-container {
            width: 100% !important;
        }

        .recommendation-item .select2-container--bootstrap4 .select2-selection--multiple {
            min-height: 38px;
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
    @php
        $existingDocuments = collect($notulensi->dokumentasi_files ?: []);
        $recommendationItems = old('rekomendasi_items', $notulensi->rekomendasi_items ?: []);
        if (empty($recommendationItems)) {
            $recommendationItems = [['aksi' => '', 'user_ids' => []]];
        }
    @endphp

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

    <div class="notulensi-topbar">
        <div>
            <div class="notulensi-topbar-meta mb-1">
                Tanggal: {{ optional($rapat->tanggal)->translatedFormat('d F Y') }} |
                Waktu: {{ $rapat->waktu_mulai_formatted }} WIT |
                Tempat: {{ $rapat->tempat }} |
                Notulis: {{ optional(auth()->user())->name ?: '-' }}
            </div>
            <div class="notulensi-topbar-meta">
                Kategori Surat: {{ $rapat->kategori_surat_label }} | Peserta: {{ $rapat->pesertas->count() }} orang
            </div>
        </div>
        <div class="d-flex" style="gap:8px;">
            @if($notulensi->exists)
                <a href="{{ route('rapat.notulensi.pdf', $notulensi) }}" target="_blank" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf mr-1"></i>Buka PDF
                </a>
            @endif
        </div>
    </div>

    <div class="card notulensi-form-card">
        <div class="card-header bg-white">
            <strong>Form Notulen Template A</strong>
        </div>
        <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf
            @if($formMethod === 'PUT')
                @method('PUT')
            @endif
            <div class="card-body">
                <div class="form-group">
                    <label>Judul Notulen</label>
                    <input type="text" name="judul" class="form-control" value="{{ old('judul', $notulensi->judul ?: $rapat->judul) }}">
                </div>

                <div class="form-group">
                    <label class="notulensi-section-label">A. Uraian Kegiatan Agenda</label>
                    <textarea name="uraian_kegiatan" id="editor-uraian" class="form-control rich-editor" rows="8">{{ old('uraian_kegiatan', $notulensi->uraian_kegiatan) }}</textarea>
                    <div class="notulensi-hint mt-2">Bagian ini terisi otomatis dari data agenda, namun tetap bisa disesuaikan bila diperlukan.</div>
                </div>

                <div class="form-group">
                    <label class="notulensi-section-label">B. Agenda</label>
                    <textarea name="agenda_rapat" id="editor-agenda" class="form-control rich-editor" rows="8">{{ old('agenda_rapat', $notulensi->agenda_rapat) }}</textarea>
                </div>

                <div class="form-group">
                    <label class="notulensi-section-label">C. Susunan Agenda</label>
                    <textarea name="susunan_agenda" id="editor-susunan" class="form-control rich-editor" rows="8">{{ old('susunan_agenda', $notulensi->susunan_agenda) }}</textarea>
                </div>

                <div class="form-group">
                    <label class="notulensi-section-label">D. Hasil Agenda</label>
                    <textarea name="hasil_rapat" id="editor-hasil" class="form-control rich-editor" rows="8">{{ old('hasil_rapat', $notulensi->hasil_rapat) }}</textarea>
                </div>

                <div class="form-group">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="notulensi-section-label mb-0">E. Rekomendasi Tindak Lanjut</label>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addRecommendationItem">
                            <i class="fas fa-plus mr-1"></i>Tambah Rekomendasi
                        </button>
                    </div>

                    <div id="recommendationList">
                        @foreach($recommendationItems as $index => $item)
                            <div class="recommendation-item" data-index="{{ $index }}">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="recommendation-item-title">Poin {{ $index + 1 }}</div>
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-recommendation-item" {{ count($recommendationItems) === 1 ? 'style=display:none;' : '' }}>
                                        <i class="fas fa-trash-alt mr-1"></i>Hapus
                                    </button>
                                </div>
                                <div class="recommendation-row">
                                    <div class="recommendation-row__editor">
                                        <div class="form-group mb-lg-0">
                                            <label>Rekomendasi yang Harus Dilakukan</label>
                                            <textarea
                                                name="rekomendasi_items[{{ $index }}][aksi]"
                                                id="recommendation-editor-{{ $index }}"
                                                class="form-control recommendation-editor"
                                                rows="6">{{ $item['aksi'] ?? '' }}</textarea>
                                        </div>
                                    </div>
                                    <div class="recommendation-row__assignee">
                                        <div class="form-group mb-0">
                                            <label>Penanggung Jawab</label>
                                            <select name="rekomendasi_items[{{ $index }}][user_ids][]" class="form-control select2 recommendation-assignee-select" multiple>
                                                @foreach($rapat->pesertas as $peserta)
                                                    @php
                                                        $selectedIds = collect($item['user_ids'] ?? [])->map(function ($id) { return (int) $id; })->all();
                                                    @endphp
                                                    <option value="{{ $peserta->id }}" {{ in_array((int) $peserta->id, $selectedIds, true) ? 'selected' : '' }}>
                                                        {{ $peserta->name }}{{ optional($peserta->jabatan)->nama ? ' - ' . $peserta->jabatan->nama : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="notulensi-hint mt-2">Peserta yang dipilih pada poin ini akan mendapat tugas tindak lanjut.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="form-group">
                    <label>Dokumentasi Kegiatan Agenda @if(!$notulensi->exists)<span class="text-danger">*</span>@endif</label>
                    <input type="file" name="dokumentasi_files[]" class="form-control-file" accept="image/*" multiple {{ !$notulensi->exists ? 'required' : '' }}>
                    <div class="notulensi-hint mt-2">Upload foto dokumentasi kegiatan. Format gambar, maksimal 5 MB per file. @if(!$notulensi->exists) Minimal 1 file saat membuat notulensi. @endif</div>
                </div>

                @if($existingDocuments->isNotEmpty())
                    <div class="form-group">
                        <label>Dokumentasi Tersimpan</label>
                        @foreach($existingDocuments as $document)
                            <div class="notulensi-doc-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="font-weight-bold">{{ $document['nama'] ?? basename($document['path']) }}</div>
                                        <div class="notulensi-doc-meta">{{ !empty($document['size']) ? number_format($document['size'] / 1024, 1) . ' KB' : '-' }}</div>
                                    </div>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" name="remove_dokumentasi_files[]" value="{{ $document['path'] }}" id="removeDoc{{ $loop->index }}">
                                        <label class="form-check-label text-danger" for="removeDoc{{ $loop->index }}">Hapus</label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($notulensi->exists && $notulensi->tindakLanjuts->isNotEmpty())
                    <div class="form-group mb-0">
                        <label>Status Tindak Lanjut Saat Ini</label>
                        @foreach($notulensi->tindakLanjuts->groupBy('item_index') as $itemIndex => $groupedItems)
                            <div class="notulensi-doc-item">
                                <div class="font-weight-bold mb-2">Poin {{ $itemIndex + 1 }}</div>
                                @foreach($groupedItems as $item)
                                    <div class="d-flex justify-content-between align-items-center {{ !$loop->last ? 'mb-2' : '' }}">
                                        <span>{{ optional($item->user)->name ?: '-' }}</span>
                                        {!! $item->status_badge !!}
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <div class="text-muted" style="font-size: 0.78rem;">
                    Status saat ini: {!! $notulensi->exists ? $notulensi->status_badge : '<span class="badge badge-secondary">Draft</span>' !!}
                </div>
                <button type="submit" class="btn btn-primary">Simpan Notulen</button>
            </div>
        </form>
    </div>

    <template id="recommendationItemTemplate">
        <div class="recommendation-item" data-index="__INDEX__">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="recommendation-item-title">Poin __NUMBER__</div>
                <button type="button" class="btn btn-outline-danger btn-sm remove-recommendation-item">
                    <i class="fas fa-trash-alt mr-1"></i>Hapus
                </button>
            </div>
            <div class="recommendation-row">
                <div class="recommendation-row__editor">
                    <div class="form-group mb-lg-0">
                        <label>Rekomendasi yang Harus Dilakukan</label>
                        <textarea
                            name="rekomendasi_items[__INDEX__][aksi]"
                            id="recommendation-editor-__INDEX__"
                            class="form-control recommendation-editor"
                            rows="6"></textarea>
                    </div>
                </div>
                <div class="recommendation-row__assignee">
                    <div class="form-group mb-0">
                        <label>Penanggung Jawab</label>
                        <select name="rekomendasi_items[__INDEX__][user_ids][]" class="form-control select2 recommendation-assignee-select" multiple>
                            @foreach($rapat->pesertas as $peserta)
                                <option value="{{ $peserta->id }}">
                                    {{ $peserta->name }}{{ optional($peserta->jabatan)->nama ? ' - ' . $peserta->jabatan->nama : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="notulensi-hint mt-2">Peserta yang dipilih pada poin ini akan mendapat tugas tindak lanjut.</div>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <script>
        (function () {
            const editorIds = ['editor-uraian', 'editor-agenda', 'editor-susunan', 'editor-hasil'];
            const editors = {};
            let recommendationIndex = document.querySelectorAll('#recommendationList .recommendation-item').length;

            function buildToolbar() {
                return [
                    'heading', '|',
                    'bold', 'italic', 'bulletedList', 'numberedList', '|',
                    'blockQuote', 'insertTable', '|',
                    'undo', 'redo'
                ];
            }

            function initEditor(element) {
                if (!element || element.dataset.editorReady === '1') {
                    return;
                }

                ClassicEditor.create(element, {
                    toolbar: buildToolbar()
                }).then(function (editor) {
                    editors[element.id] = editor;
                    element.dataset.editorReady = '1';
                }).catch(function (error) {
                    console.error(error);
                });
            }

            function initSelect2(element) {
                if (!element || !window.jQuery) {
                    return;
                }

                const $element = window.jQuery(element);
                if ($element.hasClass('select2-hidden-accessible')) {
                    return;
                }

                $element.select2({
                    theme: 'bootstrap4',
                    width: '100%',
                    placeholder: 'Pilih penanggung jawab',
                    allowClear: true
                });
            }

            function initAllEditors() {
                editorIds.forEach(function (id) {
                    initEditor(document.getElementById(id));
                });

                document.querySelectorAll('.recommendation-editor').forEach(function (element) {
                    initEditor(element);
                });

                document.querySelectorAll('.recommendation-assignee-select').forEach(function (element) {
                    initSelect2(element);
                });
            }

            function refreshRecommendationButtons() {
                const items = document.querySelectorAll('#recommendationList .recommendation-item');
                items.forEach(function (item, index) {
                    const title = item.querySelector('.recommendation-item-title');
                    if (title) {
                        title.textContent = 'Poin ' + (index + 1);
                    }

                    const removeBtn = item.querySelector('.remove-recommendation-item');
                    if (removeBtn) {
                        removeBtn.style.display = items.length === 1 ? 'none' : '';
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function () {
                initAllEditors();
                refreshRecommendationButtons();

                const addButton = document.getElementById('addRecommendationItem');
                const recommendationList = document.getElementById('recommendationList');
                const template = document.getElementById('recommendationItemTemplate');

                if (addButton && recommendationList && template) {
                    addButton.addEventListener('click', function () {
                        const html = template.innerHTML
                            .replace(/__INDEX__/g, recommendationIndex)
                            .replace(/__NUMBER__/g, recommendationIndex + 1);
                        recommendationList.insertAdjacentHTML('beforeend', html);
                        initEditor(document.getElementById('recommendation-editor-' + recommendationIndex));
                        initSelect2(recommendationList.querySelector('.recommendation-item[data-index="' + recommendationIndex + '"] .recommendation-assignee-select'));
                        recommendationIndex += 1;
                        refreshRecommendationButtons();
                    });
                }

                document.addEventListener('click', function (event) {
                    const button = event.target.closest('.remove-recommendation-item');
                    if (!button) {
                        return;
                    }

                    const item = button.closest('.recommendation-item');
                    if (!item) {
                        return;
                    }

                    const select = item.querySelector('.recommendation-assignee-select');
                    if (select && window.jQuery && window.jQuery(select).hasClass('select2-hidden-accessible')) {
                        window.jQuery(select).select2('destroy');
                    }

                    item.remove();
                    refreshRecommendationButtons();
                });
            });
        })();
    </script>
@endpush
