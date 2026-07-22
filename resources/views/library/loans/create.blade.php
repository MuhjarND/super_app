@extends('layouts.app')
@section('title', 'Peminjaman Baru')
@section('page-title', $canManageLibrary ? 'Transaksi Peminjaman Baru' : 'Pinjam Buku')
@section('page-subtitle', $canManageLibrary ? 'Input data peminjaman buku' : 'Pilih buku yang ingin dipinjam')

@push('styles')
<style>
    .member-card {
        border: 2px solid #e2e8f0;
        border-radius: 12px; padding: 14px;
        transition: all .2s; cursor: pointer;
    }
    .member-card:hover { border-color: #4f46e5; background: #f5f3ff; }
    .member-card.selected { border-color: #4f46e5; background: #eef2ff; }

    .book-item {
        border: 1.5px solid #e2e8f0;
        border-radius: 10px; padding: 12px;
        margin-bottom: 8px; position: relative;
    }
    .book-item .remove-btn {
        position: absolute; top: 8px; right: 8px;
    }

    #memberSearch .dropdown-menu { max-height: 300px; overflow-y: auto; }
    .member-option { padding: 10px 16px; cursor: pointer; }
    .member-option:hover { background: #f1f5f9; }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <form method="POST" action="{{ route('library.loans.store') }}" id="loanForm">
            @csrf
            <div class="row g-3">
                <!-- Pilih Anggota -->
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header"><i class="bi bi-person-fill me-2"></i>{{ $canManageLibrary ? 'Pilih Anggota' : 'Peminjam' }}</div>
                        <div class="card-body">
                            @if($canManageLibrary)
                            <div id="memberSearch" class="position-relative">
                                <input type="text" id="memberSearchInput" class="form-control"
                                    placeholder="Cari nama atau nomor anggota...">
                                <div id="memberDropdown" class="dropdown-menu w-100" style="display:none;position:absolute;z-index:100;">
                                </div>
                            </div>
                            <input type="hidden" name="member_id" id="selectedMemberId">

                            <div id="selectedMemberCard" style="display:none;" class="mt-3">
                                <div class="member-card selected">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-text-sm bg-primary bg-opacity-10 text-primary" id="memberInitial" style="font-weight:700;font-size:13px;"></div>
                                        <div>
                                            <div id="memberName" style="font-weight:600;font-size:14px;"></div>
                                            <small id="memberNumber" class="text-muted"></small>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-link text-danger mt-2 p-0" onclick="clearMember()">
                                        <i class="bi bi-x-circle me-1"></i>Ganti Anggota
                                    </button>
                                </div>
                            </div>
                            @else
                                <input type="hidden" name="member_id" id="selectedMemberId" value="{{ $member->id }}">
                                <div class="member-card selected">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-text-sm bg-primary bg-opacity-10 text-primary" style="font-weight:700;font-size:13px;">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div style="font-weight:600;font-size:14px;">{{ $member->name }}</div>
                                            <small class="text-muted">{{ $member->member_number }}</small>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header"><i class="bi bi-calendar-date me-2"></i>Tanggal</div>
                        <div class="card-body">
                            @if($canManageLibrary)
                            <div class="mb-3">
                                <label class="form-label">Tanggal Pinjam <span class="text-danger">*</span></label>
                                <input type="date" name="loan_date" id="loanDate" class="form-control"
                                    value="{{ old('loan_date', date('Y-m-d')) }}" required onchange="calcDue()">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Jatuh Tempo <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" id="dueDate" class="form-control"
                                    value="{{ old('due_date', date('Y-m-d', strtotime('+' . $loanDays . ' days'))) }}" required>
                                <small class="text-muted">Default {{ $loanDays }} hari dari tanggal pinjam</small>
                            </div>
                            @else
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Tanggal Pinjam</small>
                                        <strong>{{ now()->translatedFormat('d M Y') }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Jatuh Tempo</small>
                                        <strong>{{ now()->addDays($loanDays)->translatedFormat('d M Y') }}</strong>
                                    </div>
                                </div>
                            @endif
                            <div>
                                <label class="form-label">Catatan</label>
                                <textarea name="note" class="form-control" rows="2" placeholder="Catatan tambahan...">{{ old('note') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pilih Buku -->
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <div><i class="bi bi-book me-2"></i>Pilih Buku</div>
                            <span class="badge bg-primary" id="bookCount">0 buku</span>
                        </div>
                        <div class="card-body">
                            <!-- Scan / Input Kode -->
                            <div class="input-group mb-3">
                                <input type="text" id="copyCodeInput" class="form-control"
                                    placeholder="Scan barcode atau ketik kode eksemplar (BK-XXXX-XXXXXX)..."
                                    style="font-family:monospace;">
                                <button class="btn btn-primary" type="button" onclick="addBookByCode()">
                                    <i class="bi bi-plus-lg me-1"></i>Tambah
                                </button>
                                @if($canManageLibrary)<a href="{{ route('library.scan.index') }}" class="btn btn-outline-secondary" target="_blank" title="Buka Scanner Kamera">
                                    <i class="bi bi-camera"></i>
                                </a>@endif
                            </div>

                            <div id="bookList">
                                <div id="noBookPlaceholder" class="text-center py-4" style="color:#94a3b8;border:2px dashed #e2e8f0;border-radius:10px;">
                                    <i class="bi bi-book" style="font-size:32px;display:block;margin-bottom:8px;opacity:.4;"></i>
                                    <small>Belum ada buku dipilih.<br>{{ $canManageLibrary ? 'Scan barcode atau masukkan kode eksemplar.' : 'Pilih dari daftar buku atau masukkan kode buku.' }}</small>
                                </div>
                            </div>

                            <!-- Hidden inputs untuk copy codes -->
                            <div id="copyCodeInputs"></div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-body">
                            @if($errors->any())
                            <div class="alert alert-danger mb-3">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill" id="submitBtn" disabled>
                                    <i class="bi bi-book me-1"></i> {{ $canManageLibrary ? 'Simpan Peminjaman' : 'Pinjam Sekarang' }}
                                </button>
                                <a href="{{ route('library.loans.index') }}" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-1"></i> Batal
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const selectedBooks = {};
const loanDays = {{ $loanDays }};
const maxBooks = {{ $maxBooks }};

@if($canManageLibrary)
// ===== MEMBER SEARCH =====
let memberSearchTimer;
document.getElementById('memberSearchInput').addEventListener('input', function() {
    clearTimeout(memberSearchTimer);
    const q = this.value.trim();
    if (q.length < 2) { hideDropdown(); return; }
    memberSearchTimer = setTimeout(() => searchMembers(q), 300);
});

function searchMembers(q) {
    fetch(`{{ route('library.members.search') }}?q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(data => renderMemberDropdown(data));
}

function renderMemberDropdown(members) {
    const dd = document.getElementById('memberDropdown');
    if (!members.length) { dd.innerHTML = '<div class="member-option text-muted">Tidak ada hasil</div>'; }
    else {
        dd.innerHTML = members.map(m => `
            <div class="member-option" onclick="selectMember(${m.id}, '${m.name}', '${m.member_number}')">
                <div style="font-weight:600;font-size:13.5px;">${m.name}</div>
                <small class="text-muted">${m.member_number} · ${m.class_position || 'Anggota'}</small>
            </div>`).join('');
    }
    dd.style.display = 'block';
}

function selectMember(id, name, number) {
    document.getElementById('selectedMemberId').value = id;
    document.getElementById('memberName').textContent = name;
    document.getElementById('memberNumber').textContent = number;
    document.getElementById('memberInitial').textContent = name.charAt(0).toUpperCase();
    document.getElementById('selectedMemberCard').style.display = 'block';
    document.getElementById('memberSearchInput').style.display = 'none';
    hideDropdown();
    updateSubmitBtn();
}

function clearMember() {
    document.getElementById('selectedMemberId').value = '';
    document.getElementById('selectedMemberCard').style.display = 'none';
    document.getElementById('memberSearchInput').style.display = 'block';
    document.getElementById('memberSearchInput').value = '';
    updateSubmitBtn();
}

function hideDropdown() {
    document.getElementById('memberDropdown').style.display = 'none';
}

document.addEventListener('click', e => {
    if (!document.getElementById('memberSearch').contains(e.target)) hideDropdown();
});
@endif

// ===== BOOK SEARCH =====
function addBookByCode() {
    const code = document.getElementById('copyCodeInput').value.trim();
    if (!code) return;

    if (Object.keys(selectedBooks).length >= maxBooks) {
        alert(`Maksimal ${maxBooks} buku per peminjaman.`);
        return;
    }

    if (selectedBooks[code]) {
        alert('Buku dengan kode ini sudah ditambahkan.');
        return;
    }

    fetch('{{ route('library.book-copies.lookup') }}?code=' + encodeURIComponent(code))
        .then(r => r.json())
        .then(data => {
            if (!data.found) {
                alert(data.message);
                return;
            }
            const copy = data.copy;
            if (copy.status !== 'tersedia') {
                alert(`Eksemplar ${copy.copy_code} tidak tersedia (status: ${copy.status}).`);
                return;
            }
            addBookToList(copy);
            document.getElementById('copyCodeInput').value = '';
        });
}

function addBookToList(copy) {
    selectedBooks[copy.copy_code] = copy;
    document.getElementById('noBookPlaceholder').style.display = 'none';

    const div = document.createElement('div');
    div.className = 'book-item';
    div.id = `book-${copy.copy_code}`;
    div.innerHTML = `
        <div class="d-flex gap-3 align-items-center">
            <img src="${copy.book.cover_url}" style="width:50px;height:68px;object-fit:cover;border-radius:8px;">
            <div style="flex:1;">
                <div style="font-weight:600;font-size:13.5px;">${copy.book.title}</div>
                <div style="font-size:12.5px;color:#64748b;">${copy.book.author}</div>
                <code style="font-size:11.5px;background:#f1f5f9;padding:2px 8px;border-radius:6px;">${copy.copy_code}</code>
                <span class="badge bg-success ms-1">Tersedia</span>
            </div>
        </div>
        <button type="button" class="remove-btn btn btn-sm btn-icon btn-outline-danger"
            onclick="removeBook('${copy.copy_code}')">
            <i class="bi bi-x-lg"></i>
        </button>`;
    document.getElementById('bookList').insertBefore(div, document.getElementById('noBookPlaceholder'));

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'copy_codes[]';
    input.value = copy.copy_code;
    input.id = `input-${copy.copy_code}`;
    document.getElementById('copyCodeInputs').appendChild(input);

    updateBookCount();
    updateSubmitBtn();
}

function removeBook(code) {
    delete selectedBooks[code];
    document.getElementById(`book-${code}`)?.remove();
    document.getElementById(`input-${code}`)?.remove();
    if (Object.keys(selectedBooks).length === 0) {
        document.getElementById('noBookPlaceholder').style.display = 'block';
    }
    updateBookCount();
    updateSubmitBtn();
}

function updateBookCount() {
    const count = Object.keys(selectedBooks).length;
    document.getElementById('bookCount').textContent = `${count} buku`;
}

function updateSubmitBtn() {
    const hasMember = !!document.getElementById('selectedMemberId').value;
    const hasBooks  = Object.keys(selectedBooks).length > 0;
    document.getElementById('submitBtn').disabled = !(hasMember && hasBooks);
}

function calcDue() {
    const loanDate = new Date(document.getElementById('loanDate').value);
    loanDate.setDate(loanDate.getDate() + loanDays);
    document.getElementById('dueDate').value = loanDate.toISOString().split('T')[0];
}

// Enter on copy code input
document.getElementById('copyCodeInput').addEventListener('keypress', e => {
    if (e.key === 'Enter') { e.preventDefault(); addBookByCode(); }
});

// Check for pre-filled member from URL
const urlParams = new URLSearchParams(window.location.search);
@if(request()->has('copy_code'))
window.addEventListener('load', () => {
    document.getElementById('copyCodeInput').value = '{{ request('copy_code') }}';
    addBookByCode();
});
@endif
</script>
@endpush
