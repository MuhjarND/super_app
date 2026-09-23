@php
    $approved = !empty($approvalSignature);
    $field = function ($key, $fallback = '-') use ($fieldValues) {
        $value = data_get($fieldValues, $key);
        return is_scalar($value) && trim((string) $value) !== '' ? $value : $fallback;
    };
    $tanggalPresensi = $field('tanggal_presensi');
    $tanggalPresensi = $tanggalPresensi !== '-' ? \Carbon\Carbon::parse($tanggalPresensi)->format('d/m/Y') : '-';
@endphp

<div class="suket-presensi">
@if($kopImage)
    <div class="kop"><img src="{{ $kopImage }}" alt="Kop Surat"></div>
@endif

<div class="title">SURAT KETERANGAN</div>
<div class="number">Nomor : {{ $suratKeluar->nomor_surat_formatted }}</div>

<div class="body">
    <p>Yang bertanda tangan di bawah ini</p>
    <table class="identity-table">
        <tr><td class="identity-label">Nama</td><td class="identity-colon">:</td><td>{{ $field('nama_pembuat_keterangan') }}</td></tr>
        <tr><td class="identity-label">NIP/NRP</td><td class="identity-colon">:</td><td>{{ $field('nip_pembuat_keterangan') }}</td></tr>
        <tr><td class="identity-label">Jabatan</td><td class="identity-colon">:</td><td>{{ $field('jabatan_pembuat_keterangan') }}</td></tr>
    </table>
    <p>dengan ini menyatakan bahwa nama di bawah ini,</p>
    <table class="identity-table identity-table-subject">
        <tr><td class="identity-label">Nama</td><td class="identity-colon">:</td><td>{{ $field('nama_pegawai') }}</td></tr>
        <tr><td class="identity-label">NIP/NRP</td><td class="identity-colon">:</td><td>{{ $field('nip_pegawai') }}</td></tr>
        <tr><td class="identity-label">Jabatan</td><td class="identity-colon">:</td><td>{{ $field('jabatan_pegawai') }}</td></tr>
        <tr><td class="identity-label">Unit Kerja</td><td class="identity-colon">:</td><td>{{ $field('unit_kerja') }}</td></tr>
        <tr><td class="identity-label">Satuan Kerja</td><td class="identity-colon">:</td><td>{{ $field('satuan_kerja') }}</td></tr>
        <tr><td class="identity-label">Tanggal dan Waktu Presensi</td><td class="identity-colon">:</td><td>{{ $tanggalPresensi }} pukul {{ $field('waktu_presensi') }} {{ $field('zona_waktu') }}</td></tr>
    </table>
    <p>adalah benar bertugas sesuai dengan jam kerja yang berlaku pada tanggal dan waktu yang tercantum.</p>
    <p>Saya bertanggung jawab penuh atas kebenaran informasi {{ $field('status_presensi') }} atas nama tersebut di atas. Sehubungan dengan hal tersebut, mohon bantuannya untuk dilakukan perbaikan catatan jam kerja pada Sistem Informasi Manajemen Kepegawaian (SIKEP).</p>
    <p>Demikian surat keterangan ini dibuat dan untuk dipergunakan sebagaimana mestinya.</p>
</div>

<div class="footer">
    <div class="sign">
        {{ $field('tempat_surat', 'Manokwari') }}, {{ optional($suratKeluar->tanggal_surat)->translatedFormat('d F Y') }}<br>
        {{ $field('jabatan_pembuat_keterangan') }}<br>
        <div style="height:54px;"></div>
        <strong>{{ $field('nama_pembuat_keterangan') }}</strong><br>
        NIP. {{ $field('nip_pembuat_keterangan') }}
    </div>
</div>

<table class="approval-box">
    <tr><th colspan="2">PERSETUJUAN PERUBAHAN</th></tr>
    <tr>
        <td class="approval-options">
            <div>{{ $approved ? '☑' : '☐' }} Disetujui</div>
            <div>☐ Ditolak, karena {{ $approved ? '-' : ($approvalNote ?? '-') }}</div>
        </td>
        <td class="approval-sign">
            {{ $field('jabatan_pimpinan_satker', 'Ketua') }},<br>
            @if($approved && !empty($approvalSignature['image']))
                <img class="signature-qr" src="{{ $approvalSignature['image'] }}" alt="QR tanda tangan pimpinan">
                <div class="qr-caption">Pindai untuk verifikasi</div>
            @else
                <div class="signature-placeholder"></div>
            @endif
            <strong>{{ $field('nama_pimpinan_satker') }}</strong><br>
            NIP. {{ $field('nip_pimpinan_satker') }}
        </td>
    </tr>
</table>
</div>
