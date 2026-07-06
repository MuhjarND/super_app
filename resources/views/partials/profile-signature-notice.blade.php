@php
    $signatureUser = $signatureUser ?? auth()->user();
@endphp

@if($signatureUser && $signatureUser->hasProfileSignature())
    <div class="alert alert-info py-2 px-3 mb-0">
        <i class="fas fa-signature mr-1"></i>
        Dokumen ini akan memakai tanda tangan yang tersimpan pada Profil Saya.
    </div>
@else
    <div class="alert alert-warning py-2 px-3 mb-0">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        Tanda tangan profil belum tersimpan.
        <a href="{{ route('profile.edit') }}" class="font-weight-bold">Simpan TTD</a>
        sebelum melanjutkan.
    </div>
@endif
