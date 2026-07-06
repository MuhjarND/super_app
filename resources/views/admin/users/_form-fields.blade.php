@php
    $isEdit = $mode === 'edit';
    $selectedRoles = $isEdit
        ? $user->roles->pluck('id')->map(fn ($id) => (string) $id)->all()
        : [];
    $activeDelegation = $isEdit ? $user->activeJabatanDelegations->first() : null;
@endphp

<div class="user-form-grid">
    <section class="user-form-section user-form-section-photo">
        <div class="user-form-section-title">
            <i class="fas fa-id-badge"></i>
            <span>Identitas</span>
        </div>
        <div class="user-photo-field">
            <div class="user-photo-preview">
                @if($isEdit && $user->profile_photo_path)
                    <img src="{{ asset('storage/' . $user->profile_photo_path) }}" alt="{{ $user->name }}">
                @else
                    <span>{{ strtoupper(substr($user->name ?: 'U', 0, 1)) }}</span>
                @endif
            </div>
            <div class="user-photo-control">
                <label>Foto User</label>
                <input type="file" name="profile_photo" class="form-control-file" accept="image/png,image/jpeg,image/webp">
                @if($isEdit && $user->profile_photo_path)
                    <div class="custom-control custom-checkbox mt-2">
                        <input type="checkbox" name="remove_photo" value="1" class="custom-control-input" id="removePhoto{{ $user->id }}">
                        <label class="custom-control-label" for="removePhoto{{ $user->id }}">Hapus foto</label>
                    </div>
                @endif
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 form-group">
                <label>Nama</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>
            <div class="col-md-6 form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="col-md-6 form-group">
                <label>Password {{ $isEdit ? 'Baru' : '' }}</label>
                <input type="password" name="password" class="form-control" {{ $isEdit ? '' : 'required' }}>
            </div>
            <div class="col-md-6 form-group">
                <label>No. HP / WA</label>
                <input type="text" name="no_hp" class="form-control" value="{{ old('no_hp', $user->no_hp) }}">
            </div>
            <div class="col-md-6 form-group mb-0">
                <label>NIP</label>
                <input type="text" name="nip" class="form-control" value="{{ old('nip', $user->nip) }}">
            </div>
            <div class="col-md-6 form-group mb-0">
                <label>Hirarki</label>
                <input type="number" name="hirarki" class="form-control" value="{{ old('hirarki', $user->hirarki ?? 999) }}" min="1">
            </div>
        </div>
    </section>

    <section class="user-form-section">
        <div class="user-form-section-title">
            <i class="fas fa-sitemap"></i>
            <span>Organisasi</span>
        </div>
        <div class="row">
            <div class="col-md-12 form-group">
                <label>Role</label>
                <select name="role_ids[]" class="form-control select2" multiple required>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ in_array((string) $role->id, old('role_ids', $selectedRoles), true) ? 'selected' : '' }}>
                            {{ $role->display_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 form-group">
                <label>Unit Kerja</label>
                <select name="unit_id" class="form-control">
                    <option value="">-- Pilih Unit --</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ (string) old('unit_id', $user->unit_id) === (string) $unit->id ? 'selected' : '' }}>
                            {{ $unit->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 form-group">
                <label>Bidang</label>
                <select name="bidang_id" class="form-control">
                    <option value="">-- Pilih Bidang --</option>
                    @foreach($bidangs as $bidang)
                        <option value="{{ $bidang->id }}" {{ (string) old('bidang_id', $user->bidang_id) === (string) $bidang->id ? 'selected' : '' }}>
                            {{ $bidang->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 form-group">
                <label>Jabatan</label>
                <select name="jabatan_id" class="form-control">
                    <option value="">-- Pilih Jabatan --</option>
                    @foreach($jabatans as $jabatan)
                        <option value="{{ $jabatan->id }}" {{ (string) old('jabatan_id', $user->jabatan_id) === (string) $jabatan->id ? 'selected' : '' }}>
                            {{ $jabatan->nama }}{{ $jabatan->unit ? ' - ' . $jabatan->unit->nama : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 form-group">
                <label>Jabatan Keterangan</label>
                <input type="text" name="jabatan_keterangan" class="form-control" value="{{ old('jabatan_keterangan', $user->jabatan_keterangan) }}">
            </div>
        </div>
    </section>

    <section class="user-form-section">
        <div class="user-form-section-title">
            <i class="fas fa-user-shield"></i>
            <span>Delegasi PLH/PLT</span>
        </div>
        <div class="row">
            <div class="col-md-4 form-group mb-md-0">
                <label>Delegasi Role</label>
                <select name="delegasi_tipe" class="form-control">
                    <option value="">Tidak Ada</option>
                    <option value="plh" {{ old('delegasi_tipe', optional($activeDelegation)->delegation_type) === 'plh' ? 'selected' : '' }}>PLH</option>
                    <option value="plt" {{ old('delegasi_tipe', optional($activeDelegation)->delegation_type) === 'plt' ? 'selected' : '' }}>PLT</option>
                </select>
            </div>
            <div class="col-md-8 form-group mb-0">
                <label>Delegasi Jabatan</label>
                <select name="delegasi_jabatan_id" class="form-control select2">
                    <option value="">-- Pilih Jabatan Delegasi --</option>
                    @foreach($jabatans as $jabatan)
                        <option value="{{ $jabatan->id }}" {{ (string) old('delegasi_jabatan_id', optional($activeDelegation)->jabatan_id) === (string) $jabatan->id ? 'selected' : '' }}>
                            {{ $jabatan->nama }}{{ $jabatan->unit ? ' - ' . $jabatan->unit->nama : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    <section class="user-form-section">
        <div class="user-form-section-title">
            <i class="fas fa-user-check"></i>
            <span>Relasi Approval</span>
        </div>
        <div class="row">
            <div class="col-md-6 form-group mb-md-0">
                <label>Atasan Langsung</label>
                <select name="atasan_langsung_id" class="form-control select2">
                    <option value="">-- Pilih Atasan Langsung --</option>
                    @foreach($supervisorOptions as $option)
                        @if(!$isEdit || (int) $option->id !== (int) $user->id)
                            <option value="{{ $option->id }}" {{ (string) old('atasan_langsung_id', $user->atasan_langsung_id) === (string) $option->id ? 'selected' : '' }}>
                                {{ $option->name }}{{ optional($option->jabatan)->nama ? ' - ' . optional($option->jabatan)->nama : '' }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 form-group mb-0">
                <label>Pejabat Berwenang Cuti</label>
                <select name="pejabat_berwenang_id" class="form-control select2">
                    <option value="">-- Pilih Pejabat Berwenang --</option>
                    @foreach($supervisorOptions as $option)
                        @if(!$isEdit || (int) $option->id !== (int) $user->id)
                            <option value="{{ $option->id }}" {{ (string) old('pejabat_berwenang_id', $user->pejabat_berwenang_id) === (string) $option->id ? 'selected' : '' }}>
                                {{ $option->name }}{{ optional($option->jabatan)->nama ? ' - ' . optional($option->jabatan)->nama : '' }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>
    </section>
</div>
