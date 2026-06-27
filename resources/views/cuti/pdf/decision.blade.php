<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
@page { size: A4 portrait; margin: 0.45cm 0.8cm 0.38cm 0.8cm; }
body { font-family: 'Times New Roman', DejaVu Serif, serif; font-size: 8.85px; color: #111; line-height: 1.1; }
body { margin: 0; }
.wrapper { width: 88.5%; margin: 0 auto; padding-top: 1.2px; }
.top-note { text-align: right; font-size: 7.9px; line-height: 1.14; margin-bottom: 1.8px; }
.date-line { text-align: right; font-size: 8.9px; margin-bottom: 3.5px; }
.to-line { text-align: center; font-size: 8.9px; line-height: 1.1; margin-bottom: 4.5px; }
.title { text-align: center; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 0.3px; letter-spacing: .05px; }
.number { text-align: center; font-size: 8.45px; margin-bottom: 4.2px; }
.section-table { width: 100%; border-collapse: collapse; margin-bottom: 5.8px; page-break-inside: avoid; }
.section-table td, .section-table th { border: 0.8px solid #111; padding: 1.45px 3px; vertical-align: middle; }
.section-head td { font-weight: bold; font-size: 9.55px; letter-spacing: .05px; padding-top: 1.55px; padding-bottom: 1.55px; }
.label-cell { font-weight: bold; text-transform: uppercase; }
.center { text-align: center; }
.checkbox { font-family: DejaVu Sans, sans-serif; font-size: 11.4px; font-weight: bold; line-height: 1; display: inline-block; }
.inner-table { width: 100%; border-collapse: collapse; }
.inner-table td, .inner-table th { border: 0.8px solid #111; padding: 1.35px 2.9px; vertical-align: middle; }
.inner-tight td { padding-top: 1.15px; padding-bottom: 1.15px; }
.paraf-box { text-align: center; vertical-align: top; }
.paraf-check { font-family: DejaVu Sans, sans-serif; font-size: 18px; font-weight: bold; line-height: 1; margin-top: 3px; }
.paraf-meta { font-size: 6.8px; line-height: 1.05; margin-top: 1px; }
.address-cell { vertical-align: top; height: 88px; }
.signature-cell { vertical-align: top; }
.signature-inner { text-align: center; font-size: 8.05px; line-height: 1.055; }
.signature-pad-img { height: 46px; max-width: 122px; margin: 1px auto 1px auto; display: block; object-fit: contain; }
.signature-name { font-weight: bold; }
.blank-area { height: 88px; }
.decision-label { text-align: center; font-size: 8px; text-transform: uppercase; }
.decision-mark { height: 10px; text-align: center; vertical-align: middle; font-family: DejaVu Sans, sans-serif; font-size: 11px; font-weight: bold; }
.note { font-size: 7.75px; margin-top: 2.5px; line-height: 1.08; }
.section-table.tight-bottom { margin-bottom: 5.8px; }
.type-label { width: 42%; padding-left: 6px !important; }
.type-mark { width: 8%; text-align: center; }
.compact-text { line-height: 0.98; }
.extra-signature-table { margin-bottom: 3.2px; }
.extra-signature-table td { text-align: center; vertical-align: top; }
.extra-signature-role { font-weight: bold; font-size: 7px; text-transform: uppercase; }
.extra-signature-img { height: 30px; max-width: 92px; display: block; margin: 0 auto; object-fit: contain; }
.extra-signature-name { font-weight: bold; font-size: 7px; }
.extra-signature-meta { font-size: 6.6px; line-height: 1.02; }
</style>
</head>
<body>
@include('partials.pdf-verification-badge', ['pdfVerification' => $pdfVerification ?? null])
@php
    $statusAtasan = optional($formData['atasan'])->status;
    $statusPpk = optional($formData['ppk'])->status;
    $contactPhone = $leaveRequest->contact_phone ?: optional($leaveRequest->user)->no_hp;
    $checkMark = '&#10003;';
@endphp
<div class="wrapper">
    <div class="top-note">Lampiran II : Surat Edaran Sekretaris Mahkamah Agung RI<br>Nomor 13 Tahun 2019</div>
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
            <td class="type-mark"><span class="checkbox">{!! $formData['selectedTypeCode'] === 'CT' ? $checkMark : '' !!}</span></td>
            <td class="type-label">2. CUTI BESAR</td>
            <td class="type-mark"><span class="checkbox">{!! $formData['selectedTypeCode'] === 'CB' ? $checkMark : '' !!}</span></td>
        </tr>
        <tr>
            <td class="type-label">3. CUTI SAKIT</td>
            <td class="type-mark"><span class="checkbox">{!! $formData['selectedTypeCode'] === 'CS' ? $checkMark : '' !!}</span></td>
            <td class="type-label">4. CUTI MELAHIRKAN</td>
            <td class="type-mark"><span class="checkbox">{!! $formData['selectedTypeCode'] === 'CM' ? $checkMark : '' !!}</span></td>
        </tr>
        <tr>
            <td class="type-label">5. CUTI KARENA ALASAN PENTING</td>
            <td class="type-mark"><span class="checkbox">{!! $formData['selectedTypeCode'] === 'CAP' ? $checkMark : '' !!}</span></td>
            <td class="type-label">6. CUTI DI LUAR TANGGUNGAN NEGARA</td>
            <td class="type-mark"><span class="checkbox">{!! $formData['selectedTypeCode'] === 'CLTN' ? $checkMark : '' !!}</span></td>
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
                    <div class="paraf-check">{!! $checkMark !!}</div>
                    <div class="paraf-meta">{{ $formData['verifierParaf']['name'] }}<br>{{ $formData['verifierParaf']['acted_at'] }}</div>
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
            <td width="59%" rowspan="2" class="address-cell">
                {{ $leaveRequest->leave_address ?: '-' }}
                @if($leaveRequest->is_abroad)
                    <br>Negara tujuan: {{ $leaveRequest->abroad_country ?: '-' }}
                @endif
            </td>
            <td width="7%">Telp.</td>
            <td width="34%">{{ $contactPhone ?: '-' }}</td>
        </tr>
        <tr>
            <td colspan="2" class="signature-cell" style="height:66px;">
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
            <td class="decision-mark">{!! $statusAtasan === 'approved' ? $checkMark : '' !!}</td>
            <td class="decision-mark">{!! $statusAtasan === 'changed' ? $checkMark : '' !!}</td>
            <td class="decision-mark">{!! $statusAtasan === 'deferred' ? $checkMark : '' !!}</td>
            <td class="decision-mark">{!! $statusAtasan === 'rejected' ? $checkMark : '' !!}</td>
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
            <td class="decision-mark">{!! $statusPpk === 'approved' ? $checkMark : '' !!}</td>
            <td class="decision-mark">{!! $statusPpk === 'changed' ? $checkMark : '' !!}</td>
            <td class="decision-mark">{!! $statusPpk === 'deferred' ? $checkMark : '' !!}</td>
            <td class="decision-mark">{!! $statusPpk === 'rejected' ? $checkMark : '' !!}</td>
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

    @if(($formData['additionalApprovalSignatures'] ?? collect())->isNotEmpty())
        <table class="section-table extra-signature-table">
            <tr class="section-head"><td colspan="{{ $formData['additionalApprovalSignatures']->count() }}">TANDA TANGAN APPROVAL TAMBAHAN</td></tr>
            <tr>
                @foreach($formData['additionalApprovalSignatures'] as $signature)
                    <td>
                        <div class="extra-signature-role">{{ $signature['role_label'] }}</div>
                        @if(!empty($signature['signature']))<img class="extra-signature-img" src="{{ $signature['signature'] }}">@endif
                        <div class="extra-signature-meta">{{ $signature['title'] }}</div>
                        <div class="extra-signature-name">({{ $signature['name'] }})</div>
                        <div class="extra-signature-meta">NIP. {{ $signature['nip'] }}<br>{{ $signature['acted_at'] }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    <div class="note">
        <strong>Catatan :</strong><br>
        * &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Coret yang tidak perlu<br>
        ** &nbsp;&nbsp;&nbsp;Pilih salah satu dengan memberikan tanda centang<br>
        *** &nbsp;&nbsp;Diisi oleh pejabat yang menangani bidang kepegawaian sebelum PNS mengajukan cuti<br>
        **** Diberi tanda centang dan alasannya
    </div>
</div>
</body>
</html>

