@extends('layouts.app')

@section('title', $pageTitle)

@push('styles')
    <style>
        .voting-form-card { border-radius: 14px; border: 1px solid #e8eaed; }
        .voting-item-card { border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; margin-bottom: 14px; background: #f8fafc; }
        .candidate-image-panel { margin-top: 12px; border: 1px dashed #c7d2fe; border-radius: 14px; padding: 12px; background: #fff; }
        .candidate-image-row { display: grid; grid-template-columns: 1fr 180px; gap: 12px; align-items: center; padding: 10px 0; border-bottom: 1px solid #edf2f7; }
        .candidate-image-row:last-child { border-bottom: 0; }
        .candidate-image-preview { width: 76px; height: 54px; border-radius: 10px; background: #eef2ff; border: 1px solid #dbe4ff; object-fit: cover; }
        .candidate-image-empty { display: flex; align-items: center; justify-content: center; color: #64748b; font-size: 0.72rem; }
        .voting-form-card textarea { resize: none; min-height: 76px; }
        .voting-form-card :focus-visible { outline: 3px solid #a5b4fc; outline-offset: 3px; }
        .answer-row { padding: 14px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; margin-bottom: 12px; }
        .answer-text-row { display: flex; gap: 8px; align-items: center; margin-bottom: 12px; }
        .answer-text-row input { flex: 1; min-width: 0; }
        .answer-image-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .answer-image-controls { flex: 1; min-width: 0; overflow-wrap: anywhere; }
        .answer-image-preview { width: 96px; height: 72px; object-fit: contain; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; }
        .answer-image-name { color: #64748b; font-size: .78rem; margin: 5px 0; }
        .answer-image-input { max-width: 100%; }
        .voting-item-card [hidden] { display: none !important; }
        .voting-mode-help { color: #64748b; margin: 6px 0 14px; font-size: .82rem; }
        @media (max-width: 640px) {
            .candidate-image-row { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid d-flex justify-content-between align-items-start">
            <div>
                <h1 class="mb-1">{{ $pageTitle }}</h1>
                <div class="text-muted" style="font-size:0.82rem;">Pilih kandidat pegawai atau buat pilihan jawaban untuk setiap item voting.</div>
            </div>
            <a href="{{ route('rapat.voting.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
    </div>
@endsection

@section('content')
    @if($errors->any())
        <div class="alert alert-danger" id="votingFormErrors" role="alert" tabindex="-1">
            <ul class="mb-0 pl-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $initialItems = old('items');
        if (!$initialItems && $voting->exists) {
            $initialItems = $voting->items->map(function ($item) {
                return [
                    'judul' => $item->judul,
                    'deskripsi' => $item->deskripsi,
                    'uses_candidates' => $item->uses_candidates ? '1' : '0',
                    'options' => $item->uses_candidates ? [] : $item->candidates->pluck('nama_snapshot')->all(),
                    'option_images' => $item->uses_candidates ? [] : $item->candidates->map(function ($choice) {
                        return ['id' => $choice->image_path ? $choice->id : null, 'url' => $choice->image_url, 'name' => $choice->image_name];
                    })->values()->all(),
                    'candidate_ids' => $item->candidates->pluck('user_id')->map(function ($id) {
                        return (string) $id;
                    })->values()->all(),
                    'candidate_images' => $item->candidates->mapWithKeys(function ($candidate) {
                        return [(string) $candidate->user_id => [
                            'path' => $candidate->image_path,
                            'url' => $candidate->image_url,
                            'name' => $candidate->image_name,
                        ]];
                    })->all(),
                ];
            })->values()->all();
        }
        // Browsers cannot restore a file input after validation, but saved images
        // can be reconstructed from an ID belonging to this voting only.
        if (old('items') && $voting->exists) {
            $savedChoices = $voting->items->flatMap(function ($item) { return $item->candidates; })->keyBy('id');
            foreach ($initialItems as &$initialItem) {
                $initialItem['option_images'] = [];
                foreach (($initialItem['existing_option_image_ids'] ?? []) as $key => $choiceId) {
                    $choice = $savedChoices->get($choiceId);
                    if ($choice && $choice->image_path) {
                        $initialItem['option_images'][$key] = ['id' => $choice->id, 'url' => $choice->image_url, 'name' => $choice->image_name];
                    }
                }
            }
            unset($initialItem);
        }
        if (!$initialItems) {
            $initialItems = [[
                'judul' => '',
                'deskripsi' => '',
                'candidate_ids' => [],
                'candidate_images' => [],
            ]];
        }
        $selectedParticipants = collect(old('participant_ids', $voting->exists ? $voting->participants->pluck('id')->all() : []))
            ->map(function ($id) { return (string) $id; })->all();
    @endphp

    <div class="card voting-form-card">
        <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data" novalidate id="votingEditor">
            @csrf
            @if($formMethod === 'PUT')
                @method('PUT')
            @endif
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label for="votingTitle">Judul Voting</label>
                        <input type="text" id="votingTitle" name="judul" class="form-control" value="{{ old('judul', $voting->judul) }}" required maxlength="255">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="votingStatus">Status</label>
                        <select id="votingStatus" name="status" class="form-control" required>
                            @foreach(['draft' => 'Draft', 'aktif' => 'Aktif', 'selesai' => 'Selesai'] as $value => $label)
                                <option value="{{ $value }}" {{ old('status', $voting->status ?: 'draft') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="votingDescription">Deskripsi</label>
                    <textarea id="votingDescription" name="deskripsi" class="form-control" rows="3">{{ old('deskripsi', $voting->deskripsi) }}</textarea>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="select_all_participants" value="1" id="selectAllParticipants" {{ old('select_all_participants', $voting->select_all_participants) ? 'checked' : '' }}>
                        <label for="selectAllParticipants" class="form-check-label">Pilih semua peserta</label>
                    </div>
                </div>

                <div class="form-group" id="participantGroup">
                    <label for="participantIds">Peserta Voting</label>
                    <select name="participant_ids[]" id="participantIds" class="form-control select2" multiple>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ in_array((string) $user->id, $selectedParticipants, true) ? 'selected' : '' }}>
                                {{ $user->name }}{{ $user->jabatan ? ' - ' . $user->jabatan->nama : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="mb-0">Item Voting</label>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addItemButton">Tambah Item</button>
                </div>

                <div id="itemsContainer"></div>
            </div>
            <div class="card-footer bg-white text-right">
                <button type="submit" class="btn btn-primary" id="saveVoting">Simpan Voting</button>
            </div>
        </form>
    </div>

    <template id="itemTemplate">
        <div class="voting-item-card" data-item-index="__INDEX__">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Item <span class="item-number">1</span></strong>
                <button type="button" class="btn btn-sm btn-outline-danger remove-item-button">Hapus</button>
            </div>
            <div class="form-group">
                <label for="itemTitle__INDEX__">Judul Item</label>
                <input id="itemTitle__INDEX__" type="text" class="form-control" name="items[__INDEX__][judul]" required maxlength="255">
            </div>
            <div class="form-group">
                <label for="itemDescription__INDEX__">Deskripsi Item</label>
                <textarea id="itemDescription__INDEX__" class="form-control" name="items[__INDEX__][deskripsi]" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label for="itemMode__INDEX__">Menggunakan kandidat?</label>
                <select id="itemMode__INDEX__" class="form-control voting-mode" name="items[__INDEX__][uses_candidates]" aria-controls="candidateGroup__INDEX__ answerGroup__INDEX__">
                    <option value="1">Ya, pilih kandidat dari pegawai</option>
                    <option value="0">Tidak, gunakan pilihan jawaban</option>
                </select>
            </div>
            <div class="form-group mb-0 candidate-group" id="candidateGroup__INDEX__">
                <label for="candidateSelect__INDEX__">Kandidat</label>
                <select id="candidateSelect__INDEX__" class="form-control select2 candidate-select" name="items[__INDEX__][candidate_ids][]" multiple required>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Minimal 2 kandidat. Cari nama dengan ketik, tidak perlu scroll manual.</small>
                <div class="candidate-image-panel">
                    <div class="font-weight-bold mb-1" style="font-size:0.84rem;">Gambar Kandidat</div>
                    <div class="text-muted mb-2" style="font-size:0.76rem;">Opsional. JPG, PNG, atau WebP maksimal 3 MB per gambar. Jika lebih dari satu kandidat memiliki gambar, halaman voting akan menampilkannya sebagai perbandingan.</div>
                    <div class="candidate-image-inputs"></div>
                </div>
            </div>
            <div class="answer-group" id="answerGroup__INDEX__" hidden>
                <strong>Pilihan jawaban</strong>
                <p class="voting-mode-help">Isi 2–20 pilihan. Setiap pilihan boleh dilengkapi gambar JPG, PNG, atau WebP maksimal 3 MB. Peserta memilih satu jawaban pada setiap item.</p>
                <div class="answer-rows"></div>
                <button type="button" class="btn btn-sm btn-outline-primary add-answer">Tambah pilihan jawaban</button>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script>
        const initialItems = @json($initialItems);
        const usersById = @json($users->mapWithKeys(function ($user) {
            return [(string) $user->id => $user->name . ($user->jabatan ? ' - ' . $user->jabatan->nama : '')];
        }));
        let itemIndex = 0;

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function collectCurrentCandidateImages($item) {
            const current = {};
            $item.find('.candidate-image-row').each(function () {
                const userId = String($(this).data('candidate-id'));
                current[userId] = {
                    path: $(this).find('input[type="hidden"]').val() || '',
                    url: $(this).find('img').attr('src') || '',
                    name: $(this).find('.candidate-image-name').text() || '',
                };
            });
            return current;
        }

        function renderCandidateImageInputs($item, index, images = {}) {
            const $select = $item.find('.candidate-select');
            const selectedIds = ($select.val() || []).map(String);
            const existing = Object.assign({}, images || {}, collectCurrentCandidateImages($item));
            const $target = $item.find('.candidate-image-inputs');

            if (!selectedIds.length) {
                $target.html('<div class="text-muted" style="font-size:0.78rem;">Pilih kandidat terlebih dahulu.</div>');
                return;
            }

            const html = selectedIds.map(function (userId) {
                const image = existing[userId] || {};
                const preview = image.url
                    ? '<img src="' + escapeHtml(image.url) + '" class="candidate-image-preview" alt="Gambar kandidat">'
                    : '<div class="candidate-image-preview candidate-image-empty">Belum ada</div>';

                return '<div class="candidate-image-row" data-candidate-id="' + escapeHtml(userId) + '">'
                    + '<div class="d-flex align-items-center" style="gap:10px;">'
                    + preview
                    + '<div>'
                    + '<div class="font-weight-bold" style="font-size:0.86rem;">' + escapeHtml(usersById[userId] || 'Kandidat') + '</div>'
                    + '<div class="candidate-image-name text-muted" style="font-size:0.74rem;">' + escapeHtml(image.name || 'Upload gambar opsional') + '</div>'
                    + '</div>'
                    + '</div>'
                    + '<div>'
                    + '<input type="hidden" name="items[' + index + '][existing_candidate_images][' + userId + ']" value="' + escapeHtml(image.path || '') + '">'
                    + '<input type="file" aria-label="Foto kandidat ' + escapeHtml(usersById[userId] || 'Kandidat') + '" name="items[' + index + '][candidate_images][' + userId + ']" class="form-control-file" accept="image/png,image/jpeg,image/webp">'
                    + '</div>'
                    + '</div>';
            }).join('');

            $target.html(html);
        }

        function renderItem(itemData = null) {
            const currentIndex = itemIndex;
            const template = $('#itemTemplate').html().replace(/__INDEX__/g, currentIndex);
            const $item = $(template);
            $item.find('.item-number').text(currentIndex + 1);
            if (itemData) {
                $item.find('input[name="items[' + currentIndex + '][judul]"]').val(itemData.judul || '');
                $item.find('textarea[name="items[' + currentIndex + '][deskripsi]"]').val(itemData.deskripsi || '');
            }
            $('#itemsContainer').append($item);
            const $select = $item.find('.candidate-select');
            $select.select2({ theme: 'bootstrap4', width: '100%' });
            if (itemData && Array.isArray(itemData.candidate_ids)) {
                $select.val(itemData.candidate_ids).trigger('change');
            }
            renderCandidateImageInputs($item, currentIndex, itemData ? itemData.candidate_images : {});
            const options = itemData && itemData.options && Object.keys(itemData.options).length ? itemData.options : ['Setuju', 'Tidak setuju'];
            Object.entries(options).forEach(function ([key, label]) {
                appendAnswer($item, label, itemData && itemData.option_images ? itemData.option_images[key] : {}, Number(key));
            });
            $item.find('.voting-mode').val(itemData && String(itemData.uses_candidates) === '0' ? '0' : '1');
            toggleMode($item);
            $item.find('.voting-mode').on('change', function () { toggleMode($item); });
            $select.on('change', function () {
                renderCandidateImageInputs($item, currentIndex);
            });
            itemIndex++;
            renumberItems();
        }

        function appendAnswer($item, label = '', image = {}, key = null) {
            if ($item.find('.answer-row').length >= 20) return;
            const index = $item.attr('data-item-index');
            const next = $item.data('next-answer-key') || 0;
            const answerKey = Number.isInteger(key) && key >= 0 ? key : next;
            $item.data('next-answer-key', Math.max(next, answerKey + 1));
            image = image || {};
            const prefix = 'items[' + index + ']';
            const $row = $('<div class="answer-row"><div class="answer-text-row"><input type="text" class="form-control answer-text" maxlength="255" required><button type="button" class="btn btn-outline-danger remove-answer" aria-label="Hapus pilihan jawaban">Hapus</button></div><div class="answer-image-row"><img class="answer-image-preview" alt="Pratinjau gambar pilihan" hidden><div class="answer-image-controls"><label class="answer-image-label"></label><input type="hidden" class="existing-answer-image"><input type="file" class="form-control-file answer-image-input" accept="image/png,image/jpeg,image/webp"><div class="answer-image-name"></div><button type="button" class="btn btn-sm btn-outline-secondary remove-answer-image" hidden>Hapus gambar</button></div></div></div>');
            $row.attr('data-answer-key', answerKey);
            $row.data('saved-image', image);
            $row.find('.answer-text').attr('name', prefix + '[options][' + answerKey + ']').val(label);
            $row.find('.existing-answer-image').attr('name', prefix + '[existing_option_image_ids][' + answerKey + ']').val(image.id || '');
            $row.find('.answer-image-input').attr({ name: prefix + '[option_images][' + answerKey + ']', id: 'answerImage' + index + '_' + answerKey });
            $row.find('.answer-image-label').attr('for', 'answerImage' + index + '_' + answerKey);
            if (image.url) {
                $row.find('.answer-image-preview').attr('src', image.url).prop('hidden', false);
                $row.find('.answer-image-name').text(image.name || 'Gambar tersimpan');
                $row.find('.remove-answer-image').prop('hidden', false);
            }
            $item.find('.answer-rows').append($row);
            renumberAnswers($item);
        }

        function renumberAnswers($item) {
            const count = $item.find('.answer-row').length;
            $item.find('.answer-row').each(function (index) {
                $(this).find('.answer-text').attr('aria-label', 'Pilihan jawaban ' + (index + 1));
                $(this).find('.answer-image-label').text('Gambar pilihan ' + (index + 1) + ' (opsional)');
                $(this).find('.remove-answer').prop('disabled', count <= 2);
            });
            $item.find('.add-answer').prop('disabled', count >= 20);
        }

        function toggleMode($item) {
            const useCandidates = $item.find('.voting-mode').val() === '1';
            $item.find('.candidate-group').prop('hidden', !useCandidates).find(':input').prop('disabled', !useCandidates);
            $item.find('.answer-group').prop('hidden', useCandidates).find(':input').prop('disabled', useCandidates);
            if (!useCandidates) renumberAnswers($item);
        }

        function renumberItems() {
            const count = $('#itemsContainer .voting-item-card').length;
            $('#itemsContainer .voting-item-card').each(function (index) {
                $(this).find('.item-number').text(index + 1);
                $(this).find('.remove-item-button').prop('disabled', count <= 1);
            });
        }

        function toggleParticipantGroup() {
            $('#participantGroup').toggle(!$('#selectAllParticipants').is(':checked'));
            $('#participantIds').prop('disabled', $('#selectAllParticipants').is(':checked'));
        }

        $(function () {
            $('#participantIds').select2({ theme: 'bootstrap4', width: '100%' });
            toggleParticipantGroup();
            $('#selectAllParticipants').on('change', toggleParticipantGroup);

            Object.values(initialItems).forEach(function (item) { renderItem(item); });

            $('#addItemButton').on('click', function () {
                renderItem();
            });

            $('#itemsContainer').on('click', '.remove-item-button', function () {
                if ($('#itemsContainer .voting-item-card').length === 1) {
                    return;
                }
                $(this).closest('.voting-item-card').remove();
                renumberItems();
            });
            $('#itemsContainer').on('click', '.add-answer', function () {
                const $item = $(this).closest('.voting-item-card');
                appendAnswer($item);
                $item.find('.answer-row:last .answer-text').trigger('focus');
            }).on('click', '.remove-answer', function () {
                const $item = $(this).closest('.voting-item-card');
                if ($item.find('.answer-row').length <= 2) return;
                const $row = $(this).closest('.answer-row');
                revokeAnswerPreview($row);
                $row.remove();
                renumberAnswers($item);
            }).on('change', '.answer-image-input', function () {
                const $row = $(this).closest('.answer-row');
                revokeAnswerPreview($row);
                const file = this.files[0];
                restoreAnswerPreview($row);
                if (!file) return;
                if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 3 * 1024 * 1024) {
                    this.value = '';
                    $row.find('.answer-image-name').text('Pilih gambar JPG, PNG, atau WebP maksimal 3 MB.').attr('role', 'alert');
                    return;
                }
                const url = URL.createObjectURL(file);
                $row.data('preview-url', url);
                $row.find('.answer-image-preview').attr('src', url).prop('hidden', false);
                $row.find('.answer-image-name').text(file.name).removeAttr('role');
                $row.find('.remove-answer-image').prop('hidden', false);
            }).on('click', '.remove-answer-image', function () {
                const $row = $(this).closest('.answer-row');
                revokeAnswerPreview($row);
                $row.find('.answer-image-input, .existing-answer-image').val('');
                $row.data('saved-image', {});
                $row.find('.answer-image-preview').removeAttr('src').prop('hidden', true);
                $row.find('.answer-image-name').text('');
                $(this).prop('hidden', true);
            });
            $('#itemsContainer').on('change', '.candidate-image-row input[type="file"]', function () {
                const file = this.files[0];
                if (file && file.size > 3 * 1024 * 1024) {
                    this.value = '';
                    $(this).closest('.candidate-image-row').find('.candidate-image-name').text('Ukuran gambar kandidat maksimal 3 MB.').attr('role', 'alert');
                }
            });
            $('#votingEditor').on('submit', function () {
                $('#saveVoting').prop('disabled', true).attr('aria-busy', 'true');
            });
            @if($errors->any())
                const errorFields = @json(array_keys($errors->toArray()));
                let firstInvalid = null;
                errorFields.forEach(function (path) {
                    const parts = path.split('.');
                    const name = parts.shift() + parts.map(part => '[' + part + ']').join('');
                    const arrayName = name.replace(/\[\d+\]$/, '[]');
                    const field = document.getElementsByName(name)[0] || document.getElementsByName(name + '[]')[0] || document.getElementsByName(arrayName)[0];
                    if (field) { field.setAttribute('aria-invalid', 'true'); field.setAttribute('aria-describedby', 'votingFormErrors'); field.classList.add('is-invalid'); firstInvalid = firstInvalid || field; }
                });
                if (firstInvalid) firstInvalid.focus();
            @endif
        });

        function revokeAnswerPreview($row) {
            const url = $row.data('preview-url');
            if (url) URL.revokeObjectURL(url);
            $row.removeData('preview-url');
        }
        function restoreAnswerPreview($row) {
            const image = $row.data('saved-image') || {};
            $row.find('.answer-image-preview').attr('src', image.url || '').prop('hidden', !image.url);
            $row.find('.answer-image-name').text(image.name || '');
            $row.find('.remove-answer-image').prop('hidden', !image.url);
        }
        window.addEventListener('pagehide', function () {
            $('.answer-row').each(function () { revokeAnswerPreview($(this)); });
        });
    </script>
@endpush
