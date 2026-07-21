@php
    use Carbon\Carbon;

    $dasarRows = $fieldValues['dasar_hukum_rows'] ?? [];
    $petugasRows = $fieldValues['petugas_rows'] ?? [];
    $penandaTangan = $fieldValues['penanda_tangan'] ?? [];
    $tanggalSurat = optional($suratKeluar->tanggal_surat)->translatedFormat('d F Y');
    $tanggalMulai = !empty($fieldValues['tanggal_mulai']) ? Carbon::parse($fieldValues['tanggal_mulai'])->translatedFormat('d F Y') : '-';
    $tanggalSelesai = !empty($fieldValues['tanggal_selesai']) ? Carbon::parse($fieldValues['tanggal_selesai'])->translatedFormat('d F Y') : '-';
    $kotaTtd = trim((string) ($fieldValues['kota_tanda_tangan'] ?? '')) ?: 'Manokwari';
    $lokasi = trim((string) ($fieldValues['lokasi'] ?? '')) ?: '-';
    $tanggalTugas = $tanggalMulai === $tanggalSelesai ? $tanggalMulai : $tanggalMulai . ' s/d ' . $tanggalSelesai;
@endphp

<style>
    @page { margin: 3.75cm 1.85cm 2.85cm 2cm; }
    .st-page-header { position: fixed; top: -3.3cm; left: 0; right: 0; height: 2.75cm; }
    .st-page-header img { width: 100%; height: auto; display: block; }
    .st-doc { font-family: "Times New Roman", DejaVu Serif, serif; color:#111; font-size:11.2px; line-height:1.28; }
    .st-title { text-align:center; font-weight:700; font-size:17px; text-transform:uppercase; text-decoration:underline; margin:0 0 1px; }
    .st-number { text-align:center; margin-bottom:9px; }
    .st-grid { width:100%; border-collapse:collapse; table-layout:fixed; margin-bottom:8px; page-break-inside:auto; }
    .st-grid td, .st-grid th { border:0.85px solid #111; padding:3px 5px; vertical-align:top; line-height:1.22; }
    .st-grid th { text-align:center; font-weight:700; padding-top:4px; padding-bottom:4px; }
    .st-grid tr { page-break-inside:avoid; page-break-after:auto; }
    .st-grid td { overflow-wrap:break-word; word-wrap:break-word; }
    .st-row { width:100%; margin-bottom:8px; }
    .st-row table { width:100%; border-collapse:collapse; }
    .st-row td { padding:0; vertical-align:top; }
    .st-label { width:104px; white-space:nowrap; padding-right:18px !important; }
    .st-sep { width:20px; text-align:center; padding:0 8px !important; }
    .st-content { text-align:justify; }
    .st-indent { display:block; padding-left:18px; text-indent:-18px; }
    .st-section { text-align:center; font-weight:700; margin:8px 0 5px; }
    .st-sign { width:245px; margin-left:auto; text-align:center; margin-top:14px; page-break-inside:avoid; }
    .st-sign-city { margin-bottom:2px; }
    .st-sign-role { display:block; margin-bottom:0; }
    .st-sign-image-wrap { width:100%; text-align:center; margin:0 auto -16px; }
    .st-sign-image { width:172px; height:88px; display:block; margin:0 auto; object-fit:contain; }
    .st-sign-placeholder { display:block; height:72px; }
    .st-sign-name { display:block; font-weight:700; line-height:1.2; margin-top:0; position:relative; z-index:1; }
    .st-sign-nip { display:block; margin-top:2px; position:relative; z-index:1; }
</style>

@if($kopImage)
    <div class="st-page-header">
        <img src="{{ $kopImage }}" alt="Kop Surat">
    </div>
@endif

<div class="st-doc">
    <div class="st-title">Surat Tugas</div>
    <div class="st-number">Nomor : {{ $suratKeluar->nomor_surat_formatted }}</div>

    <div class="st-row">
        <table>
            <tr>
                <td class="st-label">Menimbang</td>
                <td class="st-sep">:</td>
                <td class="st-content">
                    <span class="st-indent">a. Bahwa dalam rangka {{ $fieldValues['dalam_rangka'] ?? '-' }}, maka perlu ditunjuk pejabat untuk melaksanakan kegiatan dimaksud;</span>
                    <span class="st-indent">b. Bahwa untuk melaksanakan tugas sebagaimana tersebut pada huruf a perlu dibuatkan surat tugas.</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="st-row">
        <table>
            <tr>
                <td class="st-label">Dasar</td>
                <td class="st-sep">:</td>
                <td class="st-content">
                    @forelse($dasarRows as $index => $row)
                        <span class="st-indent">{{ $index + 1 }}. {{ $row }}</span>
                    @empty
                        <span>-</span>
                    @endforelse
                </td>
            </tr>
        </table>
    </div>

    <div class="st-section">Memberi Tugas</div>

    <div style="margin-bottom:6px;"><strong>Kepada :</strong></div>
    <table class="st-grid">
        <thead>
            <tr>
                <th style="width:7%;">No</th>
                <th style="width:28%;">Nama</th>
                <th style="width:21%;">NIP</th>
                <th style="width:22%;">Pangkat</th>
                <th style="width:22%;">Jabatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($petugasRows as $index => $row)
                <tr>
                    <td style="text-align:center;">{{ $index + 1 }}</td>
                    <td><strong>{{ $row['nama'] }}</strong></td>
                    <td>{{ $row['nip'] }}</td>
                    <td>{{ $row['pangkat'] }}</td>
                    <td>{{ $row['jabatan'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center;">Belum ada petugas.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="st-row">
        <table>
            <tr>
                <td class="st-label">Untuk</td>
                <td class="st-sep">:</td>
                <td class="st-content">
                    {{ rtrim(trim((string) ($fieldValues['untuk_tugas'] ?? '-')), '.;') }}, pada tanggal {{ $tanggalTugas }}, bertempat di {{ $lokasi }}.
                </td>
            </tr>
        </table>
    </div>

    <div class="st-sign">
        <div class="st-sign-city">{{ $kotaTtd }}, {{ $tanggalSurat }}</div>
        <span class="st-sign-role">{{ $penandaTangan['jabatan_ttd'] ?? 'Ketua' }},</span>
        <div class="st-sign-image-wrap">
            @if(!empty($approvalSignature['image']))
                <img class="st-sign-image" src="{{ $approvalSignature['image'] }}" alt="Tanda Tangan Digital">
            @else
                <span class="st-sign-placeholder"></span>
            @endif
        </div>
        <span class="st-sign-name">{{ $penandaTangan['nama'] ?? (optional($suratKeluar->creator)->name ?: 'Pejabat Penandatangan') }}</span>
        <span class="st-sign-nip">NIP. {{ $penandaTangan['nip'] ?? '-' }}</span>
    </div>
</div>
