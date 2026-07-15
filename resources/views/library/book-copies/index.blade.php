@extends('layouts.app')
@section('title', 'Eksemplar Buku')
@section('page-title', 'Eksemplar Buku')
@section('page-subtitle', 'Manajemen eksemplar dan kode buku')

@section('content')
<div class="page-header">
    <div>
        <h1>Eksemplar Buku</h1>
        <p>Total {{ $copies->total() }} eksemplar</p>
    </div>
    @if($canManageLibrary)<a href="{{ route('library.book-copies.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Tambah Eksemplar
    </a>@endif
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" name="search" class="form-control" placeholder="Cari kode atau judul buku..."
                    value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="tersedia" {{ request('status')=='tersedia'?'selected':'' }}>Tersedia</option>
                    <option value="dipinjam" {{ request('status')=='dipinjam'?'selected':'' }}>Dipinjam</option>
                    <option value="rusak" {{ request('status')=='rusak'?'selected':'' }}>Rusak</option>
                    <option value="hilang" {{ request('status')=='hilang'?'selected':'' }}>Hilang</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                <a href="{{ route('library.book-copies.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-modern mb-0">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" class="form-check-input"></th>
                        <th>Kode Eksemplar</th>
                        <th>Judul Buku</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Catatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($copies as $copy)
                    <tr>
                        <td><input type="checkbox" class="form-check-input copy-check" value="{{ $copy->id }}"></td>
                        <td><code style="font-size:12px;background:#f1f5f9;padding:3px 8px;border-radius:6px;">{{ $copy->copy_code }}</code></td>
                        <td>
                            <div style="font-weight:600;font-size:13px;">{{ $copy->book->title }}</div>
                            <small class="text-muted">{{ $copy->book->author }}</small>
                        </td>
                        <td style="font-size:13px;">{{ $copy->book->category->name }}</td>
                        <td>
                            <span class="badge bg-{{ $copy->status_badge }} badge-status">
                                {{ ucfirst($copy->status) }}
                            </span>
                        </td>
                        <td style="font-size:13px;max-width:150px;">{{ $copy->notes ?? '—' }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('library.barcode.show', $copy) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Barcode">
                                    <i class="bi bi-upc-scan"></i>
                                </a>
                                @if($canManageLibrary)<a href="{{ route('library.book-copies.edit', $copy) }}" class="btn btn-sm btn-icon btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('library.book-copies.destroy', $copy) }}" class="d-inline"
                                    onsubmit="return confirm('Hapus eksemplar ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>@endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state"><i class="bi bi-collection d-block"></i>Belum ada eksemplar</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-transparent d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <button class="btn btn-sm btn-outline-secondary" id="printSelectedBtn" onclick="printSelected()" style="display:none;">
                <i class="bi bi-printer me-1"></i> Cetak Barcode Terpilih
            </button>
        </div>
        <div>{{ $copies->links('pagination::bootstrap-4') }}</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.copy-check').forEach(cb => cb.checked = this.checked);
    updatePrintBtn();
});
document.querySelectorAll('.copy-check').forEach(cb => cb.addEventListener('change', updatePrintBtn));

function updatePrintBtn() {
    const checked = document.querySelectorAll('.copy-check:checked');
    const btn = document.getElementById('printSelectedBtn');
    btn.style.display = checked.length > 0 ? 'inline-flex' : 'none';
}

function printSelected() {
    const ids = [...document.querySelectorAll('.copy-check:checked')].map(cb => cb.value).join(',');
    window.open('{{ route('library.barcode.print') }}?ids=' + ids, '_blank');
}
</script>
@endpush
