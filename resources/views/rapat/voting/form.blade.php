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
                <div class="text-muted" style="font-size:0.82rem;">Voting multi-item dengan kandidat dari data user yang ada.</div>
            </div>
            <a href="{{ route('rapat.voting.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
        </div>
    </div>
@endsection

@section('content')
    @if($errors->any())
        <div class="alert alert-danger">
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
        <form action="{{ $formAction }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if($formMethod === 'PUT')
                @method('PUT')
            @endif
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Judul Voting</label>
                        <input type="text" name="judul" class="form-control" value="{{ old('judul', $voting->judul) }}" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Status</label>
                        <select name="status" class="form-control" required>
                            @foreach(['draft' => 'Draft', 'aktif' => 'Aktif', 'selesai' => 'Selesai'] as $value => $label)
                                <option value="{{ $value }}" {{ old('status', $voting->status ?: 'draft') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="3">{{ old('deskripsi', $voting->deskripsi) }}</textarea>
                </div>

                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="select_all_participants" value="1" id="selectAllParticipants" {{ old('select_all_participants', $voting->select_all_participants) ? 'checked' : '' }}>
                        <label for="selectAllParticipants" class="form-check-label">Pilih semua peserta</label>
                    </div>
                </div>

                <div class="form-group" id="participantGroup">
                    <label>Peserta Voting</label>
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
                <button type="submit" class="btn btn-primary">Simpan Voting</button>
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
                <label>Judul Item</label>
                <input type="text" class="form-control" name="items[__INDEX__][judul]" required>
            </div>
            <div class="form-group">
                <label>Deskripsi Item</label>
                <textarea class="form-control" name="items[__INDEX__][deskripsi]" rows="2"></textarea>
            </div>
            <div class="form-group mb-0">
                <label>Kandidat</label>
                <select class="form-control select2 candidate-select" name="items[__INDEX__][candidate_ids][]" multiple required>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Minimal 2 kandidat. Cari nama dengan ketik, tidak perlu scroll manual.</small>
                <div class="candidate-image-panel">
                    <div class="font-weight-bold mb-1" style="font-size:0.84rem;">Gambar Kandidat</div>
                    <div class="text-muted mb-2" style="font-size:0.76rem;">Opsional. Jika lebih dari satu kandidat memiliki gambar, halaman voting akan menampilkannya sebagai perbandingan.</div>
                    <div class="candidate-image-inputs"></div>
                </div>
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
                    + '<input type="file" name="items[' + index + '][candidate_images][' + userId + ']" class="form-control-file" accept="image/png,image/jpeg,image/webp">'
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
            $select.on('change', function () {
                renderCandidateImageInputs($item, currentIndex);
            });
            itemIndex++;
        }

        function toggleParticipantGroup() {
            $('#participantGroup').toggle(!$('#selectAllParticipants').is(':checked'));
        }

        $(function () {
            $('#participantIds').select2({ theme: 'bootstrap4', width: '100%' });
            toggleParticipantGroup();
            $('#selectAllParticipants').on('change', toggleParticipantGroup);

            initialItems.forEach(function (item) { renderItem(item); });

            $('#addItemButton').on('click', function () {
                renderItem();
            });

            $('#itemsContainer').on('click', '.remove-item-button', function () {
                if ($('#itemsContainer .voting-item-card').length === 1) {
                    return;
                }
                $(this).closest('.voting-item-card').remove();
            });
        });
    </script>
@endpush
