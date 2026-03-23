<div class="form-row">
    <div class="form-group col-md-3"><label>Kode</label><input type="text" name="code" class="form-control" value="{{ old('code', optional($item)->code) }}" required></div>
    <div class="form-group col-md-6"><label>Nama</label><input type="text" name="name" class="form-control" value="{{ old('name', optional($item)->name) }}" required></div>
    <div class="form-group col-md-3"><label>Status</label><select name="status" class="form-control"><option value="active" {{ old('status', optional($item)->status ?: 'active') === 'active' ? 'selected' : '' }}>Aktif</option><option value="inactive" {{ old('status', optional($item)->status) === 'inactive' ? 'selected' : '' }}>Nonaktif</option></select></div>
</div>
<div class="form-group"><label>Deskripsi</label><textarea name="description" class="form-control" rows="3">{{ old('description', optional($item)->description) }}</textarea></div>
<div class="form-row">
    <div class="form-group col-md-4"><label>Maks Hari</label><input type="number" min="1" name="max_days" class="form-control" value="{{ old('max_days', optional($item)->max_days) }}"></div>
    <div class="form-group col-md-4"><label>Maks Bulan</label><input type="number" min="1" name="max_months" class="form-control" value="{{ old('max_months', optional($item)->max_months) }}"></div>
    <div class="form-group col-md-4"><label>Masa Kerja Minimum (th)</label><input type="number" min="0" name="service_years_required" class="form-control" value="{{ old('service_years_required', optional($item)->service_years_required ?: 0) }}"></div>
</div>
<div class="form-row">
    <div class="form-group col-md-3"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="requires_balance" id="requiresBalance{{ optional($item)->id ?: 'new' }}" {{ old('requires_balance', optional($item)->requires_balance) ? 'checked' : '' }}><label class="form-check-label" for="requiresBalance{{ optional($item)->id ?: 'new' }}">Pakai Saldo</label></div></div>
    <div class="form-group col-md-3"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="requires_document" id="requiresDocument{{ optional($item)->id ?: 'new' }}" {{ old('requires_document', optional($item)->requires_document) ? 'checked' : '' }}><label class="form-check-label" for="requiresDocument{{ optional($item)->id ?: 'new' }}">Wajib Dokumen</label></div></div>
    <div class="form-group col-md-3"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="requires_verification" id="requiresVerification{{ optional($item)->id ?: 'new' }}" {{ old('requires_verification', optional($item)->requires_verification) ? 'checked' : '' }}><label class="form-check-label" for="requiresVerification{{ optional($item)->id ?: 'new' }}">Perlu Verifikasi</label></div></div>
    <div class="form-group col-md-3"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="requires_ppk_approval" id="requiresPpk{{ optional($item)->id ?: 'new' }}" {{ old('requires_ppk_approval', optional($item)->requires_ppk_approval) ? 'checked' : '' }}><label class="form-check-label" for="requiresPpk{{ optional($item)->id ?: 'new' }}">Perlu PPK</label></div></div>
</div>
