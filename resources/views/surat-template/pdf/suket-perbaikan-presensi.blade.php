@php
    $approved = !empty($approvalSignature);
    $field = function ($key, $fallback = '-') use ($fieldValues) {
        $value = data_get($fieldValues, $key);
        return $value === null || trim((string) $value) === '' ? $fallback : $value;
    };
@endphp

<div class="suket-presensi">
@if($kopImage)
    <div class="kop"><img src="{{ $kopImage }}" alt="Kop Surat"></div>
@endif

<div class="title">SURAT KETERANGAN</div>
<div class="number">Nomor : {{ $suratKeluar->nomor_surat_formatted }}</div>

<div class="body">
    <p>Yang bertanda tangan di bawah ini</p>
    <p>Nama&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $field('nama_pembuat_keterangan') }}<br>
        NIP/NRP&nbsp;&nbsp;&nbsp;&nbsp;: {{ $field('nip_pembuat_keterangan') }}<br>
        Jabatan&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $field('jabatan_pembuat_keterangan') }}</p>
    <p>dengan ini menyatakan bahwa nama di bawah ini,</p>
    <p>Nama&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $field('nama_pegawai') }}<br>
        NIP/NRP&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $field('nip_pegawai') }}<br>
        Jabatan&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $field('jabatan_pegawai') }}<br>
        Unit Kerja&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;: {{ $field('unit_kerja') }}<br>
        Satuan Kerja&nbsp;&nbsp;&nbsp;&nbsp;: {{ $field('satuan_kerja') }}<br>
        Tanggal Presensi&nbsp;: {{ $field('tanggal_presensi') !== '-' ? \Carbon\Carbon::parse($field('tanggal_presensi'))->format('d/m/Y') : '-' }}<br>
        Hadir/pulang pukul : {{ $field('waktu_presensi') }} {{ $field('zona_waktu') }}</p>
    <p>adalah benar bertugas sesuai dengan jam kerja yang berlaku pada tanggal dan waktu yang tercantum.</p>
    <p>Saya bertanggung jawab penuh atas kebenaran Informasi {{ $field('status_presensi') }} nama tersebut di atas, sehubungan dengan hal tersebut mohon bantuannya untuk dilakukan perbaikan catatan jam kerja pada Sistem Informasi Manajemen Kepegawaian (SIKEP).</p>
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
                <img class="stamp-signature" src="{{ $approvalSignature['image'] }}" alt="Tanda tangan dan stempel pimpinan">
            @else
                <div class="stamp-placeholder"></div>
            @endif
            <strong>{{ $field('nama_pimpinan_satker') }}</strong><br>
            NIP. {{ $field('nip_pimpinan_satker') }}
        </td>
    </tr>
</table>
</div>
