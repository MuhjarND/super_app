@extends('layouts.app')
@section('title', 'Scan Barcode')
@section('page-title', 'Scan Barcode Buku')
@section('page-subtitle', 'Gunakan kamera untuk scan barcode eksemplar')

@push('styles')
<style>
    #qr-reader {
        border-radius: 16px;
        overflow: hidden;
        border: 3px solid #4f46e5;
    }
    #qr-reader video { border-radius: 13px; }
    #scan-result-area { min-height: 200px; }

    .scan-status-ring {
        width: 80px; height: 80px;
        border-radius: 50%;
        border: 4px solid #e2e8f0;
        border-top-color: #4f46e5;
        animation: spin 1s linear infinite;
        margin: 0 auto 16px;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    .book-result-card {
        border-radius: 16px;
        border: 2px solid #e2e8f0;
        padding: 20px;
        transition: all .3s;
    }

    .book-result-card.available { border-color: #10b981; background: #f0fdf4; }
    .book-result-card.borrowed  { border-color: #f59e0b; background: #fffbeb; }
    .book-result-card.damaged   { border-color: #ef4444; background: #fef2f2; }
</style>
@endpush

@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div><i class="bi bi-camera-fill me-2 text-primary"></i>Kamera Scanner</div>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary" id="startBtn" onclick="startScanner()">
                        <i class="bi bi-play-fill me-1"></i> Mulai Scan
                    </button>
                    <button class="btn btn-sm btn-outline-danger" id="stopBtn" onclick="stopScanner()" style="display:none;">
                        <i class="bi bi-stop-fill me-1"></i> Stop
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="qr-reader" style="width:100%;max-width:500px;margin:0 auto;"></div>
                <div id="scanner-placeholder" class="text-center py-5" style="color:#94a3b8;">
                    <i class="bi bi-camera" style="font-size:48px;display:block;margin-bottom:12px;opacity:.4;"></i>
                    <p>Klik <strong>Mulai Scan</strong> untuk mengaktifkan kamera</p>
                </div>
            </div>
        </div>

        <!-- Manual Input -->
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-keyboard me-2"></i>Input Manual</div>
            <div class="card-body">
                <div class="input-group">
                    <input type="text" id="manualCode" class="form-control"
                        placeholder="Ketik kode eksemplar..." style="font-family:monospace;">
                    <button class="btn btn-primary" onclick="lookupCode(document.getElementById('manualCode').value)">
                        <i class="bi bi-search"></i> Cari
                    </button>
                </div>
                <small class="text-muted d-block mt-2">Contoh: BK-2026-000001</small>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Hasil Scan</div>
            <div class="card-body" id="scan-result-area">
                <div class="text-center py-5" id="no-result" style="color:#94a3b8;">
                    <i class="bi bi-upc-scan" style="font-size:48px;display:block;margin-bottom:12px;opacity:.4;"></i>
                    <p>Belum ada hasil scan</p>
                    <small>Scan barcode atau masukkan kode manual</small>
                </div>
                <div id="loading-result" style="display:none;" class="text-center py-5">
                    <div class="scan-status-ring"></div>
                    <p style="color:#64748b;">Mencari eksemplar...</p>
                </div>
                <div id="result-content" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- html5-qrcode library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrCode;
let lastScanned = '';
const canManageLibrary = @json($canManageLibrary);
const createLoanUrl = @json(route('library.loans.create'));
const createReturnUrl = @json(route('library.returns.create'));

function startScanner() {
    document.getElementById('scanner-placeholder').style.display = 'none';
    document.getElementById('startBtn').style.display = 'none';
    document.getElementById('stopBtn').style.display = 'inline-flex';

    html5QrCode = new Html5Qrcode("qr-reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 250, height: 150 } },
        (decodedText) => {
            if (decodedText !== lastScanned) {
                lastScanned = decodedText;
                lookupCode(decodedText);
                // Beep / vibrate
                if (navigator.vibrate) navigator.vibrate(100);
            }
        },
        (errorMessage) => {}
    ).catch(err => {
        alert('Tidak dapat mengakses kamera: ' + err);
        document.getElementById('startBtn').style.display = 'inline-flex';
        document.getElementById('stopBtn').style.display = 'none';
    });
}

function stopScanner() {
    if (html5QrCode) {
        html5QrCode.stop().then(() => {
            html5QrCode.clear();
            document.getElementById('scanner-placeholder').style.display = 'block';
            document.getElementById('startBtn').style.display = 'inline-flex';
            document.getElementById('stopBtn').style.display = 'none';
            lastScanned = '';
        });
    }
}

function lookupCode(code) {
    if (!code || code.trim() === '') return;
    code = code.trim();

    document.getElementById('no-result').style.display = 'none';
    document.getElementById('loading-result').style.display = 'block';
    document.getElementById('result-content').style.display = 'none';

    fetch('{{ route('library.scan.lookup') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
        },
        body: JSON.stringify({ code })
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('loading-result').style.display = 'none';
        document.getElementById('result-content').style.display = 'block';
        renderResult(data, code);
    })
    .catch(() => {
        document.getElementById('loading-result').style.display = 'none';
        document.getElementById('result-content').innerHTML = `
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>
            Terjadi kesalahan. Coba lagi.</div>`;
        document.getElementById('result-content').style.display = 'block';
    });
}

function renderResult(data, code) {
    const el = document.getElementById('result-content');

    if (!data.found) {
        el.innerHTML = `
            <div class="book-result-card damaged">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width:50px;height:50px;background:#fee2e2;border-radius:12px;display:flex;align-items:center;justify-content:center;">
                        <i class="bi bi-x-circle-fill text-danger" style="font-size:24px;"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-danger">Tidak Ditemukan</div>
                        <code style="font-size:12px;">${code}</code>
                    </div>
                </div>
                <p style="font-size:13.5px;color:#7f1d1d;">${data.message}</p>
            </div>`;
        return;
    }

    const copy = data.copy;
    const book = copy.book;
    const statusColors = { tersedia: 'available', dipinjam: 'borrowed', rusak: 'damaged', hilang: 'damaged' };
    const statusClass = statusColors[copy.status] || 'borrowed';
    const statusBadge = { tersedia: 'success', dipinjam: 'warning', rusak: 'danger', hilang: 'dark' };

    let loanInfo = '';
    if (copy.loan) {
        loanInfo = `
        <div class="mt-3 p-3" style="background:rgba(0,0,0,.04);border-radius:10px;font-size:13px;">
            <div class="fw-600" style="font-weight:600;margin-bottom:4px;">📋 Informasi Peminjaman</div>
            <div><span class="text-muted">Peminjam:</span> <strong>${copy.loan.member_name}</strong></div>
            <div><span class="text-muted">No. Anggota:</span> ${copy.loan.member_number}</div>
            <div><span class="text-muted">Jatuh Tempo:</span> ${copy.loan.due_date}
                ${copy.loan.is_overdue ? '<span class="badge bg-danger ms-1">TERLAMBAT</span>' : ''}
            </div>
        </div>`;
    }

    let actionBtn = '';
    if (canManageLibrary && copy.status === 'tersedia') {
        actionBtn = `<a href="${createLoanUrl}?copy_code=${encodeURIComponent(copy.copy_code)}" class="btn btn-success btn-sm mt-3 w-100">
            <i class="bi bi-arrow-left-right me-1"></i> Proses Peminjaman
        </a>`;
    } else if (canManageLibrary && copy.status === 'dipinjam') {
        actionBtn = `<a href="${createReturnUrl}" class="btn btn-warning btn-sm mt-3 w-100">
            <i class="bi bi-arrow-return-left me-1"></i> Proses Pengembalian
        </a>`;
    }

    el.innerHTML = `
        <div class="book-result-card ${statusClass}">
            <div class="d-flex gap-3 mb-3">
                <img src="${book.cover_url}" alt="${book.title}"
                    style="width:70px;height:95px;object-fit:cover;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.15);flex-shrink:0;">
                <div style="flex:1;min-width:0;">
                    <div class="fw-bold" style="font-size:15px;margin-bottom:4px;">${book.title}</div>
                    <div style="font-size:13px;color:#64748b;">${book.author}</div>
                    <div style="font-size:12px;color:#94a3b8;margin-top:4px;">${book.category} • ${book.shelf}</div>
                    <div class="mt-2">
                        <span class="badge bg-${statusBadge[copy.status]}">
                            ${copy.status.charAt(0).toUpperCase() + copy.status.slice(1)}
                        </span>
                        <code style="font-size:11px;margin-left:6px;background:rgba(0,0,0,.06);padding:2px 6px;border-radius:4px;">
                            ${copy.copy_code}
                        </code>
                    </div>
                </div>
            </div>
            ${loanInfo}
            ${actionBtn}
        </div>`;
}

// Enter key on manual input
document.getElementById('manualCode').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') lookupCode(this.value);
});
</script>
@endpush
