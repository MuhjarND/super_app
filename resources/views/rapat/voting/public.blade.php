<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $voting->judul }} | Voting Publik</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body{margin:0;font-family:'Inter',sans-serif;background:linear-gradient(180deg,#0f172a,#1e293b 240px,#f8fafc 240px);color:#0f172a;}
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
        </div>

        <div class="panel">
            <div id="voteAlert" class="alert"></div>
            <form id="publicVotingForm">
                <div class="field">
                    <label>Nama Peserta</label>
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
                    <div class="item-card" data-item-id="{{ $item->id }}">
                        <div style="font-size:1rem;font-weight:800;">{{ $item->judul }}</div>
                        <div class="text-muted" style="font-size:.82rem;">{{ $item->deskripsi }}</div>

                        @if($item->candidates->count() > 5)
                            <div class="field mt-3 mb-0">
                                <label>Pilih Kandidat</label>
                                <select class="select choice-select" data-item-id="{{ $item->id }}" required>
                                    <option value="">-- Pilih Kandidat --</option>
                                    @foreach($item->candidates as $candidate)
                                        <option value="{{ $candidate->id }}">{{ $candidate->nama_snapshot }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="candidate-grid">
                                @foreach($item->candidates as $candidate)
                                    <label class="candidate-option" data-item-id="{{ $item->id }}">
                                        <input type="radio" name="choice_{{ $item->id }}" value="{{ $candidate->id }}" style="display:none;">
                                        <div style="font-weight:700;">{{ $candidate->nama_snapshot }}</div>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach

                <button type="submit" class="btn btn-primary">Kirim Voting</button>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const alertBox = document.getElementById('voteAlert');

        function showAlert(message, type) {
            alertBox.className = 'alert show ' + (type === 'success' ? 'alert-success' : 'alert-error');
            alertBox.innerHTML = message;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        document.querySelectorAll('.candidate-option').forEach(function (label) {
            label.addEventListener('click', function () {
                const itemId = label.dataset.itemId;
                document.querySelectorAll('.candidate-option[data-item-id="' + itemId + '"]').forEach(x => x.classList.remove('active'));
                label.classList.add('active');
                label.querySelector('input').checked = true;
            });
        });

        document.getElementById('publicVotingForm').addEventListener('submit', async function (event) {
            event.preventDefault();

            const payload = {
                participant_id: document.getElementById('participant_id').value,
                choices: {}
            };

            @foreach($voting->items as $item)
                @if($item->candidates->count() > 5)
                    payload.choices['{{ $item->id }}'] = document.querySelector('.choice-select[data-item-id="{{ $item->id }}"]').value;
                @else
                    const checked{{ $item->id }} = document.querySelector('input[name="choice_{{ $item->id }}"]:checked');
                    payload.choices['{{ $item->id }}'] = checked{{ $item->id }} ? checked{{ $item->id }}.value : '';
                @endif
            @endforeach

            try {
                const response = await fetch('{{ route('rapat.voting.public.store', $voting->public_code) }}', {
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
                    throw new Error(data.message || 'Voting gagal disimpan.');
                }

                showAlert(data.message, 'success');
                setTimeout(function () { window.location.href = data.redirect_url; }, 800);
            } catch (error) {
                showAlert(error.message, 'error');
            }
        });
    </script>
</body>
</html>
