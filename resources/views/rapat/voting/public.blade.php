<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $voting->judul }} | Voting Publik</title>
    @include('partials.app-icons')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box}body{margin:0;font-family:'Inter',system-ui,sans-serif;background:linear-gradient(180deg,#0f172a,#1e293b 240px,#f8fafc 240px);color:#0f172a;}
        .container{max-width:980px;margin:0 auto;padding:20px 16px 40px;}
        .hero{background:linear-gradient(135deg,#111827,#4f46e5);color:#fff;border-radius:24px;padding:22px;box-shadow:0 20px 50px rgba(15,23,42,.25);}
        .hero h1{margin:12px 0 8px;font-size:1.6rem;}
        .hero p{margin:0;color:rgba(255,255,255,.82);}
        .panel{margin-top:18px;background:#fff;border:1px solid #e2e8f0;border-radius:22px;padding:18px;box-shadow:0 12px 30px rgba(15,23,42,.06);}
        .field{margin-bottom:14px;}
        label{display:block;margin-bottom:6px;font-weight:700;font-size:.84rem;}
        .input,.select{width:100%;border:1px solid #cbd5e1;border-radius:14px;padding:12px 14px;font-size:.92rem;}
        .item-card{border:1px solid #e2e8f0;border-radius:18px;padding:16px;margin-bottom:14px;}
        .candidate-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-top:10px;}
        .candidate-option{border:1px solid #cbd5e1;border-radius:16px;padding:12px;cursor:pointer;transition:.2s;background:#fff;}
        .candidate-option.active{border-color:#4f46e5;background:#eef2ff;}
        .candidate-option:hover{border-color:#4f46e5}.candidate-option:focus-within{outline:3px solid #a5b4fc;outline-offset:3px}.candidate-option input[type="radio"]{position:absolute;opacity:0;width:1px;height:1px}.candidate-option{position:relative}.candidate-option.active:after{content:'✓';position:absolute;right:10px;top:8px;background:#4f46e5;color:#fff;border-radius:50%;width:24px;height:24px;display:grid;place-items:center}.candidate-body{padding-right:40px}.btn:disabled{opacity:.65;cursor:wait}.hero a{display:inline-block;color:#fff;margin-top:16px;text-underline-offset:4px}.input:focus-visible,.select:focus-visible,.btn:focus-visible{outline:3px solid #a5b4fc;outline-offset:3px}.item-card legend{font-weight:800;padding:0 5px}.item-card[aria-invalid="true"]{border-color:#dc2626}@media(prefers-reduced-motion:reduce){*{transition:none!important;scroll-behavior:auto!important}}
        .candidate-option.has-image{padding:0;overflow:hidden;}
        .candidate-image{width:100%;height:150px;object-fit:cover;background:#e2e8f0;display:block;}
        .answer-choice .candidate-image{height:180px;object-fit:contain;background:#f8fafc;}
        .candidate-body{padding:12px;}
        .comparison-note{display:inline-flex;align-items:center;gap:6px;margin-top:10px;background:#ecfdf5;color:#047857;border-radius:999px;padding:6px 10px;font-size:.76rem;font-weight:800;}
        .btn{border:0;border-radius:16px;padding:14px 16px;font-weight:800;font-size:.95rem;cursor:pointer;}
        .btn-primary{background:#4f46e5;color:#fff;width:100%;}
        .alert{display:none;border-radius:14px;padding:12px 14px;margin-bottom:14px;}
        .alert.show{display:block;}
        .alert-error{background:#fee2e2;color:#991b1b;}
        .alert-success{background:#dcfce7;color:#166534;}
        @media (max-width:640px){.hero h1{font-size:1.35rem}.candidate-grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>
    <div class="container">
        <div class="hero">
            <div style="font-size:.72rem;font-weight:800;letter-spacing:.4px;">VOTING PUBLIK</div>
            <h1>{{ $voting->judul }}</h1>
            <p>{{ $voting->deskripsi }}</p>
            <a href="{{ route('rapat.voting.public.results', $voting->public_code) }}">Lihat hasil langsung</a>
        </div>

        <div class="panel">
            <div id="voteAlert" class="alert" role="alert" tabindex="-1"></div>
            <form id="publicVotingForm" novalidate>
                <div class="field">
                    <label for="participant_id">Nama Peserta</label>
                    <select id="participant_id" class="select" required>
                        <option value="">-- Pilih Nama Peserta --</option>
                        @foreach($voting->participantPivots as $participant)
                            <option value="{{ $participant->user_id }}" {{ $participant->voted_at ? 'disabled' : '' }}>
                                {{ $participant->user->name }}{{ $participant->voted_at ? ' (sudah voting)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @foreach($voting->items as $item)
                    @php($imageCandidateCount = $item->candidates->filter(function ($candidate) { return !empty($candidate->image_path); })->count())
                    <fieldset class="item-card" data-item-id="{{ $item->id }}" aria-describedby="itemHelp{{ $item->id }}">
                        <legend style="font-size:1rem;">{{ $item->judul }}</legend>
                        <div class="text-muted" style="font-size:.82rem;">{{ $item->deskripsi }}</div>
                        <p id="itemHelp{{ $item->id }}" style="font-size:.82rem;color:#64748b;">{{ $item->uses_candidates ? 'Pilih satu kandidat.' : 'Pilih satu jawaban.' }}</p>
                        @if($imageCandidateCount > 1)
                            <div class="comparison-note">Mode perbandingan gambar</div>
                        @endif

                        @if($item->candidates->count() > 5 && $imageCandidateCount <= 1)
                            <div class="field mt-3 mb-0">
                                <label for="choiceSelect{{ $item->id }}">{{ $item->uses_candidates ? 'Pilih Kandidat' : 'Pilih Jawaban' }}</label>
                                <select id="choiceSelect{{ $item->id }}" class="select choice-select" data-item-id="{{ $item->id }}" required>
                                    <option value="">-- Pilih {{ $item->uses_candidates ? 'Kandidat' : 'Jawaban' }} --</option>
                                    @foreach($item->candidates as $candidate)
                                        <option value="{{ $candidate->id }}">{{ $candidate->nama_snapshot }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="candidate-grid">
                                @foreach($item->candidates as $candidate)
                                    <label class="candidate-option {{ $candidate->image_path ? 'has-image' : '' }} {{ $item->uses_candidates ? '' : 'answer-choice' }}" data-item-id="{{ $item->id }}">
                                        <input type="radio" name="choice_{{ $item->id }}" value="{{ $candidate->id }}" required>
                                        @if($candidate->image_path)
                                            <img src="{{ $candidate->image_url }}" class="candidate-image" alt="{{ $candidate->nama_snapshot }}">
                                        @endif
                                        <div class="candidate-body">
                                            <div style="font-weight:700;">{{ $candidate->nama_snapshot }}</div>
                                            @if($candidate->jabatan_snapshot)
                                                <div style="font-size:.76rem;color:#64748b;margin-top:3px;">{{ $candidate->jabatan_snapshot }}</div>
                                            @endif
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </fieldset>
                @endforeach

                <button type="submit" class="btn btn-primary" id="submitVote">Kirim Voting</button>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const alertBox = document.getElementById('voteAlert');

        function showAlert(message, type) {
            alertBox.className = 'alert show ' + (type === 'success' ? 'alert-success' : 'alert-error');
            alertBox.textContent = message;
            alertBox.focus();
        }

        document.querySelectorAll('.candidate-option').forEach(function (label) {
            label.querySelector('input').addEventListener('change', function () {
                const itemId = label.dataset.itemId;
                document.querySelectorAll('.candidate-option[data-item-id="' + itemId + '"]').forEach(x => x.classList.remove('active'));
                label.classList.add('active');
            });
        });

        document.getElementById('publicVotingForm').addEventListener('submit', async function (event) {
            event.preventDefault();
            const button = document.getElementById('submitVote');
            if (button.disabled) return;

            const payload = {
                participant_id: document.getElementById('participant_id').value,
                choices: {}
            };

            @foreach($voting->items as $item)
                @php($submitUsesSelect = $item->candidates->count() > 5 && $item->candidates->filter(function ($candidate) { return !empty($candidate->image_path); })->count() <= 1)
                @if($submitUsesSelect)
                    payload.choices['{{ $item->id }}'] = document.querySelector('.choice-select[data-item-id="{{ $item->id }}"]').value;
                @else
                    const checked{{ $item->id }} = document.querySelector('input[name="choice_{{ $item->id }}"]:checked');
                    payload.choices['{{ $item->id }}'] = checked{{ $item->id }} ? checked{{ $item->id }}.value : '';
                @endif
            @endforeach

            if (!payload.participant_id) {
                showAlert('Pilih nama peserta terlebih dahulu.', 'error');
                document.getElementById('participant_id').setAttribute('aria-invalid', 'true');
                document.getElementById('participant_id').focus();
                return;
            }
            let firstMissing = null;
            document.querySelectorAll('.item-card').forEach(function (item) {
                const missing = !payload.choices[item.dataset.itemId];
                item.setAttribute('aria-invalid', String(missing));
                if (missing && !firstMissing) firstMissing = item.querySelector('input,select');
            });
            if (firstMissing) {
                showAlert('Lengkapi satu pilihan pada setiap item voting.', 'error');
                firstMissing.focus();
                return;
            }
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            const controller = new AbortController();
            const timeout = setTimeout(function () { controller.abort(); }, 15000);

            try {
                const response = await fetch('{{ route('rapat.voting.public.store', $voting->public_code) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload),
                    signal: controller.signal
                });

                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                    throw new Error(firstError || data.message || 'Voting gagal disimpan.');
                }

                showAlert(data.message, 'success');
                setTimeout(function () { window.location.href = data.redirect_url; }, 800);
            } catch (error) {
                showAlert(error.name === 'AbortError' ? 'Pengiriman belum terkonfirmasi. Periksa hasil langsung sebelum mencoba kembali.' : error.message, 'error');
                button.disabled = false;
            } finally {
                clearTimeout(timeout);
                button.removeAttribute('aria-busy');
            }
        });
    </script>
</body>
</html>
