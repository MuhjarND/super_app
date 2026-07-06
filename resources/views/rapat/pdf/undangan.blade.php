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
            width: 65%;
        }

        .meta-right {
            width: 35%;
            text-align: right;
            white-space: nowrap;
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
            width: 80pt;
        }

        .detail-table td:first-child {
            width: 120pt;
        }

        .meta-table td:nth-child(2),
        .detail-table td:nth-child(2) {
            width: 14pt;
        }

        .tujuan {
            margin: 8pt 0 8pt 0;
            line-height: 1.15;
        }

        .tujuan > div {
            margin: 0;
            padding: 0;
        }

        .recipient-inline {
            margin-left: 22pt;
        }

        .salam {
            font-style: italic;
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
        }

        .signature-pad-image {
            margin: 0 0 -12pt 0;
        }

        .signature-pad-image img {
            width: 138pt;
            height: 70pt;
            display: block;
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
@include('partials.pdf-verification-badge', ['pdfVerification' => $pdfVerification ?? null])
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
                        <td>{{ $rapat->nomor_undangan }}</td>
                    </tr>
                    <tr>
                        <td>Lampiran</td>
                        <td>:</td>
                        <td>{{ $lampiranLabel }}</td>
                    </tr>
                    <tr>
                        <td>Hal</td>
                        <td>:</td>
                        <td>Undangan</td>
                    </tr>
                </table>
            </td>
            <td class="meta-right">Manokwari, {{ $tanggalSuratIndonesia }}</td>
        </tr>
    </table>

    <div class="tujuan">
        <div>Kepada Yth.</div>
        @if($tujuanManual)
            <div>{!! nl2br(e($rapat->tujuan_surat)) !!}</div>
        @elseif($singleRecipient)
            @php $recipient = $displayRecipients->first(); @endphp
            <div>{{ $recipient->name }}{{ $recipient->jabatan_keterangan ? ', ' . $recipient->jabatan_keterangan : '' }}</div>
        @else
            <div>Para Pejabat dan Pegawai (terlampir)</div>
            @if($showRecipientListInLetter && $recipientSummary)
                <div class="recipient-inline">{{ $recipientSummary }}</div>
            @endif
        @endif
        <div>di</div>
        <div>Tempat</div>
    </div>

    <p class="salam">Assalamu'alaikum Wr.Wb.</p>

    <p class="paragraf">
        Dalam rangka pelaksanaan <strong>{{ $rapat->judul }}</strong> di lingkungan Pengadilan Tinggi Agama Papua Barat,
        dengan ini kami mengharapkan kehadiran Saudara pada kegiatan dimaksud yang akan dilaksanakan pada:
    </p>

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
                <td>{{ $rapat->tempat }}</td>
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
    <p class="salam">Wassalamu'alaikum Wr.Wb.</p>

    <table class="ttd-table">
        <tr>
            <td style="width: 52%;"></td>
            <td>
                <div class="ttd-box">
                    <div>{{ $signatoryTitle['line1'] }}</div>
                    <div><strong>{{ $signatoryTitle['line2'] }}</strong></div>
                    @if(!empty($signatureImage) && $signatureApprovedAt)
                        <div class="signature-pad-image">
                            <img src="{{ $signatureImage }}" alt="Tanda Tangan Digital">
                        </div>
                    @else
                        <div style="height: 68pt;"></div>
                    @endif
                    <div class="nama-ttd">{{ optional($signatory)->name ?? '(menunggu approval 1)' }}</div>
                </div>
            </td>
        </tr>
    </table>

    @if($showTembusan)
        <div class="tembusan">
            <div>Tembusan:</div>
            <div>Yth. Ketua Pengadilan Tinggi Agama Papua Barat (sebagai laporan)</div>
        </div>
    @endif

    @if($showLampiranPage)
        <div class="lampiran-page"></div>

        <div class="lampiran-header">
            <div class="lampiran-heading">LAMPIRAN</div>
            <p class="lampiran-meta-line">Surat Undangan {{ $signatoryLampiranTitle }}</p>
            <p class="lampiran-meta-line">Nomor : {{ $rapat->nomor_undangan }}</p>
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
                        <div><strong>{{ $signatoryTitle['line2'] }}</strong></div>
                        @if(!empty($signatureImage) && $signatureApprovedAt)
                            <div class="signature-pad-image">
                                <img src="{{ $signatureImage }}" alt="Tanda Tangan Digital">
                            </div>
                        @else
                            <div style="height: 68pt;"></div>
                        @endif
                        <div class="nama-ttd">{{ optional($signatory)->name ?? '(menunggu approval 1)' }}</div>
                    </div>
                </td>
            </tr>
        </table>
    @endif
</body>
</html>
