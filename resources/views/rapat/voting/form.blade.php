@extends('layouts.app')

@section('title', $pageTitle)

@push('styles')
    <style>
        .voting-form-card { border-radius: 16px; border: 1px solid #e5e7eb; }
        .voting-item-card { border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; margin-bottom: 14px; background: #f8fafc; }
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
                ];
            })->values()->all();
        }
        if (!$initialItems) {
            $initialItems = [[
                'judul' => '',
                'deskripsi' => '',
                'candidate_ids' => [],
            ]];
        }
        $selectedParticipants = collect(old('participant_ids', $voting->exists ? $voting->participants->pluck('id')->all() : []))
            ->map(function ($id) { return (string) $id; })->all();
    @endphp

    <div class="card voting-form-card">
        <form action="{{ $formAction }}" method="POST">
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
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script>
        const initialItems = @json($initialItems);
        let itemIndex = 0;

        function renderItem(itemData = null) {
            const template = $('#itemTemplate').html().replace(/__INDEX__/g, itemIndex);
            const $item = $(template);
            $item.find('.item-number').text(itemIndex + 1);
            if (itemData) {
                $item.find('input[name="items[' + itemIndex + '][judul]"]').val(itemData.judul || '');
                $item.find('textarea[name="items[' + itemIndex + '][deskripsi]"]').val(itemData.deskripsi || '');
            }
            $('#itemsContainer').append($item);
            const $select = $item.find('.candidate-select');
            $select.select2({ theme: 'bootstrap4', width: '100%' });
            if (itemData && Array.isArray(itemData.candidate_ids)) {
                $select.val(itemData.candidate_ids).trigger('change');
            }
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
