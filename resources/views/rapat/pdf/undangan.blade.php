<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Undangan Rapat</title>
    <style>
        @page {
            size: A4 portrait;
            margin-top: 2cm;
            margin-bottom: 2cm;
            margin-left: 2.5cm;
            margin-right: 2.5cm;
        }

        body {
            margin: 0;
            color: #000;
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.35;
        }

        p {
            margin: 0 0 10pt 0;
        }

        .kop {
            margin-bottom: 12pt;
        }

        .kop img {
            width: 100%;
            display: block;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10pt;
        }

        .meta td {
            vertical-align: top;
        }

        .meta-left {
            width: 72%;
        }

        .meta-right {
            width: 28%;
            text-align: right;
            white-space: nowrap;
            font-size: 11pt;
        }

        .meta-table,
        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table td,
        .detail-table td {
            vertical-align: top;
            padding: 0;
        }

        .meta-table td:first-child {
            width: 62pt;
        }

        .detail-table td:first-child {
            width: 120pt;
        }

        .meta-table td:nth-child(2),
        .detail-table td:nth-child(2) {
            width: 12pt;
        }

        .nomor-undangan-value {
            white-space: nowrap;
            font-size: 10.5pt;
            letter-spacing: -0.1pt;
        }

        .hal-value {
            line-height: 1.2;
            padding: 0;
        }

        .institution-name {
            white-space: nowrap;
        }

        .tujuan {
            margin: 8pt 0 8pt 0;
            line-height: 1.15;
        }

        .tujuan > div {
            margin: 0;
            padding: 0;
        }

        .recipient-destination {
            font-weight: bold;
        }

        .recipient-inline {
            margin-left: 22pt;
        }

        .salam {
            font-style: normal;
            margin-bottom: 12pt;
        }

        .paragraf {
            text-align: justify;
            text-indent: 28pt;
            margin-bottom: 10pt;
        }

        .detail-wrap {
            margin-left: 30pt;
            margin-bottom: 12pt;
        }

        .penutup {
            margin-top: 4pt;
            text-align: justify;
            text-indent: 28pt;
        }

        .ttd-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16pt;
        }

        .ttd-table td {
            vertical-align: top;
        }

        .ttd-box {
            width: 240pt;
            margin-left: auto;
            text-align: left;
            page-break-inside: avoid;
        }

        .signature-pad-image {
            width: 64pt;
            height: 64pt;
            margin: 4pt 0 3pt;
            text-align: left;
            page-break-inside: avoid;
        }

        .signature-pad-image img {
            width: 64pt;
            height: 64pt;
            display: block;
            margin: 0;
            object-fit: contain;
        }

        .nama-ttd {
            font-weight: bold;
            font-size: 12pt;
            position: relative;
            z-index: 1;
        }

        .tembusan {
            margin-top: 14pt;
            font-size: 11pt;
            page-break-inside: avoid;
        }

        .lampiran-page {
            page-break-before: always;
        }

        .lampiran-header {
            margin-bottom: 18pt;
        }

        .lampiran-heading {
            font-weight: bold;
            margin-bottom: 8pt;
        }

        .lampiran-meta-line {
            margin: 0;
            line-height: 1.25;
        }

        .lampiran-title {
            text-align: center;
            font-weight: bold;
            margin: 22pt 0 22pt;
        }

        .lampiran-list {
            margin: 0;
            padding-left: 24pt;
        }

        .lampiran-list li {
            margin-bottom: 2pt;
        }
    </style>
</head>
<body>
@include('partials.pdf-verification-badge', [
    'pdfVerification' => $pdfVerification ?? null,
    'pdfVerificationBottom' => '-1.4cm',
])
    @php
        $recipientSummary = $displayRecipients->map(function ($recipient) {
            return $recipient->jabatan_keterangan ?: optional($recipient->jabatan)->nama;
        })->filter()->unique()->implode(', ');
        $agendaUndangan = trim((string) ($rapat->deskripsi ?: $rapat->judul));
        $tanggalSuratIndonesia = ucfirst($issueDate->locale('id')->isoFormat('D MMMM Y'));
        $tanggalRapatIndonesia = ucfirst($rapat->tanggal->copy()->locale('id')->isoFormat('dddd, D MMMM Y'));
        $lampiranDaftar = $displayRecipients->map(function ($recipient) {
            return $recipient->jabatan_keterangan ?: optional($recipient->jabatan)->nama ?: $recipient->name;
        })->filter()->unique()->values();
        $signatoryLampiranTitle = trim(rtrim($signatoryTitle['line1'], ',')) . ' ' . trim($signatoryTitle['line2']);
        $signatoryName = \App\Support\PersonNameFormatter::withoutTitles(optional($signatory)->name)
            ?: '(menunggu approval 1)';
        $institutionName = 'Pengadilan Tinggi Agama Papua Barat';
        $keepInstitutionTogether = function ($value) use ($institutionName) {
            return str_replace(
                e($institutionName),
                str_replace(' ', '&nbsp;', e($institutionName)),
                e((string) $value)
            );
        };
        $invitationSubject = preg_replace('/\s+/u', ' ', trim((string) $rapat->judul));
        $invitationSubjectLength = mb_strlen('Undangan ' . $invitationSubject);
        $invitationSubjectFontSize = $invitationSubjectLength > 145
            ? max(7.5, round(11 * 145 / $invitationSubjectLength, 1))
            : 11;
        $isSatkerInvitation = $isSatkerInvitation ?? false;
        $penerimaSatker = $penerimaSatker ?? '';
        if ($tujuanManual) {
            $recipientDestination = trim(
                ($isSatkerInvitation && !empty($penerimaSatker) ? $penerimaSatker . ' ' : '') . $tujuanSurat
            );
        } elseif ($singleRecipient) {
            $recipient = $displayRecipients->first();
            $recipientDestination = $recipient->name
                . ($recipient->jabatan_keterangan ? ', ' . $recipient->jabatan_keterangan : '');
        } else {
            $recipientDestination = 'Para Pejabat dan Pegawai (terlampir)';
        }
    @endphp

    @if($kopImage)
        <div class="kop">
            <img src="{{ $kopImage }}" alt="Kop Surat">
        </div>
    @endif

    <table class="meta">
        <tr>
            <td class="meta-left">
                <table class="meta-table">
                    <tr>
                        <td>Nomor</td>
                        <td>:</td>
                        <td class="nomor-undangan-value">{{ $nomorUndangan ?? $rapat->nomor_undangan }}</td>
                    </tr>
                    <tr>
                        <td>Sifat</td>
                        <td>:</td>
                        <td>{{ $rapat->sifat_surat_label ?: 'Biasa' }}</td>
                    </tr>
                    <tr>
                        <td>Lampiran</td>
                        <td>:</td>
                        <td>{{ $lampiranLabel }}</td>
                    </tr>
                </table>
            </td>
            <td class="meta-right">Manokwari, {{ $tanggalSuratIndonesia }}</td>
        </tr>
        <tr>
            <td colspan="2" class="hal-value">
                <table class="meta-table">
                    <tr>
                        <td>Hal</td>
                        <td>:</td>
                        <td style="font-size: {{ $invitationSubjectFontSize }}pt;">Undangan {!! $keepInstitutionTogether($invitationSubject) !!}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="tujuan">
        <div class="recipient-destination">Yth. {!! nl2br($keepInstitutionTogether($recipientDestination)) !!}</div>
        @if(!$tujuanManual && !$singleRecipient && $showRecipientListInLetter && $recipientSummary)
            <div class="recipient-inline">{!! $keepInstitutionTogether($recipientSummary) !!}</div>
        @endif
        <div>di</div>
        <div>Tempat</div>
    </div>

    <p class="salam">Assalamu'alaikum warahmatullahi wabarakatuh.</p>

    <p class="paragraf">{!! nl2br($keepInstitutionTogether($openingParagraph)) !!}</p>

    <div class="detail-wrap">
        <table class="detail-table">
            <tr>
                <td>Hari, Tanggal</td>
                <td>:</td>
                <td>{{ $tanggalRapatIndonesia }}</td>
            </tr>
            <tr>
                <td>Waktu</td>
                <td>:</td>
                <td>{{ $rapat->waktu_mulai_formatted }} WIT s/d Selesai</td>
            </tr>
            <tr>
                <td>Tempat</td>
                <td>:</td>
                <td>{!! $keepInstitutionTogether($rapat->tempat) !!}</td>
            </tr>
            @if($rapat->jenis_pakaian)
                <tr>
                    <td>Pakaian</td>
                    <td>:</td>
                    <td>{{ $rapat->jenis_pakaian }}</td>
                </tr>
            @endif
            @if($rapat->is_virtual)
                <tr>
                    <td>Meeting ID</td>
                    <td>:</td>
                    <td>{{ $rapat->meeting_id ?: '-' }}</td>
                </tr>
                <tr>
                    <td>Passcode</td>
                    <td>:</td>
                    <td>{{ $rapat->meeting_passcode ?: '-' }}</td>
                </tr>
            @endif
            <tr>
                <td>Agenda</td>
                <td>:</td>
                <td>{{ $agendaUndangan }}</td>
            </tr>
        </table>
    </div>

    <p class="penutup">Sehubungan dengan hal tersebut, dimohon kehadiran Saudara tepat pada waktunya.</p>
    <p class="penutup">Demikian undangan ini disampaikan, atas perhatian dan kehadiran Saudara diucapkan terima kasih.</p>
    <p class="salam">Wassalamu'alaikum warahmatullahi wabarakatuh.</p>

    <table class="ttd-table">
        <tr>
            <td style="width: 52%;"></td>
            <td>
                <div class="ttd-box">
                    <div>{{ $signatoryTitle['line1'] }}</div>
                    <div><strong>{!! $keepInstitutionTogether($signatoryTitle['line2']) !!}</strong></div>
                    @if(!empty($signatureImage) && $signatureApprovedAt)
                        <div class="signature-pad-image">
                            <img src="{{ $signatureImage }}" alt="QR tanda tangan elektronik">
                        </div>
                    @else
                        <div style="height: 68pt;"></div>
                    @endif
                    <div class="nama-ttd">{{ $signatoryName }}</div>
                </div>
            </td>
        </tr>
    </table>

    @if($showTembusan)
        <div class="tembusan">
            <div>Tembusan:</div>
            <div>Yth. Ketua <span class="institution-name">Pengadilan Tinggi Agama Papua Barat</span> (sebagai laporan)</div>
        </div>
    @endif

    @if($showLampiranPage)
        <div class="lampiran-page"></div>

        <div class="lampiran-header">
            <div class="lampiran-heading">LAMPIRAN</div>
            <p class="lampiran-meta-line">Surat Undangan {!! $keepInstitutionTogether($signatoryLampiranTitle) !!}</p>
            <p class="lampiran-meta-line">Nomor : {{ $nomorUndangan ?? $rapat->nomor_undangan }}</p>
            <p class="lampiran-meta-line">Tanggal : {{ $tanggalSuratIndonesia }}</p>
        </div>

        <div class="lampiran-title">DAFTAR PEJABAT/PEGAWAI YANG DIUNDANG</div>

        <ol class="lampiran-list">
            @foreach($lampiranDaftar as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ol>

        <table class="ttd-table" style="margin-top: 36pt;">
            <tr>
                <td style="width: 52%;"></td>
                <td>
                    <div class="ttd-box">
                        <div>{{ $signatoryTitle['line1'] }}</div>
                        <div><strong>{!! $keepInstitutionTogether($signatoryTitle['line2']) !!}</strong></div>
                        @if(!empty($signatureImage) && $signatureApprovedAt)
                            <div class="signature-pad-image">
                                <img src="{{ $signatureImage }}" alt="QR tanda tangan elektronik">
                            </div>
                        @else
                            <div style="height: 68pt;"></div>
                        @endif
                        <div class="nama-ttd">{{ $signatoryName }}</div>
                    </div>
                </td>
            </tr>
        </table>
    @endif
</body>
</html>
