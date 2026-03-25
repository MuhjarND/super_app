@php
    use Carbon\Carbon;

    $dasarRows = $fieldValues['dasar_hukum_rows'] ?? [];
    $petugasRows = $fieldValues['petugas_rows'] ?? [];
    $penandaTangan = $fieldValues['penanda_tangan'] ?? [];
    $tanggalSurat = optional($suratKeluar->tanggal_surat)->translatedFormat('d F Y');
    $tanggalMulai = !empty($fieldValues['tanggal_mulai']) ? Carbon::parse($fieldValues['tanggal_mulai'])->translatedFormat('d F Y') : '-';
    $tanggalSelesai = !empty($fieldValues['tanggal_selesai']) ? Carbon::parse($fieldValues['tanggal_selesai'])->translatedFormat('d F Y') : '-';
    $kotaTtd = trim((string) ($fieldValues['kota_tanda_tangan'] ?? '')) ?: 'Manokwari';
@endphp

<style>
    .st-doc { font-family: "Times New Roman", DejaVu Serif, serif; color:#111; font-size:12px; line-height:1.35; }
    .st-kop { margin-bottom:8px; }
    .st-kop img { width:100%; height:auto; display:block; }
    .st-title { text-align:center; font-weight:700; font-size:18px; text-transform:uppercase; text-decoration:underline; margin:4px 0 2px; }
    .st-number { text-align:center; margin-bottom:10px; }
    .st-grid { width:100%; border-collapse:collapse; margin-bottom:8px; }
    .st-grid td, .st-grid th { border:1px solid #111; padding:5px 6px; vertical-align:top; }
    .st-grid th { text-align:center; font-weight:700; }
    .st-row { width:100%; margin-bottom:8px; }
    .st-row table { width:100%; border-collapse:collapse; }
    .st-row td { padding:0; vertical-align:top; }
    .st-label { width:88px; white-space:nowrap; }
    .st-sep { width:14px; text-align:center; }
    .st-content { text-align:justify; }
    .st-indent { display:block; padding-left:20px; text-indent:-20px; }
    .st-section { text-align:center; font-weight:700; margin:10px 0 6px; }
    .st-sign { width:245px; margin-left:auto; text-align:center; margin-top:16px; }
    .st-sign-city { margin-bottom:2px; }
    .st-sign-role { display:block; margin-bottom:8px; }
    .st-sign-qr-wrap { width:100%; text-align:center; margin:0 auto 6px; }
    .st-sign-qr { width:104px; height:104px; display:block; margin:0 auto; }
    .st-sign-placeholder { display:block; height:104px; }
    .st-sign-name { display:block; font-weight:700; line-height:1.2; margin-top:2px; }
    .st-sign-nip { display:block; margin-top:4px; }
</style>

<div class="st-doc">
    @if($kopImage)
        <div class="st-kop">
            <img src="{{ $kopImage }}" alt="Kop Surat">
        </div>
    @endif

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
                <th style="width:40px;">No</th>
                <th>Nama</th>
                <th style="width:150px;">NIP</th>
                <th style="width:150px;">Pangkat</th>
                <th style="width:150px;">Jabatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($petugasRows as $index => $row)
                <tr>
                    <td style="text-align:center;">{{ $index + 1 }}</td>
                    <td>{{ $row['nama'] }}</td>
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
                    {{ $fieldValues['untuk_tugas'] ?? '-' }}, pada tanggal {{ $tanggalMulai }} s/d {{ $tanggalSelesai }}.
                </td>
            </tr>
        </table>
    </div>

    <div class="st-sign">
        <div class="st-sign-city">{{ $kotaTtd }}, {{ $tanggalSurat }}</div>
        <span class="st-sign-role">{{ $penandaTangan['jabatan_ttd'] ?? 'Ketua' }},</span>
        <div class="st-sign-qr-wrap">
            @if(!empty($approvalSignature['barcode']))
                <img class="st-sign-qr" src="{{ $approvalSignature['barcode'] }}" alt="Barcode TTD">
            @else
                <span class="st-sign-placeholder"></span>
            @endif
        </div>
        <span class="st-sign-name">{{ $penandaTangan['nama'] ?? (optional($suratKeluar->creator)->name ?: 'Pejabat Penandatangan') }}</span>
        <span class="st-sign-nip">NIP. {{ $penandaTangan['nip'] ?? '-' }}</span>
    </div>
</div>
