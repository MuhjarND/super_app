@extends('layouts.app')

@section('title', 'Ajukan E-Sign Surat Keluar')

@push('styles')
<style>
    .esign-layout { display:grid; grid-template-columns:minmax(0, 1fr) 340px; gap:18px; align-items:start; }
    .esign-card { border:1px solid #e2e8f0; border-radius:18px; box-shadow:0 12px 30px rgba(15,23,42,.06); overflow:hidden; }
    .esign-toolbar { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:12px 16px; background:#f8fafc; border-bottom:1px solid #e2e8f0; }
    .esign-page-control { display:flex; align-items:center; gap:9px; }
    .esign-workspace { padding:20px; background:#dfe5ee; min-height:720px; overflow:auto; text-align:center; }
    .esign-page { position:relative; display:inline-block; width:min(100%, 820px); line-height:0; box-shadow:0 8px 28px rgba(15,23,42,.18); background:#fff; user-select:none; touch-action:none; }
    .esign-page canvas { display:block; width:100%; height:auto; }
    .esign-box { position:absolute; z-index:5; display:flex; align-items:center; justify-content:center; padding:4px; border:2px solid #4f46e5; background:rgba(255,255,255,.94); color:#0f172a; line-height:1.18; cursor:move; box-shadow:0 4px 12px rgba(79,70,229,.18); overflow:hidden; }
    .esign-box-signature { display:block; width:100%; height:100%; object-fit:contain; pointer-events:none; visibility:hidden; }
    .esign-signer-preview { display:flex; align-items:center; gap:12px; padding:10px; margin-top:10px; border:1px solid #e2e8f0; border-radius:12px; background:#f8fafc; }
    .esign-signer-preview img { width:118px; height:42px; object-fit:contain; background:#fff; border:1px solid #e2e8f0; border-radius:6px; visibility:hidden; }
    .esign-resize { position:absolute; right:-1px; bottom:-1px; width:18px; height:18px; background:#4f46e5; border:2px solid #fff; border-radius:4px 0 0 0; cursor:nwse-resize; }
    .esign-status { display:inline-flex; align-items:center; padding:7px 11px; border-radius:999px; font-weight:700; font-size:.8rem; }
    .esign-status.pending { color:#92400e; background:#fef3c7; }
    .esign-status.approved { color:#166534; background:#dcfce7; }
    .esign-status.rejected { color:#991b1b; background:#fee2e2; }
    .esign-instruction { font-size:.88rem; line-height:1.55; color:#475569; }
    .esign-form-label { font-size:.76rem; text-transform:uppercase; letter-spacing:.05em; color:#64748b; font-weight:800; }
    .esign-position-values { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:8px; }
    .esign-position-values .form-control { background:#f8fafc; }
    @media(max-width:991.98px) { .esign-layout { grid-template-columns:1fr; } .esign-workspace { min-height:560px; padding:12px; } }
    @media(max-width:575.98px) { .esign-toolbar { align-items:flex-start; flex-direction:column; } .esign-box { padding:3px; } }
</style>
@endpush

@section('content')
@include('admin._alerts')

<div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
    <div>
        <h3 class="mb-1">Ajukan E-Sign Surat Keluar</h3>
        <div class="text-muted">{{ $suratKeluar->nomor_surat_formatted }} &mdash; {{ $suratKeluar->perihal }}</div>
    </div>
    <a href="{{ route('surat-keluar.index') }}" class="btn btn-outline-secondary mt-2 mt-md-0"><i class="fas fa-arrow-left mr-1"></i>Kembali</a>
</div>

<form action="{{ route('surat-keluar.esign.store', $suratKeluar) }}" method="POST" id="esignForm">
    @csrf
    <div class="esign-layout">
        <section class="card esign-card border-0 mb-0">
            <div class="esign-toolbar">
                <div>
                    <strong>Posisi TTE pada PDF</strong>
                    <div class="text-muted small">Geser gambar TTE dan tarik sudut kanan bawah untuk mengubah ukurannya.</div>
                </div>
                <div class="esign-page-control">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="prevPage" aria-label="Halaman sebelumnya"><i class="fas fa-chevron-left"></i></button>
                    <span class="small">Halaman <strong id="pageNumber">{{ old('page', $placement['page'] ?? 1) }}</strong> / {{ $pageCount }}</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="nextPage" aria-label="Halaman berikutnya"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
            <div class="esign-workspace">
                <div class="esign-page" id="pdfPage">
                    <canvas id="pdfCanvas"></canvas>
                    <div class="esign-box" id="esignBox" role="button" aria-label="Gambar TTE yang dapat dipindahkan">
                        <img class="esign-box-signature" id="esignBoxSignature" src="" alt="Pratinjau TTE penanda tangan">
                        <span class="esign-resize" id="resizeHandle"></span>
                    </div>
                </div>
                <div class="text-muted py-5" id="pdfLoading"><i class="fas fa-spinner fa-spin mr-1"></i>Memuat PDF...</div>
            </div>
        </section>

        <aside class="card esign-card border-0 mb-0">
            <div class="card-body">
                @if($approval)
                    <div class="mb-3">
                        <div class="esign-form-label mb-2">Status E-Sign Saat Ini</div>
                        <span class="esign-status {{ $approval->status }}">{{ $approval->status_label }}</span>
                        @if($approval->status === 'approved')
                            <a href="{{ route('surat-keluar.file', $suratKeluar) }}" target="_blank" class="btn btn-sm btn-outline-success ml-1"><i class="fas fa-file-signature mr-1"></i>Lihat Hasil</a>
                        @endif
                        @if($approval->note)
                            <div class="small text-muted mt-2">Catatan: {{ $approval->note }}</div>
                        @endif
                    </div>
                @endif

                <div class="form-group">
                    <label class="esign-form-label" for="approverId">Ajukan kepada</label>
                    <select name="approver_id" id="approverId" class="form-control @error('approver_id') is-invalid @enderror" required>
                        <option value="">Pilih penanda tangan</option>
                        @foreach($tteSigners as $signer)
                            <option value="{{ $signer['user_id'] }}" data-key="{{ $signer['key'] }}" data-name="{{ $signer['name'] }}" data-title="{{ $signer['title'] }}" data-tte-url="{{ $signer['url'] }}"
                                {{ (string) old('approver_id', optional($approval)->approver_id) === (string) $signer['user_id'] ? 'selected' : '' }}>
                                {{ $signer['label'] }} — {{ $signer['name'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('approver_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('tte_key')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <input type="hidden" name="tte_key" id="tteKey" value="{{ old('tte_key', data_get(optional($approval)->field_values, 'tte.key')) }}">
                    <div class="esign-signer-preview" id="signerPreview" aria-live="polite">
                        <img id="previewTte" src="" alt="TTE penanda tangan terpilih">
                        <div class="small"><strong id="previewSigner">Pilih penanda tangan</strong><br><span class="text-muted" id="previewSignerTitle"></span></div>
                    </div>
                    @if($tteSigners->isEmpty())
                        <div class="alert alert-warning mt-2 mb-0 small">Belum ada pegawai aktif yang cocok dengan berkas TTE di <code>public/tte</code>.</div>
                    @endif
                </div>

                <div class="esign-form-label mb-2">Koordinat Kotak</div>
                <div class="esign-position-values mb-3">
                    <div><small class="text-muted">X (%)</small><input class="form-control form-control-sm" id="positionX" name="x" readonly value="{{ old('x', $placement['x'] ?? 58) }}"></div>
                    <div><small class="text-muted">Y (%)</small><input class="form-control form-control-sm" id="positionY" name="y" readonly value="{{ old('y', $placement['y'] ?? 76) }}"></div>
                    <div><small class="text-muted">Lebar (%)</small><input class="form-control form-control-sm" id="positionWidth" name="width" readonly value="{{ old('width', $placement['width'] ?? 38) }}"></div>
                    <div><small class="text-muted">Tinggi (%)</small><input class="form-control form-control-sm" id="positionHeight" name="height" readonly value="{{ old('height', $placement['height'] ?? 14) }}"></div>
                </div>
                <input type="hidden" name="page" id="positionPage" value="{{ old('page', $placement['page'] ?? 1) }}">
                @error('position')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
                @error('page')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror

                <div class="alert alert-info esign-instruction">
                    Setelah penanda tangan menyetujui approval, sistem menempelkan gambar TTE yang dipilih ke PDF final. Tidak ada barcode atau QR yang dibuat pada dokumen.
                </div>
                @if($approval && $approval->status === 'approved')
                    <div class="alert alert-warning esign-instruction">Mengajukan ulang akan membuat dokumen menunggu tanda tangan kembali dengan posisi yang baru.</div>
                @endif
                <button type="submit" class="btn btn-primary btn-block" id="submitEsign"><i class="fas fa-file-signature mr-1"></i>{{ $approval ? 'Simpan & Ajukan E-Sign' : 'Ajukan E-Sign' }}</button>
            </div>
        </aside>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(function () {
    const sourceUrl = @json(route('surat-keluar.esign.source', $suratKeluar));
    const maxPages = {{ (int) $pageCount }};
    const canvas = document.getElementById('pdfCanvas');
    const pageElement = document.getElementById('pdfPage');
    const box = document.getElementById('esignBox');
    const resizeHandle = document.getElementById('resizeHandle');
    const loading = document.getElementById('pdfLoading');
    const pageLabel = document.getElementById('pageNumber');
    const pageInput = document.getElementById('positionPage');
    const fields = {
        x: document.getElementById('positionX'), y: document.getElementById('positionY'),
        width: document.getElementById('positionWidth'), height: document.getElementById('positionHeight')
    };
    let pdfDocument = null;
    let currentPage = Math.max(1, Math.min(maxPages, Number(pageInput.value) || 1));
    let placement = {
        x: Number(fields.x.value) || 58, y: Number(fields.y.value) || 76,
        width: Number(fields.width.value) || 38, height: Number(fields.height.value) || 14
    };

    function clamp(value, min, max) { return Math.max(min, Math.min(max, value)); }
    function syncBox() {
        placement.width = clamp(placement.width, 30, 60);
        placement.height = clamp(placement.height, 12, 30);
        placement.x = clamp(placement.x, 0, 100 - placement.width);
        placement.y = clamp(placement.y, 0, 100 - placement.height);
        box.style.left = placement.x + '%'; box.style.top = placement.y + '%';
        box.style.width = placement.width + '%'; box.style.height = placement.height + '%';
        Object.keys(fields).forEach(function (key) { fields[key].value = placement[key].toFixed(3); });
        pageInput.value = currentPage; pageLabel.textContent = currentPage;
        document.getElementById('prevPage').disabled = currentPage <= 1;
        document.getElementById('nextPage').disabled = currentPage >= maxPages;
    }

    function renderPage() {
        if (!pdfDocument) return;
        loading.classList.remove('d-none');
        pdfDocument.getPage(currentPage).then(function (page) {
            const viewport = page.getViewport({scale: 1.55});
            canvas.width = viewport.width; canvas.height = viewport.height;
            return page.render({canvasContext: canvas.getContext('2d'), viewport: viewport}).promise;
        }).then(function () {
            loading.classList.add('d-none'); syncBox();
        }).catch(function () {
            loading.innerHTML = '<span class="text-danger">Preview PDF gagal dimuat. Muat ulang halaman atau periksa berkas PDF.</span>';
        });
    }

    if (!window.pdfjsLib) {
        loading.innerHTML = '<span class="text-danger">Komponen preview PDF gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.</span>';
        document.getElementById('submitEsign').disabled = true;
    } else {
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        pdfjsLib.getDocument({url: sourceUrl, withCredentials: true}).promise.then(function (pdf) {
            pdfDocument = pdf; renderPage();
        }).catch(function () {
            loading.innerHTML = '<span class="text-danger">PDF tidak dapat ditampilkan.</span>';
        });
    }

    function beginPointer(event, resizing) {
        event.preventDefault(); event.stopPropagation();
        const rect = pageElement.getBoundingClientRect();
        const start = {x:event.clientX, y:event.clientY, placement:Object.assign({}, placement)};
        function move(pointer) {
            const dx = ((pointer.clientX - start.x) / rect.width) * 100;
            const dy = ((pointer.clientY - start.y) / rect.height) * 100;
            if (resizing) {
                placement.width = start.placement.width + dx;
                placement.height = start.placement.height + dy;
            } else {
                placement.x = start.placement.x + dx;
                placement.y = start.placement.y + dy;
            }
            syncBox();
        }
        function end() { window.removeEventListener('pointermove', move); window.removeEventListener('pointerup', end); }
        window.addEventListener('pointermove', move); window.addEventListener('pointerup', end);
    }
    box.addEventListener('pointerdown', function (event) { if (event.target !== resizeHandle) beginPointer(event, false); });
    resizeHandle.addEventListener('pointerdown', function (event) { beginPointer(event, true); });
    document.getElementById('prevPage').addEventListener('click', function () { if (currentPage > 1) { currentPage--; renderPage(); } });
    document.getElementById('nextPage').addEventListener('click', function () { if (currentPage < maxPages) { currentPage++; renderPage(); } });

    const approver = document.getElementById('approverId');
    const tteKey = document.getElementById('tteKey');
    const previewTte = document.getElementById('previewTte');
    const boxSignature = document.getElementById('esignBoxSignature');
    function updateSignerPreview() {
        const option = approver.options[approver.selectedIndex];
        const selected = option && option.value;
        const url = selected ? option.dataset.tteUrl : '';
        document.getElementById('previewSigner').textContent = selected ? option.dataset.name : 'Pilih penanda tangan';
        document.getElementById('previewSignerTitle').textContent = selected ? (option.dataset.title || '') : '';
        tteKey.value = selected ? option.dataset.key : '';
        previewTte.src = url;
        previewTte.style.visibility = selected ? 'visible' : 'hidden';
        previewTte.alt = selected ? 'TTE ' + option.dataset.name : 'TTE penanda tangan terpilih';
        boxSignature.src = url;
        boxSignature.style.visibility = selected ? 'visible' : 'hidden';
        boxSignature.alt = selected ? 'TTE ' + option.dataset.name : 'Pratinjau TTE penanda tangan';
    }
    approver.addEventListener('change', updateSignerPreview); updateSignerPreview(); syncBox();
})();
</script>
@endpush
