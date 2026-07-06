@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Profil Saya</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" id="profileForm">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Foto Profil</label>
                            <div class="d-flex align-items-center mb-3" style="gap:16px;">
                                <div style="width:76px;height:76px;border-radius:20px;overflow:hidden;background:#eef2ff;display:flex;align-items:center;justify-content:center;border:1px solid #dbeafe;">
                                    @if($user->profile_photo_path)
                                        <img src="{{ asset('storage/' . $user->profile_photo_path) }}" alt="{{ $user->name }}" style="width:100%;height:100%;object-fit:cover;">
                                    @else
                                        <span style="font-size:1.35rem;font-weight:800;color:#4f46e5;">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" name="profile_photo" class="form-control-file">
                                    <div class="mt-2">
                                        <label class="mb-0" style="font-weight:500;">
                                            <input type="checkbox" name="remove_photo" value="1"> Hapus foto profil
                                        </label>
                                    </div>
                                    @error('profile_photo')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="name">Nama</label>
                            <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" value="{{ old('username', $user->username) }}" required>
                            <small class="form-text text-muted">Username dapat dipakai untuk login selain email.</small>
                            @error('username')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="text" class="form-control" value="{{ $user->email }}" readonly>
                        </div>

                        <div class="form-group">
                            <label>Tanda Tangan Profil</label>
                            <div class="border rounded p-3" style="background:#f8fafc;">
                                @if($user->profile_signature_path)
                                    <div class="d-flex align-items-center mb-3" style="gap:14px;">
                                        <div style="width:180px;height:78px;border:1px solid #dbe4f0;border-radius:8px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                                            <img src="{{ asset('storage/' . $user->profile_signature_path) }}" alt="Tanda tangan {{ $user->name }}" style="max-width:100%;max-height:100%;object-fit:contain;">
                                        </div>
                                        <div>
                                            <div class="font-weight-bold">Tanda tangan sudah tersimpan</div>
                                            <small class="text-muted">Metode: {{ strtoupper($user->profile_signature_method ?: '-') }}</small>
                                            <div class="mt-2">
                                                <label class="mb-0" style="font-weight:500;">
                                                    <input type="checkbox" name="remove_signature" value="1"> Hapus tanda tangan
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="alert alert-warning py-2 mb-3">
                                        Tanda tangan belum tersimpan. Simpan tanda tangan agar approval dan PDF tidak lagi meminta tanda tangan manual.
                                    </div>
                                @endif

                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="signatureMethodDraw" name="signature_method" value="draw" class="custom-control-input" checked>
                                    <label class="custom-control-label" for="signatureMethodDraw">Tanda tangan langsung</label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="signatureMethodUpload" name="signature_method" value="upload" class="custom-control-input">
                                    <label class="custom-control-label" for="signatureMethodUpload">Upload gambar</label>
                                </div>

                                <div id="profileSignatureDrawBox" class="mt-3">
                                    @include('partials.signature-pad', [
                                        'id' => 'profileSignaturePad',
                                        'name' => 'profile_signature_data',
                                        'label' => 'Tanda Tangan',
                                        'required' => false,
                                        'hint' => 'Tanda tangan ini akan digunakan otomatis pada dokumen yang membutuhkan tanda tangan Anda.'
                                    ])
                                </div>

                                <div id="profileSignatureUploadBox" class="mt-3" style="display:none;">
                                    <input type="file" name="profile_signature_file" class="form-control-file" accept="image/png,image/jpeg">
                                    <small class="form-text text-muted">Gunakan gambar PNG/JPG berlatar transparan atau putih.</small>
                                </div>

                                @error('profile_signature_data')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                                @error('profile_signature_file')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Jabatan</label>
                            <input type="text" class="form-control" value="{{ $user->jabatan_keterangan ?: optional($user->jabatan)->nama ?: '-' }}" readonly>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Simpan Profil
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ubah Password</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="current_password">Password Saat Ini</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                            @error('current_password')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password">Password Baru</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                            @error('password')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Konfirmasi Password Baru</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-key mr-1"></i> Ubah Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('profileForm');
            const drawRadio = document.getElementById('signatureMethodDraw');
            const uploadRadio = document.getElementById('signatureMethodUpload');
            const drawBox = document.getElementById('profileSignatureDrawBox');
            const uploadBox = document.getElementById('profileSignatureUploadBox');

            function syncMethod() {
                const useDraw = drawRadio && drawRadio.checked;
                if (drawBox) drawBox.style.display = useDraw ? '' : 'none';
                if (uploadBox) uploadBox.style.display = useDraw ? 'none' : '';
            }

            if (drawRadio) drawRadio.addEventListener('change', syncMethod);
            if (uploadRadio) uploadRadio.addEventListener('change', syncMethod);
            syncMethod();

            if (form) {
                form.addEventListener('submit', function () {
                    if (drawRadio && drawRadio.checked && window.AppSignaturePad) {
                        const field = document.querySelector('#profileSignatureDrawBox .js-signature-pad');
                        window.AppSignaturePad.sync(field);
                    }
                });
            }
        })();
    </script>
@endpush
