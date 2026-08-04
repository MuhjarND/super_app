<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 10mm 12mm;
        }

        body {
            margin: 0;
            color: #111111;
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.2pt;
            line-height: 1.22;
        }

        .report-heading {
            margin: 0 0 10pt;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
        }

        .report-heading div {
            margin: 0 0 2pt;
        }

        .report-heading .institution,
        .report-heading .period {
            font-size: 10pt;
        }

        .report-heading .title {
            font-size: 10.5pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th,
        td {
            border: 0.6pt solid #777777;
            padding: 5pt 6pt;
            vertical-align: middle;
            word-wrap: break-word;
        }

        th {
            background: #898989;
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            text-align: left;
        }

        .center {
            text-align: center;
        }

        .detail {
            display: block;
            margin-bottom: 2pt;
            font-style: italic;
        }

        .status-detail {
            display: block;
            margin-top: 2pt;
            font-style: normal;
        }

        .empty {
            padding: 20pt;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="report-heading">
        <div class="title">{{ $title }}</div>
        <div class="institution">PENGADILAN TINGGI AGAMA PAPUA BARAT</div>
        <div class="period">{{ $periodLabel }}</div>
    </div>

    @if($type === 'masuk')
        <table>
            <thead>
                <tr>
                    <th class="center" style="width:4%;">No.</th>
                    <th style="width:21%;">Nomor Surat</th>
                    <th style="width:24%;">Pengirim</th>
                    <th style="width:9%;">Tanggal</th>
                    <th style="width:21%;">Perihal</th>
                    <th style="width:8%;">Status</th>
                    <th style="width:13%;">Dibuat Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="center">{{ $row['number'] }}</td>
                        <td>
                            <span class="detail">{{ $row['classification'] }}</span>
                            <div>{{ $row['letter_number'] }}</div>
                            <span class="detail">{{ $row['nature'] }}</span>
                        </td>
                        <td>
                            <span class="detail">{{ $row['sender_type'] }}</span>
                            <div>{{ $row['sender'] }}</div>
                        </td>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['subject'] }}</td>
                        <td>
                            <span class="detail">{{ $row['status'] }}</span>
                            <span class="status-detail">{{ $row['file_status'] }}</span>
                        </td>
                        <td>{{ $row['creator'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">Tidak ada surat masuk pada rentang tanggal yang dipilih.</td></tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table>
            <thead>
                <tr>
                    <th class="center" style="width:4%;">No.</th>
                    <th style="width:26%;">Nomor Surat</th>
                    <th style="width:12%;">Tujuan</th>
                    <th style="width:9%;">Tanggal</th>
                    <th style="width:22%;">Perihal</th>
                    <th style="width:13%;">Status</th>
                    <th style="width:14%;">Dibuat Oleh</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="center">{{ $row['number'] }}</td>
                        <td>{{ $row['letter_number'] }}</td>
                        <td>{{ $row['recipient'] }}</td>
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['subject'] }}</td>
                        <td>
                            <span class="detail">{{ $row['status'] }}</span>
                            <span class="status-detail">{{ $row['file_status'] }}</span>
                        </td>
                        <td>{{ $row['creator'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">Tidak ada surat keluar pada rentang tanggal yang dipilih.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
</body>
</html>
