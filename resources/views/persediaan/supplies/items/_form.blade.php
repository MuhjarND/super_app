@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $fieldValue = function ($field, $default = null) use ($item, $isEdit) {
        return old($field, $isEdit && $item ? $item->{$field} : $default);
    };
@endphp

<div class="row">
    <div class="col-md-3 mb-3">
        <label>Kode</label>
        <input type="text" name="code" class="form-control" value="{{ $fieldValue('code') }}" placeholder="ATK-001">
    </div>
    <div class="col-md-5 mb-3">
        <label>Nama Barang <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="{{ $fieldValue('name') }}" required placeholder="Kertas HVS A4">
    </div>
    <div class="col-md-2 mb-3">
        <label>Satuan <span class="text-danger">*</span></label>
        <input type="text" name="unit" class="form-control" value="{{ $fieldValue('unit', 'Pcs') }}" required>
    </div>
    <div class="col-md-2 mb-3">
        <label>Stok <span class="text-danger">*</span></label>
        <input type="number" name="stock" min="0" class="form-control" value="{{ $fieldValue('stock', 0) }}" required>
    </div>
    <div class="col-md-3 mb-3">
        <label>Minimum</label>
        <input type="number" name="minimum_stock" min="0" class="form-control" value="{{ $fieldValue('minimum_stock', 0) }}">
    </div>
    <div class="col-md-5 mb-3">
        <label>Catatan</label>
        <input type="text" name="description" class="form-control" value="{{ $fieldValue('description') }}" placeholder="Opsional">
    </div>
    <div class="col-md-3 mb-3">
        <label>Gambar</label>
        <input type="file" name="image" class="form-control-file" accept="image/*">
        @if($isEdit && $item && $item->image_url)
            <div class="mt-2">
                <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="supply-image-thumb">
            </div>
        @endif
    </div>
    <div class="col-md-1 mb-3 d-flex align-items-end">
        <div class="custom-control custom-switch mb-2">
            <input type="checkbox" class="custom-control-input" id="is_active_{{ $isEdit && $item ? $item->id : 'create' }}" name="is_active" {{ $fieldValue('is_active', true) ? 'checked' : '' }}>
            <label class="custom-control-label" for="is_active_{{ $isEdit && $item ? $item->id : 'create' }}">Aktif</label>
        </div>
    </div>
</div>
