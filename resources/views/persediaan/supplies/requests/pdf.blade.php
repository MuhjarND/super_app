<!doctype html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Formulir Permintaan ATK</title>
    <style>
        @page { margin: 13mm 12mm 12mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #111827;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10px;
        }
        .sheet {
            min-height: 271mm;
            page-break-after: always;
            position: relative;
        }
        .sheet:last-child { page-break-after: auto; }
        .kop {
            display: block;
            width: 100%;
            height: auto;
            margin-bottom: 8px;
        }
        .title {
            margin: 3px 0 7px;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: .2px;
        }
        .number {
            margin: 0 0 3px 2px;
            font-size: 10px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .request-table th,
        .request-table td {
            border: 1px solid #111827;
            padding: 4px 5px;
            vertical-align: middle;
        }
        .request-table th {
            height: 25px;
            text-align: center;
            font-size: 9.5px;
            font-weight: bold;
        }
        .request-table td {
            height: 25px;
            font-size: 9px;
        }
        .request-table .no { width: 9%; text-align: center; }
        .request-table .item { width: 37%; }
        .request-table .qty { width: 24%; text-align: center; }
        .request-table .purpose { width: 30%; }
        .signature {
            width: 42%;
            margin: 24px 0 0 auto;
            text-align: center;
            font-size: 10px;
        }
        .signature .place-date { margin-bottom: 3px; }
        .signature .label { margin-bottom: 33px; }
        .signature .line {
            border-bottom: 1px solid #111827;
            height: 1px;
            margin: 0 10px 4px;
        }
        .signature .name { font-weight: bold; }
        .signature .nip { margin-top: 2px; }
    </style>
</head>
<body>
@foreach($requests as $request)
    @php
        $items = $request->items instanceof \Illuminate\Support\Collection
            ? $request->items->values()
            : collect($request->items ?: [])->values();
        $rowCount = max(12, $items->count());
        $requestDate = $request->submitted_at ?: $request->created_at;
        $requestDateLabel = $requestDate
            ? \Carbon\Carbon::parse($requestDate)->locale('id')->translatedFormat('d F Y')
            : '-';
    @endphp
    <section class="sheet">
        <img class="kop" src="{{ $kopImage }}" alt="Kop Pengadilan Tinggi Agama Papua Barat">
        <div class="title">FORMULIR PERMINTAAN ALAT TULIS KANTOR (ATK)</div>
        <div class="number">NOMOR : {{ $request->request_number }}</div>

        <table class="request-table">
            <thead>
                <tr>
                    <th class="no">NO</th>
                    <th class="item">JENIS BARANG</th>
                    <th class="qty">JUMLAH</th>
                    <th class="purpose">KEPERLUAN</th>
                </tr>
            </thead>
            <tbody>
                @for($index = 0; $index < $rowCount; $index++)
                    @php($item = $items->get($index))
                    <tr>
                        <td class="no">{{ $item ? $index + 1 : '' }}</td>
                        <td>{{ $item ? $item->item_name_snapshot : '' }}</td>
                        <td class="qty">{{ $item ? number_format((int) $item->quantity_requested, 0, ',', '.') . ' ' . $item->unit_snapshot : '' }}</td>
                        <td>{{ $item ? $request->purpose : '' }}</td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <div class="signature">
            <div class="place-date">Manokwari, {{ $requestDateLabel }}</div>
            <div class="label">Pemohon</div>
            <div class="line"></div>
            <div class="name">{{ optional($request->requester)->name ?: '-' }}</div>
            @if(optional($request->requester)->nip)
                <div class="nip">NIP. {{ $request->requester->nip }}</div>
            @endif
        </div>
    </section>
@endforeach
</body>
</html>
