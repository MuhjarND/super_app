@php
    use Carbon\Carbon;

    $fieldValues = $fieldValues ?? [];
    $dasarRows = $fieldValues['dasar_hukum_rows'] ?? [];
    $petugasRows = $fieldValues['petugas_rows'] ?? [];
    $penandaTangan = $fieldValues['penanda_tangan'] ?? [];
    $tanggalSurat = !empty($fieldValues['tanggal_surat']) ? Carbon::parse($fieldValues['tanggal_surat'])->translatedFormat('d F Y') : Carbon::now()->translatedFormat('d F Y');
    $tanggalMulai = !empty($fieldValues['tanggal_mulai']) ? Carbon::parse($fieldValues['tanggal_mulai'])->translatedFormat('d F Y') : '-';
    $tanggalSelesai = !empty($fieldValues['tanggal_selesai']) ? Carbon::parse($fieldValues['tanggal_selesai'])->translatedFormat('d F Y') : '-';
    $lokasi = trim((string) ($fieldValues['lokasi'] ?? '')) ?: '-';
    $tanggalTugas = $tanggalMulai === $tanggalSelesai ? $tanggalMulai : $tanggalMulai . ' s/d ' . $tanggalSelesai;
@endphp

<style>
    .st-wrap { color:#111827; font-size:13px; line-height:1.5; }
    .st-title { text-align:center; font-weight:700; font-size:28px; margin-bottom:4px; text-transform:uppercase; }
    .st-number { text-align:center; margin-bottom:18px; }
    .st-table { width:100%; border-collapse:collapse; margin-bottom:12px; }
    .st-table td, .st-table th { border:1px solid #cbd5e1; padding:7px 8px; vertical-align:top; }
    .st-row { width:100%; margin-bottom:12px; }
    .st-row table { width:100%; border-collapse:collapse; }
    .st-row td { padding:0; vertical-align:top; }
    .st-label { width:138px; font-weight:700; white-space:nowrap; padding-right:22px !important; }
    .st-sep { width:24px; text-align:center; padding:0 10px !important; }
    .st-content { text-align:justify; }
    .st-indent { display:block; padding-left:20px; text-indent:-20px; }
    .st-section-head { text-align:center; font-weight:700; margin:18px 0 8px; }
    .st-sign { width:260px; margin-left:auto; text-align:center; margin-top:20px; }
</style>

<div class="st-wrap">
    <div class="st-title">Surat Tugas</div>
    <div class="st-number">Nomor : {{ $fieldValues['nomor_surat'] ?? '-' }}</div>

    <div class="st-row">
        <table>
            <tr>
                <td class="st-label">Menimbang</td>
                <td class="st-sep">:</td>
                <td class="st-content">
                    <span class="st-indent">a. Bahwa dalam rangka {{ $fieldValues['dalam_rangka'] ?? '-' }}, maka perlu ditunjuk pejabat untuk melaksanakan kegiatan dimaksud;</span>
                    <span class="st-indent">b. Bahwa untuk melaksanakan tugas sebagaimana tersebut pada huruf (a) perlu dibuatkan surat tugas.</span>
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

    <div class="st-section-head">Memberi Tugas</div>

    <div style="font-weight:700; margin-bottom:6px;">Kepada :</div>
    <table class="st-table">
        <thead>
            <tr>
                <th style="width:52px;">No</th>
                <th>Nama</th>
                <th style="width:180px;">NIP</th>
                <th style="width:180px;">Pangkat</th>
                <th style="width:180px;">Jabatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($petugasRows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}.</td>
                    <td><strong>{{ $row['nama'] }}</strong></td>
                    <td>{{ $row['nip'] }}</td>
                    <td>{{ $row['pangkat'] }}</td>
                    <td>{{ $row['jabatan'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center">Belum ada petugas.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="st-row">
        <table>
            <tr>
                <td class="st-label">Untuk</td>
                <td class="st-sep">:</td>
                <td class="st-content">
                    Melaksanakan {{ $fieldValues['dalam_rangka'] ?? '-' }}, pada tanggal {{ $tanggalTugas }}, di {{ $lokasi }}.
                </td>
            </tr>
        </table>
    </div>

    <div class="st-sign">
        {{ $fieldValues['kota_tanda_tangan'] ?? 'Manokwari' }}, {{ $tanggalSurat }}<br>
        {{ $penandaTangan['jabatan_ttd'] ?? 'Ketua' }},<br><br><br><br>
        <strong>{{ $penandaTangan['nama'] ?? 'Pejabat Penanda Tangan' }}</strong><br>
        NIP. {{ $penandaTangan['nip'] ?? '-' }}
    </div>
</div>
