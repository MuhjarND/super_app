@php
    $item = $item ?? null;
    $decodedSchema = data_get($item, 'field_schema', []);
    if (!is_array($decodedSchema)) {
        $decodedSchema = [];
    }

    $builderRows = [];
    if (old('field_builder.name')) {
        $names = old('field_builder.name', []);
        $labels = old('field_builder.label', []);
        $types = old('field_builder.type', []);
        $requireds = old('field_builder.required', []);

        foreach ($names as $index => $name) {
            $builderRows[] = [
                'name' => $name,
                'label' => $labels[$index] ?? '',
                'type' => $types[$index] ?? 'text',
                'required' => !empty($requireds[$index]),
            ];
        }
    } else {
        $builderRows = collect($decodedSchema)->filter(function ($row) {
            return is_array($row) && !empty($row['name']) && !empty($row['label']);
        })->map(function ($row) {
            return [
                'name' => $row['name'],
                'label' => $row['label'],
                'type' => $row['type'] ?? 'text',
                'required' => !empty($row['required']),
            ];
        })->values()->all();
    }

    if (empty($builderRows)) {
        $builderRows[] = ['name' => 'nomor_surat', 'label' => 'Nomor Surat', 'type' => 'text', 'required' => true];
    }

    $fieldSchemaJson = old('field_schema', json_encode($builderRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
@endphp
<div class="template-schema-editor">
    <div class="row">
        <div class="col-md-6 form-group">
            <label>Nama Template</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', data_get($item, 'name')) }}" required>
        </div>
        <div class="col-md-6 form-group">
            <label>Slug Template</label>
            <input type="text" name="slug" class="form-control" value="{{ old('slug', data_get($item, 'slug')) }}" required>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 form-group">
            <label>Kategori</label>
            <input type="text" name="category" class="form-control" value="{{ old('category', data_get($item, 'category')) }}" required>
            <small class="text-muted">Contoh: Penugasan, Keterangan, Instruksi, Undangan Internal.</small>
        </div>
        <div class="col-md-6 form-group">
            <label>Status</label>
            <select name="status" class="form-control" required>
                @php $currentStatus = old('status', data_get($item, 'status', 'active')); @endphp
                <option value="active" {{ $currentStatus === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="draft" {{ $currentStatus === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="inactive" {{ $currentStatus === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label>Deskripsi</label>
        <textarea name="description" class="form-control" rows="3">{{ old('description', data_get($item, 'description')) }}</textarea>
    </div>

    <div class="card border-0 bg-light mb-3">
        <div class="card-body pb-2">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <label class="mb-1 d-block">Field Builder</label>
                    <small class="text-muted">Susun field yang harus diisi pengguna saat memakai template surat.</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" data-add-template-field>
                    <i class="fas fa-plus"></i> Tambah Field
                </button>
            </div>

            <div data-template-field-list>
                @foreach($builderRows as $row)
                    <div class="row align-items-end mb-2 template-field-row">
                        <div class="col-md-3 form-group mb-2">
                            <label class="small text-muted">Label Field</label>
                            <input type="text" class="form-control form-control-sm" name="field_builder[label][]" value="{{ $row['label'] }}" placeholder="Contoh: Nama Pegawai">
                        </div>
                        <div class="col-md-3 form-group mb-2">
                            <label class="small text-muted">Nama Sistem</label>
                            <input type="text" class="form-control form-control-sm" name="field_builder[name][]" value="{{ $row['name'] }}" placeholder="contoh: nama_pegawai">
                        </div>
                        <div class="col-md-3 form-group mb-2">
                            <label class="small text-muted">Tipe</label>
                            <select class="form-control form-control-sm" name="field_builder[type][]">
                                <option value="text" {{ ($row['type'] ?? 'text') === 'text' ? 'selected' : '' }}>Text</option>
                                <option value="textarea" {{ ($row['type'] ?? '') === 'textarea' ? 'selected' : '' }}>Textarea</option>
                                <option value="date" {{ ($row['type'] ?? '') === 'date' ? 'selected' : '' }}>Tanggal</option>
                                <option value="user_select" {{ ($row['type'] ?? '') === 'user_select' ? 'selected' : '' }}>Pilih 1 User</option>
                                <option value="user_multi" {{ ($row['type'] ?? '') === 'user_multi' ? 'selected' : '' }}>Pilih Banyak User</option>
                            </select>
                        </div>
                        <div class="col-md-2 form-group mb-2">
                            <label class="small text-muted d-block">Wajib Isi</label>
                            <label class="mb-0 mt-1">
                                <input type="checkbox" name="field_builder[required][]" value="1" {{ !empty($row['required']) ? 'checked' : '' }}> Ya
                            </label>
                        </div>
                        <div class="col-md-1 form-group mb-2 text-right">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-remove-template-field><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="form-group">
        <label>Body Template</label>
        <textarea name="template_body" class="form-control" rows="10" required>{{ old('template_body', data_get($item, 'template_body')) }}</textarea>
        <small class="text-muted">Gunakan placeholder seperti <code>@{{nomor_surat}}</code>, <code>@{{tanggal_surat}}</code>, dan placeholder lain sesuai field builder.</small>
    </div>

    <div class="form-group">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="mb-0">JSON Field Schema</label>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="collapse" data-target="#advancedJsonFieldSchema{{ data_get($item, 'id', 'new') }}">Tampilkan JSON</button>
        </div>
        <div class="collapse" id="advancedJsonFieldSchema{{ data_get($item, 'id', 'new') }}">
            <textarea class="form-control font-monospace template-field-schema-preview" rows="9" readonly>{{ $fieldSchemaJson }}</textarea>
            <small class="text-muted">Schema ini dibentuk otomatis dari Field Builder. Ubah manual hanya jika diperlukan.</small>
        </div>
        <textarea name="field_schema" class="form-control font-monospace template-field-schema-output d-none" rows="9" required>{{ $fieldSchemaJson }}</textarea>
    </div>

    <div class="form-group mb-0">
        <label>Contoh File Template</label>
        <input type="file" name="sample_file" class="form-control-file">
        @if(data_get($item, 'sample_file_path'))
            <small class="d-block text-muted mt-1">File contoh saat ini sudah tersimpan.</small>
        @else
            <small class="d-block text-muted mt-1">Opsional. Format: PDF, DOC, DOCX, JPG, JPEG, PNG.</small>
        @endif
    </div>
</div>
