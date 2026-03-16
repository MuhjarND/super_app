@php
    $suratTujuan = trim((string) $rapat->tujuan_surat);
    $tujuanLines = $suratTujuan !== '' ? preg_split('/\r\n|\r|\n/', $suratTujuan) : [];
    $defaultTujuan = $rapat->pesertas->count() === 1
        ? [$rapat->pesertas->first()->name]
        : ['Para Pejabat dan Pegawai', 'terlampir'];
@endphp

<div class="document-preview">
    <div class="document-preview__header">
        <div class="document-preview__title">Undangan Rapat</div>
        <div class="document-preview__date">{{ $rapat->created_at ? $rapat->created_at->copy()->timezone('Asia/Jayapura')->translatedFormat('d F Y') : '-' }}</div>
    </div>
    <table class="document-preview__meta">
        <tr>
            <td>Nomor</td>
            <td>: {{ $rapat->nomor_undangan }}</td>
        </tr>
        <tr>
            <td>Perihal</td>
            <td>: {{ $rapat->judul }}</td>
        </tr>
        <tr>
            <td>Tujuan</td>
            <td>:
                <div class="mt-1">
                    @foreach(($tujuanLines ?: $defaultTujuan) as $line)
                        <div>{{ $line }}</div>
                    @endforeach
                </div>
            </td>
        </tr>
    </table>

    <div class="document-preview__body">
        <p>
            {{ $rapat->detail_tambahan ?: 'Dengan hormat, dimohon kehadiran Bapak/Ibu pada kegiatan rapat sebagai berikut:' }}
        </p>
        <table class="document-preview__meta">
            <tr>
                <td>Hari / Tanggal</td>
                <td>: {{ optional($rapat->tanggal)->translatedFormat('l, d F Y') }}</td>
            </tr>
            <tr>
                <td>Waktu</td>
                <td>: {{ $rapat->waktu_mulai_formatted }} WIT</td>
            </tr>
            <tr>
                <td>Tempat</td>
                <td>: {{ $rapat->tempat }}</td>
            </tr>
            <tr>
                <td>Kategori</td>
                <td>: {{ optional($rapat->kategoriRapat)->nama ?: '-' }}</td>
            </tr>
            @if($rapat->jenis_pakaian)
                <tr>
                    <td>Pakaian</td>
                    <td>: {{ $rapat->jenis_pakaian }}</td>
                </tr>
            @endif
            @if($rapat->is_virtual)
                <tr>
                    <td>Meeting ID</td>
                    <td>: {{ $rapat->meeting_id }}</td>
                </tr>
                <tr>
                    <td>Passcode</td>
                    <td>: {{ $rapat->meeting_passcode }}</td>
                </tr>
            @endif
        </table>

        @if($rapat->deskripsi)
            <p class="mb-2"><strong>Deskripsi:</strong> {{ $rapat->deskripsi }}</p>
        @endif

        <p class="mb-2"><strong>Peserta Undangan:</strong></p>
        <ol class="document-preview__list">
            @foreach($rapat->pesertas as $peserta)
                <li>{{ $peserta->name }}{{ $peserta->jabatan_keterangan ? ' - ' . $peserta->jabatan_keterangan : '' }}</li>
            @endforeach
        </ol>
    </div>
</div>
