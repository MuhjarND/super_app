<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Absensi Rapat | PTA Papua Barat</title>
    @include('partials.app-icons')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --line: #dbe2ea;
            --text: #0f172a;
            --muted: #64748b;
            --primary: #4338ca;
            --primary-soft: #e0e7ff;
            --success: #166534;
            --success-soft: #dcfce7;
            --danger: #b91c1c;
            --danger-soft: #fee2e2;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(180deg, #eef2ff 0%, var(--bg) 220px);
            color: var(--text);
        }

        .container {
            max-width: 980px;
            margin: 0 auto;
            padding: 20px 16px 32px;
        }

        .hero {
            background: linear-gradient(135deg, #0f172a, #4338ca);
            color: #fff;
            border-radius: 24px;
            padding: 22px 20px;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18);
        }

        .hero__badge {
            display: inline-flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.4px;
        }

        .hero h1 {
            margin: 12px 0 6px;
            font-size: 1.55rem;
            line-height: 1.2;
        }

        .hero p {
            margin: 0;
            color: rgba(255, 255, 255, 0.84);
            font-size: 0.9rem;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 16px;
        }

        .meta-box {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 14px;
            padding: 12px 14px;
        }

        .meta-box__label {
            font-size: 0.72rem;
            color: rgba(255, 255, 255, 0.72);
            text-transform: uppercase;
        }

        .meta-box__value {
            font-size: 0.92rem;
            font-weight: 700;
            margin-top: 6px;
        }

        .panel {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 22px;
            padding: 18px;
            margin-top: 18px;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
        }

        .tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 16px;
        }

        .tab-button {
            border: 1px solid var(--line);
            background: #fff;
            color: var(--muted);
            border-radius: 14px;
            padding: 12px 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .tab-button.active {
            background: var(--primary-soft);
            color: var(--primary);
            border-color: #e0e7ff;
        }

        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .field {
            margin-bottom: 14px;
        }

        .input,
        .select {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 0.92rem;
            background: #fff;
        }

        .hint {
            font-size: 0.78rem;
            color: var(--muted);
            margin-top: 6px;
        }

        .signature-wrap {
            border: 1px solid #cbd5e1;
            border-radius: 18px;
            overflow: hidden;
            background: #fff;
        }

        .signature-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .signature-canvas {
            width: 100%;
            height: 220px;
            display: block;
            background: #fff;
            touch-action: none;
        }

        .btn {
            border: 0;
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 0.92rem;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #334155;
        }

        .alert {
            border-radius: 14px;
            padding: 12px 14px;
            font-size: 0.88rem;
            margin-bottom: 14px;
            display: none;
        }

        .alert.show { display: block; }
        .alert-success { background: var(--success-soft); color: var(--success); }
        .alert-error { background: var(--danger-soft); color: var(--danger); }

        .public-loader {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(3px);
            z-index: 9999;
            padding: 20px;
        }

        .public-loader.show {
            display: flex;
        }

        .public-loader__card {
            width: min(100%, 320px);
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(219, 226, 234, 0.9);
            border-radius: 20px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.2);
            text-align: center;
            padding: 24px 20px;
        }

        .public-loader__spinner {
            width: 42px;
            height: 42px;
            margin: 0 auto 14px;
            border-radius: 50%;
            border: 3px solid #e0e7ff;
            border-top-color: var(--primary);
            animation: spin 0.8s linear infinite;
        }

        .public-loader__title {
            font-size: 0.96rem;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .public-loader__message {
            font-size: 0.84rem;
            color: var(--muted);
            line-height: 1.5;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 640px) {
            .hero h1 { font-size: 1.3rem; }
            .panel { padding: 16px; }
            .signature-canvas { height: 200px; }
        }
    </style>
</head>
<body>
    <div id="publicLoader" class="public-loader" aria-hidden="true">
        <div class="public-loader__card">
            <div class="public-loader__spinner"></div>
            <div class="public-loader__title">Memproses Absensi</div>
            <div id="publicLoaderMessage" class="public-loader__message">Mohon tunggu, data absensi sedang dikirim.</div>
        </div>
    </div>

    @php
        $attendedUserIds = $rapat->internalAttendances->pluck('user_id')->filter()->map(function ($id) {
            return (int) $id;
        })->all();
        $availableParticipants = $rapat->pesertas->reject(function ($peserta) use ($attendedUserIds) {
            return in_array((int) $peserta->id, $attendedUserIds, true);
        })->values();
    @endphp

    <div class="container">
        <div class="hero">
            <div class="hero__badge">Absensi Publik Rapat</div>
            <h1>{{ $rapat->judul }}</h1>
            <p>{{ $rapat->nomor_undangan }}</p>

            <div class="meta-grid">
                <div class="meta-box">
                    <div class="meta-box__label">Tanggal</div>
                    <div class="meta-box__value">{{ optional($rapat->tanggal)->translatedFormat('d F Y') }}</div>
                </div>
                <div class="meta-box">
                    <div class="meta-box__label">Waktu</div>
                    <div class="meta-box__value">{{ $rapat->waktu_mulai_formatted }} WIT</div>
                </div>
                <div class="meta-box">
                    <div class="meta-box__label">Tempat</div>
                    <div class="meta-box__value">{{ $rapat->tempat }}</div>
                </div>
                <div class="meta-box">
                    <div class="meta-box__label">Kategori</div>
                    <div class="meta-box__value">{{ $rapat->kategori_surat_label }}</div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div id="attendanceAlert" class="alert"></div>

            <div class="tabs">
                <button type="button" class="tab-button active" data-tab="internal">Peserta Undangan</button>
                <button type="button" class="tab-button" data-tab="guest">External</button>
            </div>

            <div id="panel-internal" class="tab-panel active">
                <form id="internalAttendanceForm">
                    <div class="field">
                        <label for="user_id">Nama Peserta</label>
                        <select name="user_id" id="user_id" class="select" required {{ $availableParticipants->isEmpty() ? 'disabled' : '' }}>
                            <option value="">-- Pilih Nama Peserta --</option>
                            @foreach($availableParticipants as $peserta)
                                <option value="{{ $peserta->id }}">
                                    {{ $peserta->name }}
                                </option>
                            @endforeach
                        </select>
                        @if($availableParticipants->isEmpty())
                            <div class="hint">Semua peserta undangan sudah melakukan absensi.</div>
                        @else
                            <div class="hint">Nama yang masih tersedia pada daftar berarti belum melakukan absensi.</div>
                        @endif
                    </div>

                    <div class="field">
                        <div style="border:1px solid #dbe4ff;background:#eef2ff;border-radius:16px;padding:12px 14px;color:#334155;font-size:.86rem;font-weight:600;">
                            Kehadiran akan tercatat secara elektronik beserta tanggal dan waktu absensi.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" {{ $availableParticipants->isEmpty() ? 'disabled' : '' }}>Kirim Absensi Peserta</button>
                </form>
            </div>

            <div id="panel-guest" class="tab-panel">
                <form id="guestAttendanceForm">
                    <div class="field">
                        <label for="guest_name">Nama External</label>
                        <input type="text" name="guest_name" id="guest_name" class="input" required>
                    </div>

                    <div class="field">
                        <label for="guest_instansi">Instansi / Jabatan</label>
                        <input type="text" name="guest_instansi" id="guest_instansi" class="input">
                    </div>

                    <div class="field">
                        <div style="border:1px solid #dbe4ff;background:#eef2ff;border-radius:16px;padding:12px 14px;color:#334155;font-size:.86rem;font-weight:600;">
                            Kehadiran peserta eksternal akan dicatat secara elektronik beserta tanggal dan waktu absensi.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Kirim Absensi External</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const tabButtons = document.querySelectorAll('.tab-button');
        const alertBox = document.getElementById('attendanceAlert');
        const publicLoader = document.getElementById('publicLoader');
        const publicLoaderMessage = document.getElementById('publicLoaderMessage');
        const canvases = {};

        function showAlert(message, type) {
            alertBox.className = 'alert show ' + (type === 'success' ? 'alert-success' : 'alert-error');
            alertBox.innerHTML = message;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function setupTabs() {
            tabButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    document.querySelectorAll('.tab-panel').forEach(panel => panel.classList.remove('active'));
                    button.classList.add('active');
                    document.getElementById('panel-' + button.dataset.tab).classList.add('active');
                });
            });
        }

        function initSignature(canvasId) {
            const canvas = document.getElementById(canvasId);
            const ctx = canvas.getContext('2d');
            const state = { drawing: false, lastX: 0, lastY: 0, dirty: false, ctx: ctx, canvas: canvas };

            function resizeCanvas() {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                const rect = canvas.getBoundingClientRect();
                canvas.width = rect.width * ratio;
                canvas.height = rect.height * ratio;
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                ctx.scale(ratio, ratio);
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, rect.width, rect.height);
                ctx.lineWidth = 3;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.strokeStyle = '#000000';
                state.dirty = false;
            }

            function pointFromEvent(event) {
                const rect = canvas.getBoundingClientRect();
                const source = event.touches ? event.touches[0] : event;
                return {
                    x: source.clientX - rect.left,
                    y: source.clientY - rect.top
                };
            }

            function startDraw(event) {
                event.preventDefault();
                const point = pointFromEvent(event);
                state.drawing = true;
                state.lastX = point.x;
                state.lastY = point.y;
            }

            function moveDraw(event) {
                if (!state.drawing) {
                    return;
                }
                event.preventDefault();
                const point = pointFromEvent(event);
                ctx.beginPath();
                ctx.moveTo(state.lastX, state.lastY);
                ctx.lineTo(point.x, point.y);
                ctx.stroke();
                state.lastX = point.x;
                state.lastY = point.y;
                state.dirty = true;
            }

            function endDraw(event) {
                if (event) {
                    event.preventDefault();
                }
                state.drawing = false;
            }

            canvas.addEventListener('mousedown', startDraw);
            canvas.addEventListener('mousemove', moveDraw);
            canvas.addEventListener('mouseup', endDraw);
            canvas.addEventListener('mouseleave', endDraw);
            canvas.addEventListener('touchstart', startDraw, { passive: false });
            canvas.addEventListener('touchmove', moveDraw, { passive: false });
            canvas.addEventListener('touchend', endDraw, { passive: false });

            resizeCanvas();
            canvases[canvasId] = { state: state, resize: resizeCanvas };
        }

        function clearSignature(canvasId) {
            if (canvases[canvasId]) {
                canvases[canvasId].resize();
            }
        }

        function setLoadingState(show, message) {
            if (publicLoaderMessage && message) {
                publicLoaderMessage.textContent = message;
            }

            if (publicLoader) {
                publicLoader.classList.toggle('show', show);
                publicLoader.setAttribute('aria-hidden', show ? 'false' : 'true');
            }

            document.querySelectorAll('#internalAttendanceForm button, #guestAttendanceForm button').forEach(function (button) {
                button.disabled = show;
            });
        }

        async function submitForm(url, payload, message) {
            setLoadingState(true, message || 'Mohon tunggu, data absensi sedang dikirim.');

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(data.message || 'Gagal mengirim absensi.');
                }

                return data;
            } finally {
                setLoadingState(false);
            }
        }

        function bindForms() {
            document.getElementById('internalAttendanceForm').addEventListener('submit', async function (event) {
                event.preventDefault();

                try {
                    const result = await submitForm('{{ route('rapat.absensi.public.store', $rapat->public_code) }}', {
                        user_id: document.getElementById('user_id').value
                    }, 'Mohon tunggu, absensi peserta sedang diproses.');
                    showAlert(result.message, 'success');
                    setTimeout(function () { window.location.reload(); }, 800);
                } catch (error) {
                    showAlert(error.message, 'error');
                }
            });

            document.getElementById('guestAttendanceForm').addEventListener('submit', async function (event) {
                event.preventDefault();
                try {
                    const result = await submitForm('{{ route('rapat.absensi.public.guest', $rapat->public_code) }}', {
                        guest_name: document.getElementById('guest_name').value,
                        guest_instansi: document.getElementById('guest_instansi').value
                    }, 'Mohon tunggu, absensi external sedang diproses.');
                    showAlert(result.message, 'success');
                    setTimeout(function () { window.location.reload(); }, 800);
                } catch (error) {
                    showAlert(error.message, 'error');
                }
            });
        }

        setupTabs();
        bindForms();

        window.addEventListener('resize', function () {
            Object.keys(canvases).forEach(function (id) {
                canvases[id].resize();
            });
        });
    </script>
</body>
</html>
