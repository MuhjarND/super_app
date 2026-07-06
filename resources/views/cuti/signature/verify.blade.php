<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Tanda Tangan Elektronik Cuti</title>
    @include('partials.app-icons')
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: #f8fafc;
            color: #0f172a;
        }

        .page {
            max-width: 880px;
            margin: 0 auto;
            padding: 32px 18px 48px;
        }

        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .header {
            padding: 24px 24px 18px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .title {
            margin: 0 0 6px;
            font-size: 1.5rem;
            font-weight: 800;
        }

        .subtitle {
            margin: 0;
            color: #475569;
            font-size: 0.95rem;
        }

        .status {
            display: inline-flex;
            align-items: center;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 0.88rem;
            font-weight: 800;
            margin-top: 14px;
        }

        .status.valid {
            background: #dcfce7;
            color: #166534;
        }

        .status.invalid {
            background: #fee2e2;
            color: #991b1b;
        }

        .body {
            padding: 24px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px 18px;
        }

        .item {
            padding: 14px 16px;
            border-radius: 14px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }

        .label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 6px;
        }

        .value {
            font-size: 0.98rem;
            line-height: 1.45;
            word-break: break-word;
        }

        .note {
            margin-top: 18px;
            padding: 16px 18px;
            border-radius: 14px;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            color: #312e81;
            font-size: 0.92rem;
            line-height: 1.55;
        }

        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .page {
                padding: 18px 12px 28px;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <div class="header">
                <h1 class="title">Verifikasi Tanda Tangan Elektronik</h1>
                <p class="subtitle">PAPEDA</p>
                <div class="status {{ $data['valid'] ? 'valid' : 'invalid' }}">
                    {{ $data['valid'] ? 'Valid dan terverifikasi' : 'Tidak valid / belum final' }}
                </div>
            </div>
            <div class="body">
                <div class="grid">
                    <div class="item">
                        <span class="label">Nomor Dokumen</span>
                        <div class="value">{{ $data['document_number'] }}</div>
                    </div>
                    <div class="item">
                        <span class="label">Jenis Dokumen</span>
                        <div class="value">{{ $data['document_type'] }}</div>
                    </div>
                    <div class="item">
                        <span class="label">Jenis Cuti</span>
                        <div class="value">{{ $data['leave_type'] }}</div>
                    </div>
                    <div class="item">
                        <span class="label">Periode Cuti</span>
                        <div class="value">{{ $data['period'] }}</div>
                    </div>
                    <div class="item">
                        <span class="label">Tahap Approval</span>
                        <div class="value">{{ $data['role_label'] }}</div>
                    </div>
                    <div class="item">
                        <span class="label">Status Approval</span>
                        <div class="value">{{ $data['status'] }}</div>
                    </div>
                    <div class="item">
                        <span class="label">Penanda Tangan</span>
                        <div class="value">{{ $data['signer_name'] }}</div>
                    </div>
                    <div class="item">
                        <span class="label">Jabatan Penanda Tangan</span>
                        <div class="value">{{ $data['signer_title'] }}</div>
                    </div>
                    <div class="item">
                        <span class="label">Waktu Tanda Tangan</span>
                        <div class="value">{{ $data['acted_at'] }}</div>
                    </div>
                    <div class="item">
                        <span class="label">Validasi URL</span>
                        <div class="value">{{ $data['valid'] ? 'Tautan verifikasi sah' : 'Tautan verifikasi tidak sah' }}</div>
                    </div>
                </div>

                <div class="note">
                    @if($data['valid'])
                        Tanda tangan digital pada formulir cuti ini sah dan tercatat pada data approval final yang tersimpan di sistem. Halaman publik ini hanya menampilkan informasi verifikasi dokumen dan penanda tangan tanpa memuat data sensitif pegawai.
                    @else
                        Dokumen ini belum berada pada status final yang sah untuk tanda tangan elektronik, atau tautan verifikasinya tidak memenuhi syarat validasi.
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
