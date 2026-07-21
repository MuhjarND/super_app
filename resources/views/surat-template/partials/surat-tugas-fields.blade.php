@php
    $fieldValues = $fieldValues ?? [];
    $modalId = $modalId ?? '';
    $selectedPetugas = array_map('strval', (array) old('fields.petugas_ids', $fieldValues['petugas_ids'] ?? []));
    $selectedSigner = (string) old('fields.penanda_tangan_id', $fieldValues['penanda_tangan_id'] ?? data_get($fieldValues, 'penanda_tangan.id'));
    $selectedParaf = (string) old('fields.paraf_user_id', $fieldValues['paraf_user_id'] ?? data_get($fieldValues, 'paraf.id'));
@endphp

<div class="row surat-tugas-form-grid">
    <div class="col-md-6 form-group">
        <label>Dalam Rangka <span class="text-danger">*</span></label>
        <textarea name="fields[dalam_rangka]" class="form-control" rows="3" required>{{ old('fields.dalam_rangka', $fieldValues['dalam_rangka'] ?? '') }}</textarea>
    </div>
    <div class="col-md-6 form-group">
        <label>Untuk <span class="text-danger">*</span></label>
        <textarea name="fields[untuk_tugas]" class="form-control" rows="3" required placeholder="Uraian tugas yang muncul pada bagian Untuk">{{ old('fields.untuk_tugas', $fieldValues['untuk_tugas'] ?? '') }}</textarea>
    </div>

    <div class="col-12 form-group">
        <label>Tambahan Dasar Hukum</label>
        <textarea name="fields[tambahan_dasar_hukum]" class="form-control surat-tugas-legal-input" rows="3" placeholder="Satu baris untuk satu dasar hukum">{{ old('fields.tambahan_dasar_hukum', $fieldValues['tambahan_dasar_hukum'] ?? '') }}</textarea>
    </div>

    <div class="col-12 form-group">
        <label>Daftar Petugas <span class="text-danger">*</span></label>
        <select name="fields[petugas_ids][]" class="form-control select2" multiple required data-dropdown-parent="#{{ $modalId }}">
            @foreach($templateUsers as $user)
                <option value="{{ $user->id }}" {{ in_array((string) $user->id, $selectedPetugas, true) ? 'selected' : '' }}>
                    {{ $user->name }}{{ optional($user->jabatan)->nama ? ' - ' . optional($user->jabatan)->nama : '' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 form-group">
        <label>Tanggal Mulai <span class="text-danger">*</span></label>
        <input type="date" name="fields[tanggal_mulai]" class="form-control" value="{{ old('fields.tanggal_mulai', $fieldValues['tanggal_mulai'] ?? '') }}" required>
    </div>
    <div class="col-md-6 form-group">
        <label>Tanggal Selesai <span class="text-danger">*</span></label>
        <input type="date" name="fields[tanggal_selesai]" class="form-control" value="{{ old('fields.tanggal_selesai', $fieldValues['tanggal_selesai'] ?? '') }}" required>
    </div>

    <div class="col-md-6 form-group">
        <label>Penanda Tangan <span class="text-danger">*</span></label>
        <select name="fields[penanda_tangan_id]" class="form-control" required>
            <option value="">-- Pilih --</option>
            @foreach($templateSignerUsers as $user)
                <option value="{{ $user->id }}" {{ $selectedSigner === (string) $user->id ? 'selected' : '' }}>
                    {{ $user->name }}{{ optional($user->jabatan)->nama ? ' - ' . optional($user->jabatan)->nama : '' }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 form-group">
        <label>Paraf <span class="text-danger">*</span></label>
        <select name="fields[paraf_user_id]" class="form-control" required>
            <option value="">-- Pilih --</option>
            @foreach($templateUsers as $user)
                <option value="{{ $user->id }}" {{ $selectedParaf === (string) $user->id ? 'selected' : '' }}>
                    {{ $user->name }}{{ optional($user->jabatan)->nama ? ' - ' . optional($user->jabatan)->nama : '' }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6 form-group mb-md-0">
        <label>Jabatan Plh/Plt</label>
        <input type="text" name="fields[jabatan_plh]" class="form-control" value="{{ old('fields.jabatan_plh', $fieldValues['jabatan_plh'] ?? '') }}" placeholder="Opsional">
    </div>
    <div class="col-md-6 form-group mb-0">
        <label>Lokasi <span class="text-danger">*</span></label>
        <input type="text" name="fields[lokasi]" class="form-control" value="{{ old('fields.lokasi', $fieldValues['lokasi'] ?? '') }}" required>
    </div>
</div>
