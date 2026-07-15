<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ $formAction }}" id="{{ $formId }}">
                @csrf
                @if($method !== 'POST') @method($method) @endif
                <div class="modal-header">
                    <h5 class="modal-title">{{ $modalTitle }}</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Barang</label>
                            <select name="inventory_item_id" class="form-control" required
                                data-schedule-item data-detail-target="#{{ $formId }}Detail">
                                <option value="">Pilih barang</option>
                                @foreach($items as $item)
                                    <option value="{{ $item->id }}">{{ $item->code }} - {{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Sub Barang</label>
                            <select name="inventory_item_detail_id" id="{{ $formId }}Detail" class="form-control">
                                <option value="">Tanpa sub barang</option>
                                @foreach($items as $item)
                                    @foreach($item->details as $detail)
                                        <option value="{{ $detail->id }}" data-item="{{ $item->id }}">
                                            {{ $detail->sub_code ?: $detail->nup }} - {{ $detail->name }}
                                        </option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Tanggal dan Waktu Perawatan</label>
                        <input type="datetime-local" name="scheduled_at" class="form-control" required>
                    </div>
                    <div class="form-group mb-0">
                        <label>Keterangan Perawatan</label>
                        <textarea name="description" class="form-control" rows="3" maxlength="2000" required placeholder="Contoh: Servis berkala dan pembersihan unit"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn app-create-btn">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
