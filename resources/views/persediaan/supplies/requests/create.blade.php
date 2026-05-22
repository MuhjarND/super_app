@extends('layouts.app')

@section('title', 'Ajukan Persediaan')

@push('styles')
@include('persediaan.supplies._styles')
@endpush

@section('content')
@include('admin._alerts')

<div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1 class="inventory-module-title mb-0">Ajukan Persediaan</h1>
    <div class="supply-action-row">
        <a href="{{ route('persediaan.requests.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#customSupplyItemModal">
            <i class="fas fa-plus mr-1"></i> Barang Baru
        </button>
    </div>
</div>

<div class="inventory-module-shell supply-request-shop">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="inventory-module-board-title"><i class="fas fa-th-large text-muted mr-1"></i> Katalog Barang</div>
            <span class="inventory-module-chip"><span id="selectedSupplyCount">0</span> terpilih</span>
        </div>
        <div class="inventory-module-board-body">
            <form method="POST" action="{{ route('persediaan.requests.store') }}" id="supplyRequestForm" class="mb-0">
                @csrf

                <div class="supply-search-bar mb-3">
                    <i class="fas fa-search"></i>
                    <input type="search" id="supplyCatalogSearch" class="form-control" placeholder="Cari barang..." autocomplete="off">
                </div>

                <div class="inventory-module-panel mb-3">
                    <div class="inventory-module-panel-body">
                        @if($items->count())
                            <div class="supply-shop-grid">
                                @foreach($items as $index => $item)
                                    @php($initialQty = (string) $selectedItemId === (string) $item->id && (int) $item->stock > 0 ? 1 : 0)
                                    <div class="supply-shop-card {{ $initialQty > 0 ? 'is-selected' : '' }}" data-supply-card data-supply-search="{{ \Illuminate\Support\Str::lower(trim($item->name . ' ' . $item->code . ' ' . $item->description)) }}">
                                        <div class="supply-shop-image">
                                            @if($item->image_url)
                                                <img src="{{ $item->image_url }}" alt="{{ $item->name }}">
                                            @else
                                                <i class="fas fa-box-open text-muted"></i>
                                            @endif
                                        </div>
                                        <div class="supply-shop-body">
                                            <div>
                                                <div class="supply-shop-name">{{ $item->name }}</div>
                                                <div class="supply-shop-meta-row mt-2">
                                                    <span class="inventory-module-muted">{{ $item->code ?: '-' }}</span>
                                                    <span class="supply-stock-pill {{ $item->is_low_stock ? 'low' : '' }}">{{ $item->stock_label }}</span>
                                                </div>
                                            </div>

                                            <input type="hidden" name="items[{{ $index }}][supply_item_id]" value="{{ $item->id }}">
                                            <div class="supply-qty-control">
                                                <button type="button" class="supply-qty-btn" data-qty-minus aria-label="Kurangi {{ $item->name }}">-</button>
                                                <input type="number" name="items[{{ $index }}][quantity]" class="supply-qty-input" value="{{ $initialQty }}" min="0" max="{{ $item->stock }}" data-qty-input data-stock="{{ $item->stock }}">
                                                <button type="button" class="supply-qty-btn plus" data-qty-plus aria-label="Tambah {{ $item->name }}">+</button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="inventory-module-empty d-none mt-3" id="supplyCatalogEmpty">Barang tidak ditemukan.</div>
                        @else
                            <div class="inventory-module-empty"><i class="far fa-folder-open"></i> Belum ada barang aktif</div>
                        @endif
                    </div>
                </div>

                <input type="hidden" name="items[custom][supply_item_id]" value="custom">
                <input type="hidden" name="items[custom][custom_item_name]" data-custom-name-input>
                <input type="hidden" name="items[custom][custom_unit]" value="Pcs" data-custom-unit-input>
                <input type="hidden" name="items[custom][quantity]" value="0" data-custom-qty-input>

                <div class="supply-purpose-card">
                    <label for="supplyPurpose" class="mb-2">Keperluan</label>
                    <textarea id="supplyPurpose" name="purpose" rows="2" class="form-control" required placeholder="Contoh: ATK pelayanan PTSP.">{{ old('purpose') }}</textarea>
                    <div class="supply-custom-summary d-none" id="customSupplySummary"></div>
                    <div class="supply-action-row mt-3">
                        <button type="submit" class="btn btn-sm app-create-btn"><i class="fas fa-paper-plane mr-1"></i> Kirim</button>
                        <a href="{{ route('persediaan.requests.index') }}" class="btn btn-sm btn-outline-secondary">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="customSupplyItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 px-3">
                <h5 class="modal-title">Barang Baru</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body py-2 px-3">
                <div class="form-group mb-2">
                        <label class="mb-1">Nama Barang</label>
                        <input type="text" class="form-control form-control-sm" id="customSupplyName" placeholder="Nama barang yang diinginkan">
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group mb-0">
                            <label class="mb-1">Satuan</label>
                            <input type="text" class="form-control form-control-sm" id="customSupplyUnit" value="Pcs">
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="mb-1">Jumlah</label>
                        <div class="supply-qty-control">
                            <button type="button" class="supply-qty-btn" data-custom-minus>-</button>
                            <input type="number" class="supply-qty-input" id="customSupplyQty" value="1" min="0">
                            <button type="button" class="supply-qty-btn plus" data-custom-plus>+</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm app-create-btn" id="applyCustomSupplyItem">Pilih</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var selectedCounter = document.getElementById('selectedSupplyCount');
        var customNameModal = document.getElementById('customSupplyName');
        var customUnitModal = document.getElementById('customSupplyUnit');
        var customQtyModal = document.getElementById('customSupplyQty');
        var customNameInput = document.querySelector('[data-custom-name-input]');
        var customUnitInput = document.querySelector('[data-custom-unit-input]');
        var customQtyInput = document.querySelector('[data-custom-qty-input]');
        var customSummary = document.getElementById('customSupplySummary');
        var catalogSearch = document.getElementById('supplyCatalogSearch');
        var catalogEmpty = document.getElementById('supplyCatalogEmpty');
 
        function clamp(value, min, max) {
            var number = parseInt(value, 10);
            if (isNaN(number)) {
                number = 0;
            }
            number = Math.max(min, number);
            if (typeof max === 'number' && max >= 0) {
                number = Math.min(max, number);
            }
            return number;
        }
 
        function updateCard(input) {
            var card = input.closest('[data-supply-card]');
            if (!card) {
                return;
            }
            card.classList.toggle('is-selected', parseInt(input.value || '0', 10) > 0);
        }
 
        function hasCustomItem() {
            return customNameInput.value.trim() !== '' && parseInt(customQtyInput.value || '0', 10) > 0;
        }
 
        function updateCustomSummary() {
            if (!hasCustomItem()) {
                customSummary.classList.add('d-none');
                customSummary.textContent = '';
                return;
            }
 
            customSummary.classList.remove('d-none');
            customSummary.textContent = 'Barang baru: ' + customNameInput.value + ' (' + customQtyInput.value + ' ' + (customUnitInput.value || 'Pcs') + ')';
        }
 
        function updateSelectedCount() {
            var count = 0;
            document.querySelectorAll('[data-qty-input]').forEach(function (input) {
                if (parseInt(input.value || '0', 10) > 0) {
                    count++;
                }
            });
 
            if (hasCustomItem()) {
                count++;
            }
 
            selectedCounter.textContent = count;
            updateCustomSummary();
        }
 
        document.querySelectorAll('[data-supply-card]').forEach(function (card) {
            var input = card.querySelector('[data-qty-input]');
            var max = parseInt(input.dataset.stock || '0', 10);
 
            card.querySelector('[data-qty-minus]').addEventListener('click', function () {
                input.value = clamp(input.value - 1, 0, max);
                updateCard(input);
                updateSelectedCount();
            });
 
            card.querySelector('[data-qty-plus]').addEventListener('click', function () {
                input.value = clamp(parseInt(input.value || '0', 10) + 1, 0, max);
                updateCard(input);
                updateSelectedCount();
            });
 
            input.addEventListener('input', function () {
                input.value = clamp(input.value, 0, max);
                updateCard(input);
                updateSelectedCount();
            });
 
            updateCard(input);
        });
 
        if (catalogSearch) {
            catalogSearch.addEventListener('input', function () {
                var keyword = catalogSearch.value.trim().toLowerCase();
                var visibleCount = 0;
 
                document.querySelectorAll('[data-supply-card]').forEach(function (card) {
                    var haystack = (card.dataset.supplySearch || '').toLowerCase();
                    var matched = keyword === '' || haystack.indexOf(keyword) !== -1;
                    card.classList.toggle('d-none', !matched);
 
                    if (matched) {
                        visibleCount++;
                    }
                });
 
                if (catalogEmpty) {
                    catalogEmpty.classList.toggle('d-none', visibleCount > 0);
                }
            });
        }
 
        document.querySelector('[data-custom-minus]').addEventListener('click', function () {
            customQtyModal.value = clamp(customQtyModal.value - 1, 0);
        });
 
        document.querySelector('[data-custom-plus]').addEventListener('click', function () {
            customQtyModal.value = clamp(parseInt(customQtyModal.value || '0', 10) + 1, 0);
        });
 
        customQtyModal.addEventListener('input', function () {
            customQtyModal.value = clamp(customQtyModal.value, 0);
        });
 
        document.getElementById('applyCustomSupplyItem').addEventListener('click', function () {
            var name = customNameModal.value.trim();
            var quantity = clamp(customQtyModal.value, 0);
 
            if (name === '' || quantity < 1) {
                showToast('Isi nama dan jumlah barang baru.', 'error');
                return;
            }
 
            customNameInput.value = name;
            customUnitInput.value = customUnitModal.value.trim() || 'Pcs';
            customQtyInput.value = quantity;
            updateSelectedCount();
            $('#customSupplyItemModal').modal('hide');
        });
 
        document.getElementById('supplyRequestForm').addEventListener('submit', function (event) {
            var selected = parseInt(selectedCounter.textContent || '0', 10);
            if (selected < 1) {
                event.preventDefault();
                showToast('Pilih minimal satu barang.', 'error');
            }
        });
 
        updateSelectedCount();
    })();
</script>
@endpush
