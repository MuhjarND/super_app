<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Kehadiran - PAPEDA</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f5f7ff; color: #172033; font-family: DejaVu Sans, Arial, sans-serif; }
        .page { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { width: 100%; max-width: 620px; background: #fff; border: 1px solid #dfe5f4; border-radius: 22px; box-shadow: 0 18px 50px rgba(58, 49, 128, .12); overflow: hidden; }
        .head { padding: 24px 28px; color: #fff; background: linear-gradient(135deg, #4f46e5, #7c3aed); }
        .head h1 { margin: 0 0 6px; font-size: 22px; }
        .head p { margin: 0; opacity: .86; font-size: 14px; }
        .body { padding: 26px 28px; }
        .valid { display: inline-block; margin-bottom: 20px; padding: 8px 13px; border-radius: 999px; background: #dcfce7; color: #166534; font-weight: 700; font-size: 13px; }
        dl { margin: 0; display: grid; grid-template-columns: 165px 1fr; gap: 12px 18px; }
        dt { color: #64748b; font-size: 13px; }
        dd { margin: 0; font-weight: 700; font-size: 14px; overflow-wrap: anywhere; }
        @media (max-width: 560px) { .page { padding: 12px; } .head, .body { padding: 20px; } dl { grid-template-columns: 1fr; gap: 4px; } dd { margin-bottom: 10px; } }
    </style>
</head>
<body>
<main class="page">
    <section class="card">
        <header class="head">
            <h1>Validasi Kehadiran Rapat</h1>
            <p>PAPEDA - PTA Papua Barat</p>
        </header>
        <div class="body">
            <div class="valid">Data valid dan tercatat</div>
            <dl>
                <dt>Nama peserta</dt><dd>{{ $attendance->participant_name_snapshot ?: optional($attendance->user)->name ?: '-' }}</dd>
                <dt>Jabatan / instansi</dt><dd>{{ $attendance->participant_jabatan_snapshot ?: $attendance->guest_instansi ?: optional(optional($attendance->user)->jabatan)->nama ?: '-' }}</dd>
                <dt>Agenda rapat</dt><dd>{{ optional($attendance->rapat)->judul ?: '-' }}</dd>
                <dt>Waktu kehadiran</dt><dd>{{ $attendance->attended_at ? $attendance->attended_at->copy()->timezone('Asia/Jayapura')->translatedFormat('d F Y H:i') . ' WIT' : '-' }}</dd>
                <dt>Jenis peserta</dt><dd>{{ $attendance->attendance_type === 'guest' ? 'Peserta eksternal' : 'Peserta internal' }}</dd>
            </dl>
        </div>
    </section>
</main>
</body>
</html>
