@extends('layouts.app')

@section('title', 'Authenticator 2 Faktor')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Authenticator 2 Faktor</h3>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning">{{ session('warning') }}</div>
                    @endif

                    <p class="text-muted mb-4">Aktifkan autentikasi 2 faktor agar setiap login memerlukan kode 6 digit dari aplikasi authenticator.</p>

                    @if($user->hasTwoFactorEnabled())
                        <div class="alert alert-success">
                            <strong>Status:</strong> Aktif sejak {{ optional($user->two_factor_confirmed_at)->format('d M Y H:i') }}
                        </div>

                        <div class="row">
                            <div class="col-lg-7">
                                <div class="border rounded p-3 mb-4">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-1">Backup Recovery Code</h5>
                                            <p class="text-muted mb-0">Gunakan kode ini jika perangkat authenticator tidak tersedia. Setiap kode hanya dapat dipakai satu kali.</p>
                                        </div>
                                        <span class="badge badge-info">{{ $remainingRecoveryCodeCount }} kode tersisa</span>
                                    </div>

                                    @if(!empty($generatedRecoveryCodes))
                                        <div class="alert alert-warning mt-3 mb-3">
                                            <strong>Simpan sekarang.</strong> Recovery code ini hanya ditampilkan sekali pada layar ini.
                                        </div>
                                        <div class="row">
                                            @foreach($generatedRecoveryCodes as $generatedRecoveryCode)
                                                <div class="col-sm-6 mb-2">
                                                    <div class="border rounded px-3 py-2 font-weight-bold text-monospace">{{ $generatedRecoveryCode }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="alert alert-light border mt-3 mb-3">
                                            Recovery code aktif tidak ditampilkan ulang demi keamanan. Jika diperlukan, buat ulang recovery code baru.
                                        </div>
                                    @endif

                                    <form action="{{ route('two-factor.recovery-codes.regenerate') }}" method="POST" class="mt-2">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="fas fa-sync-alt mr-1"></i> Buat Ulang Recovery Code
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <form action="{{ route('two-factor.disable') }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <div class="form-group">
                                <label for="current_password">Password Saat Ini</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" required>
                                @error('current_password')
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-user-shield mr-1"></i> Nonaktifkan 2 Faktor
                            </button>
                        </form>
                    @else
                        @if(!$setupSecret)
                            <form action="{{ route('two-factor.setup') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-shield-alt mr-1"></i> Mulai Setup 2 Faktor
                                </button>
                            </form>
                        @else
                            <div class="row">
                                <div class="col-md-5 mb-3">
                                    <div class="text-center p-3" style="border:1px solid #e5e7eb;border-radius:16px;background:#fff;">
                                        {!! $qrSvg !!}
                                    </div>
                                </div>
                                <div class="col-md-7">
                                    <div class="form-group">
                                        <label>Secret Manual</label>
                                        <input type="text" class="form-control" value="{{ $setupSecret }}" readonly>
                                    </div>

                                    <form action="{{ route('two-factor.enable') }}" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <label for="code">Kode Verifikasi 6 Digit</label>
                                            <input type="text" id="code" name="code" class="form-control" maxlength="6" inputmode="numeric" required>
                                            @error('code')
                                                <div class="text-danger mt-1">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-check-circle mr-1"></i> Aktifkan 2 Faktor
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
