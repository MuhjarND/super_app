<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
@page { size: 209.97mm 297.69mm; margin: 0.56cm 1cm 0.28cm 1cm; }
body { font-family: 'Times New Roman', DejaVu Serif, serif; font-size: 8.32px; color: #111; line-height: 1.055; }
body { margin: 0; }
.wrapper { width: 88.2%; margin: 0 auto; padding-top: 1.8px; }
.top-note { text-align: right; font-size: 7.55px; margin-bottom: 1.2px; }
.date-line { text-align: right; font-size: 8.45px; margin-bottom: 2.5px; }
.to-line { text-align: center; font-size: 8.45px; line-height: 1.06; margin-bottom: 3.8px; }
.title { text-align: center; font-size: 10.55px; font-weight: bold; text-transform: uppercase; margin-bottom: 0.2px; letter-spacing: .05px; }
.number { text-align: center; font-size: 8.05px; margin-bottom: 3.5px; }
.section-table { width: 100%; border-collapse: collapse; margin-bottom: 5.6px; page-break-inside: avoid; }
.section-table td, .section-table th { border: 0.8px solid #111; padding: 1px 2.6px; vertical-align: middle; }
.section-head td { font-weight: bold; font-size: 9.12px; letter-spacing: .05px; padding-top: 1.18px; padding-bottom: 1.18px; }
.label-cell { font-weight: bold; text-transform: uppercase; }
.center { text-align: center; }
.checkbox { font-family: DejaVu Sans, sans-serif; font-size: 11.4px; font-weight: bold; line-height: 1; display: inline-block; }
.inner-table { width: 100%; border-collapse: collapse; }
.inner-table td, .inner-table th { border: 0.8px solid #111; padding: 1px 2.6px; vertical-align: middle; }
.inner-tight td { padding-top: 0.85px; padding-bottom: 0.85px; }
.paraf-box { text-align: center; vertical-align: top; }
.paraf-initials { font-family: DejaVu Sans, sans-serif; font-size: 16px; font-style: italic; line-height: 1; margin-top: 3px; }
.address-cell { vertical-align: top; height: 56px; }
.signature-cell { vertical-align: top; }
.signature-inner { text-align: center; font-size: 7.72px; line-height: 1.035; }
.signature-pad-img { height: 42px; max-width: 115px; margin: 1px auto 1px auto; display: block; object-fit: contain; }
.signature-name { font-weight: bold; }
.blank-area { height: 46px; }
.decision-label { text-align: center; font-size: 7.72px; text-transform: uppercase; }
.decision-mark { height: 10px; text-align: center; vertical-align: middle; font-family: DejaVu Sans, sans-serif; font-size: 11px; font-weight: bold; }
.note { font-size: 7.7px; margin-top: 0.8px; line-height: 1.08; }
.section-table.tight-bottom { margin-bottom: 4.9px; }
.type-label { width: 42%; padding-left: 6px !important; }
.type-mark { width: 8%; text-align: center; }
.compact-text { line-height: 0.98; }
</style>
</head>
<body>
@include('partials.pdf-verification-badge', ['pdfVerification' => $pdfVerification ?? null])
@php
    $statusAtasan = optional($formData['atasan'])->status;
    $statusPpk = optional($formData['ppk'])->status;
@endphp
<div class="wrapper">
    <div class="top-note">Lampiran II : Formulir Permintaan dan Pemberian Cuti</div>
    <div class="date-line">Manokwari, {{ optional($leaveRequest->submitted_at ?: $leaveRequest->created_at)->translatedFormat('d F Y') }}</div>
    <div class="to-line">
        Yth. Ketua Pengadilan Tinggi Agama Papua Barat<br>
        di-<br>
        Manokwari
    </div>
    <div class="title">Formulir Permintaan dan Pemberian Cuti</div>
    <div class="number">Nomor : {{ $leaveRequest->letter_number ?: ($leaveRequest->request_number ?: '-') }}</div>

    <table class="section-table">
        <tr class="section-head"><td colspan="4">I. &nbsp; DATA PEGAWAI</td></tr>
        <tr>
            <td class="label-cell" width="16%">NAMA</td>
            <td width="44%">{{ optional($leaveRequest->user)->name }}</td>
            <td class="label-cell" width="16%">NIP</td>
            <td width="24%">{{ optional($leaveRequest->user)->nip ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label-cell">JABATAN</td>
            <td>{{ optional(optional($leaveRequest->user)->jabatan)->nama ?: ($leaveRequest->jabatan_snapshot ?: '-') }}</td>
            <td class="label-cell">GOL. RUANG</td>
            <td>{{ optional($leaveRequest->user)->jabatan_keterangan ?: '-' }}</td>
        </tr>
        <tr>
            <td class="label-cell">UNIT KERJA</td>
            <td>{{ optional(optional($leaveRequest->user)->unit)->nama ?: ($leaveRequest->unit_snapshot ?: '-') }}</td>
            <td class="label-cell">MASA KERJA</td>
            <td>{{ $formData['workPeriodText'] }}</td>
        </tr>
    </table>

    <table class="section-table tight-bottom">
        <tr class="section-head"><td colspan="4">II. &nbsp; JENIS CUTI YANG DIAMBIL **</td></tr>
        <tr>
            <td class="type-label">1. CUTI TAHUNAN</td>
            <td class="type-mark"><span class="checkbox">{{ $formData['selectedTypeCode'] === 'CT' ? 'v' : '' }}</span></td>
            <td class="type-label">2. CUTI BESAR</td>
            <td class="type-mark"><span class="checkbox">{{ $formData['selectedTypeCode'] === 'CB' ? 'v' : '' }}</span></td>
        </tr>
        <tr>
            <td class="type-label">3. CUTI SAKIT</td>
            <td class="type-mark"><span class="checkbox">{{ $formData['selectedTypeCode'] === 'CS' ? 'v' : '' }}</span></td>
            <td class="type-label">4. CUTI MELAHIRKAN</td>
            <td class="type-mark"><span class="checkbox">{{ $formData['selectedTypeCode'] === 'CM' ? 'v' : '' }}</span></td>
        </tr>
        <tr>
            <td class="type-label">5. CUTI KARENA ALASAN PENTING</td>
            <td class="type-mark"><span class="checkbox">{{ $formData['selectedTypeCode'] === 'CAP' ? 'v' : '' }}</span></td>
            <td class="type-label">6. CUTI DI LUAR TANGGUNGAN NEGARA</td>
            <td class="type-mark"><span class="checkbox">{{ $formData['selectedTypeCode'] === 'CLTN' ? 'v' : '' }}</span></td>
        </tr>
    </table>

    <table class="section-table">
        <tr class="section-head"><td>III. &nbsp; ALASAN CUTI</td></tr>
        <tr><td style="height:10px;" class="compact-text">{{ $leaveRequest->purpose }}</td></tr>
    </table>

    <table class="section-table">
        <tr class="section-head"><td colspan="4">IV. &nbsp; LAMANYA CUTI</td></tr>
        <tr>
            <td width="13%">Selama</td>
            <td width="30%">{{ $leaveRequest->requested_days ?: 0 }} Hari Kerja</td>
            <td width="18%" class="center">Mulai Tanggal</td>
            <td width="39%" style="padding:0;">
                <table class="inner-table" style="border:none; width:100%; margin:-1px;">
                    <tr>
                        <td width="63%" style="border-left:none;border-top:none;border-bottom:none;">{{ optional($leaveRequest->start_date)->translatedFormat('d F Y') }}</td>
                        <td width="11%" class="center" style="border-top:none;border-bottom:none;">s/d</td>
                        <td width="26%" style="border-right:none;border-top:none;border-bottom:none;">{{ optional($leaveRequest->end_date)->translatedFormat('d F Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="section-table">
        <tr class="section-head"><td colspan="4">V. &nbsp; CATATAN CUTI ***</td></tr>
        <tr>
            <td colspan="2" style="padding:0; width:50%;">
                <table class="inner-table" style="width:100%; margin:-1px;">
                    <tr>
                        <td colspan="4" class="label-cell">H. CUTI TAHUNAN</td>
                    </tr>
                    <tr>
                        <td width="25%" class="label-cell center">TAHUN</td>
                        <td width="20%" class="label-cell center">SISA</td>
                        <td width="55%" colspan="2" class="label-cell center">KETERANGAN</td>
                    </tr>
                    @foreach($formData['annualLeaveRows'] as $row)
                        <tr>
                            <td>{{ $row['year'] }}</td>
                            <td class="center">{{ $row['remaining'] }}</td>
                            <td colspan="2">{{ $row['note'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
            <td class="paraf-box compact-text" width="16%">
                <div><strong>PARAF<br>PETUGAS CUTI</strong></div>
                @if($formData['verifierParaf'])
                    <div class="paraf-initials">{{ $formData['verifierParaf']['initials'] }}</div>
                @endif
            </td>
            <td style="padding:0; width:34%;">
                <table class="inner-table" style="width:100%; margin:-1px;">
                    <tr><td class="label-cell">I. CUTI BESAR</td></tr>
                    <tr><td class="label-cell">J. CUTI SAKIT</td></tr>
                    <tr><td class="label-cell">K. CUTI MELAHIRKAN</td></tr>
                    <tr><td class="label-cell">L. CUTI KARENA ALASAN PENTING</td></tr>
                    <tr><td class="label-cell">M. CUTI DI LUAR TANGGUNGAN NEGARA</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="section-table">
        <tr class="section-head"><td colspan="3">VI. &nbsp; ALAMAT SELAMA MENJALANKAN CUTI</td></tr>
        <tr>
            <td width="59%" rowspan="2" class="address-cell">{{ $leaveRequest->leave_address ?: '-' }}</td>
            <td width="7%">Telp.</td>
            <td width="34%">{{ $leaveRequest->contact_phone ?: '-' }}</td>
        </tr>
        <tr>
            <td colspan="2" class="signature-cell" style="height:37px;">
                <div class="signature-inner">
                    <div style="font-size:7px; margin-bottom:1px;">Hormat Saya,</div>
                    @if(!empty($formData['pemohonSignature']))<img class="signature-pad-img" src="{{ $formData['pemohonSignature'] }}">@endif
                    <div class="signature-name">({{ optional($leaveRequest->user)->name ?: '-' }})</div>
                    <div>NIP. {{ optional($leaveRequest->user)->nip ?: '-' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="section-table">
        <tr class="section-head"><td colspan="4">VII. &nbsp; PERTIMBANGAN ATASAN LANGSUNG **</td></tr>
        <tr>
            <td class="decision-label" width="13%">DISETUJUI</td>
            <td class="decision-label" width="23%">PERUBAHAN ****</td>
            <td class="decision-label" width="23%">DITANGGUHKAN ****</td>
            <td class="decision-label" width="41%">TIDAK DISETUJUI ****</td>
        </tr>
        <tr>
            <td class="decision-mark">{{ $statusAtasan === 'approved' ? 'v' : '' }}</td>
            <td class="decision-mark"></td>
            <td class="decision-mark"></td>
            <td class="decision-mark">{{ $statusAtasan === 'rejected' ? 'v' : '' }}</td>
        </tr>
        <tr>
            <td colspan="3" class="blank-area"></td>
            <td class="signature-cell">
                <div class="signature-inner">
                    @if(!empty($formData['atasanSignature']))<img class="signature-pad-img" src="{{ $formData['atasanSignature'] }}">@endif
                    <div>{{ optional(optional(optional($formData['atasan'])->approver)->jabatan)->nama ?: optional(optional($leaveRequest->user)->atasanLangsung)->jabatan_keterangan ?: '-' }}</div>
                    <div class="signature-name">({{ optional(optional($formData['atasan'])->approver)->name ?: optional(optional($leaveRequest->user)->atasanLangsung)->name ?: '-' }})</div>
                    <div>NIP. {{ optional(optional($formData['atasan'])->approver)->nip ?: optional(optional($leaveRequest->user)->atasanLangsung)->nip ?: '-' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="section-table">
        <tr class="section-head"><td colspan="4">VIII. &nbsp; KEPUTUSAN PEJABAT YANG BERWENANG MEMBERIKAN CUTI **</td></tr>
        <tr>
            <td class="decision-label" width="13%">DISETUJUI</td>
            <td class="decision-label" width="23%">PERUBAHAN ****</td>
            <td class="decision-label" width="23%">DITANGGUHKAN ****</td>
            <td class="decision-label" width="41%">TIDAK DISETUJUI ****</td>
        </tr>
        <tr>
            <td class="decision-mark">{{ $statusPpk === 'approved' ? 'v' : '' }}</td>
            <td class="decision-mark"></td>
            <td class="decision-mark"></td>
            <td class="decision-mark">{{ $statusPpk === 'rejected' ? 'v' : '' }}</td>
        </tr>
        <tr>
            <td colspan="3" class="blank-area"></td>
            <td class="signature-cell">
                <div class="signature-inner">
                    @if(!empty($formData['ppkSignature']))<img class="signature-pad-img" src="{{ $formData['ppkSignature'] }}">@endif
                    <div>{{ optional(optional(optional($formData['ppk'])->approver)->jabatan)->nama ?: (optional(optional($formData['ppk'])->approver)->jabatan_keterangan ?: '-') }}</div>
                    <div class="signature-name">({{ optional(optional($formData['ppk'])->approver)->name ?: '-' }})</div>
                    <div>NIP. {{ optional(optional($formData['ppk'])->approver)->nip ?: '-' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="note">
        <strong>Catatan :</strong><br>
        * &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Coret yang tidak perlu<br>
        ** &nbsp;&nbsp;&nbsp;Pilih salah satu dengan memberikan tanda centang (v)<br>
        *** &nbsp;&nbsp;Diisi oleh pejabat yang menangani bidang kepegawaian sebelum PNS mengajukan cuti<br>
        **** Diberi tanda centang dan alasannya
    </div>
</div>
</body>
</html>

