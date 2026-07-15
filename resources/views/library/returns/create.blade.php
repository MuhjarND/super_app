@extends('layouts.app')
@section('title', 'Proses Pengembalian')
@section('page-title', 'Proses Pengembalian Buku')
@section('page-subtitle', 'Scan atau cari nomor peminjaman')

@push('styles')
<style>
    .loan-preview {
        border: 2px solid #10b981;
        border-radius: 12px;
        padding: 16px;
        background: #f0fdf4;
    }
    .fine-preview {
        border: 2px solid #ef4444;
        border-radius: 12px;
        padding: 16px;
        background: #fef2f2;
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header"><i class="bi bi-search me-2"></i>Cari Transaksi Peminjaman</div>
            <div class="card-body">
                <div class="input-group">
                    <input type="text" id="loanSearch" class="form-control"
                        placeholder="Ketik no. pinjam, nama, atau nomor anggota...">
                    <button class="btn btn-primary" onclick="searchLoan()">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                    <a href="{{ route('library.scan.index') }}" class="btn btn-outline-secondary" target="_blank">
                        <i class="bi bi-camera me-1"></i> Scan
                    </a>
                </div>
                <div id="loanSearchResults" class="mt-2"></div>
            </div>
        </div>

        @if($loan)
        <form method="POST" action="{{ route('library.returns.store') }}" id="returnForm">
            @csrf
            <input type="hidden" name="loan_id" value="{{ $loan->id }}">

            <div class="card mt-3">
                <div class="card-header"><i class="bi bi-person me-2"></i>Informasi Peminjaman</div>
                <div class="card-body">
                    <div class="loan-preview">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-text-sm bg-success bg-opacity-15 text-success"
                                        style="width:48px;height:48px;font-size:17px;font-weight:700;">
                                        {{ strtoupper(substr($loan->member->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight:700;font-size:15px;">{{ $loan->member->name }}</div>
                                        <code style="font-size:12px;">{{ $loan->member->member_number }}</code>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div style="font-size:13px;">
                                    <div><span class="text-muted">No. Pinjam:</span> <code>{{ $loan->loan_number }}</code></div>
                                    <div><span class="text-muted">Tgl. Pinjam:</span> {{ $loan->loan_date->format('d M Y') }}</div>
                                    <div><span class="text-muted">Jatuh Tempo:</span>
                                        <strong class="{{ $loan->isOverdue() ? 'text-danger' : '' }}">
                                            {{ $loan->due_date->format('d M Y') }}
                                        </strong>
                                        @if($loan->isOverdue())
                                            <span class="badge bg-danger ms-1">TERLAMBAT {{ $loan->due_date->diffInDays(now()) }} HARI</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><i class="bi bi-book me-2"></i>Buku yang Dikembalikan</div>
                <div class="card-body p-0">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr><th>Cover</th><th>Buku</th><th>Kode Eksemplar</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach($loan->loanItems as $item)
                            <tr>
                                <td><img src="{{ $item->bookCopy->book->cover_url }}"
                                    style="width:40px;height:54px;object-fit:cover;border-radius:6px;"></td>
                                <td>
                                    <div style="font-weight:600;font-size:13.5px;">{{ $item->bookCopy->book->title }}</div>
                                    <small class="text-muted">{{ $item->bookCopy->book->author }}</small>
                                </td>
                                <td><code style="font-size:12px;">{{ $item->bookCopy->copy_code }}</code></td>
                                <td><span class="badge bg-warning text-dark">Belum Dikembalikan</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><i class="bi bi-calendar-check me-2"></i>Data Pengembalian</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Pengembalian <span class="text-danger">*</span></label>
                            <input type="date" name="return_date" id="returnDate" class="form-control"
                                value="{{ date('Y-m-d') }}" required onchange="calcFine()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="note" class="form-control" placeholder="Catatan kondisi buku, dll...">
                        </div>
                    </div>

                    <!-- Kalkulasi Denda -->
                    <div id="fineCalc" class="mt-3" style="display:none;">
                        <div class="fine-preview">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size:24px;"></i>
                                <div>
                                    <div style="font-weight:700;color:#dc2626;font-size:15px;">Keterlambatan Terdeteksi!</div>
                                    <div id="fineDetail" style="font-size:13.5px;color:#7f1d1d;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-1"></i> Konfirmasi Pengembalian
                    </button>
                    <a href="{{ route('library.returns.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Batal
                    </a>
                </div>
            </div>
        </form>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
const finePerDay = {{ $finePerDay }};
const dueDate = '{{ optional(optional($loan)->due_date)->format('Y-m-d') }}';
const returnCreateUrl = @json(route('library.returns.create'));
const returnSearchUrl = @json(route('library.returns.search-loan'));

function calcFine() {
    if (!dueDate) return;
    const returnDate = new Date(document.getElementById('returnDate').value);
    const due = new Date(dueDate);
    const diffDays = Math.floor((returnDate - due) / (1000 * 60 * 60 * 24));

    const fineDiv = document.getElementById('fineCalc');
    const fineDetail = document.getElementById('fineDetail');

    if (diffDays > 0) {
        const totalFine = diffDays * finePerDay;
        fineDetail.textContent = `Terlambat ${diffDays} hari × Rp${finePerDay.toLocaleString('id-ID')} = Rp${totalFine.toLocaleString('id-ID')}`;
        fineDiv.style.display = 'block';
    } else {
        fineDiv.style.display = 'none';
    }
}

// Inisialisasi
calcFine();

// Search loan
let searchTimer;
document.getElementById('loanSearch').addEventListener('input', function() {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    if (q.length < 2) { document.getElementById('loanSearchResults').innerHTML = ''; return; }
    searchTimer = setTimeout(() => searchLoan(q), 300);
});

function searchLoan(q) {
    q = q || document.getElementById('loanSearch').value.trim();
    fetch(`${returnSearchUrl}?q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(data => {
            const el = document.getElementById('loanSearchResults');
            if (!data.length) { el.innerHTML = '<small class="text-muted">Tidak ada hasil</small>'; return; }
            el.innerHTML = data.map(l => `
                <a href="${returnCreateUrl}?loan_id=${l.id}"
                    class="d-flex align-items-center justify-content-between text-decoration-none
                        border rounded p-2 mb-1 hover-bg"
                    style="border-radius:8px;background:#f8fafc;">
                    <div>
                        <div style="font-weight:600;font-size:13.5px;">${l.member_name}</div>
                        <small class="text-muted">${l.loan_number} · Jatuh tempo: ${l.due_date}</small>
                    </div>
                    <span class="badge bg-${l.is_overdue ? 'danger' : 'warning'} text-${l.is_overdue ? 'white' : 'dark'}">
                        ${l.status}
                    </span>
                </a>`).join('');
        });
}
</script>
@endpush
