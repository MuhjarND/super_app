<div class="modal fade" id="{{ $modalId }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $title }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="{{ $formId }}" action="{{ $action }}" method="POST">
                @csrf
                @php
                    $prefix = \Illuminate\Support\Str::contains($formId, 'edit') ? 'edit' : 'create';
                @endphp
                @if($method === 'PUT')
                    @method('PUT')
                @endif
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Tanggal Kegiatan <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_kegiatan" id="{{ $prefix }}TanggalKegiatan" class="form-control" required value="{{ $prefix === 'create' ? now()->format('Y-m-d') : '' }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Waktu WIT <span class="text-danger">*</span></label>
                            <input type="time" name="waktu" id="{{ $prefix }}Waktu" class="form-control" required step="60" value="{{ $prefix === 'create' ? now()->timezone('Asia/Jayapura')->format('H:i') : '' }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Judul Agenda <span class="text-danger">*</span></label>
                        <input type="text" name="judul_agenda" id="{{ $prefix }}JudulAgenda" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Tempat <span class="text-danger">*</span></label>
                        <input type="text" name="tempat" id="{{ $prefix }}Tempat" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Yang Menghadiri</label>
                        <textarea name="yang_menghadiri" id="{{ $prefix }}YangMenghadiri" class="form-control" rows="2" placeholder="Contoh: Ketua, Wakil Ketua, Sekretaris"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Seragam / Pakaian</label>
                            <input type="text" name="seragam_pakaian" id="{{ $prefix }}SeragamPakaian" class="form-control" placeholder="Contoh: PSL atau Batik Korpri">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Nomor Naskah Dinas</label>
                            <input type="text" name="nomor_naskah_dinas" id="{{ $prefix }}NomorNaskahDinas" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Lampiran Link</label>
                        <input type="url" name="lampiran_link" id="{{ $prefix }}LampiranLink" class="form-control" placeholder="https://...">
                        <small class="form-text text-muted">Lampiran menggunakan link, bukan upload file.</small>
                    </div>

                    <div class="form-group">
                        <label>Peserta Penerima</label>
                        <select name="recipient_ids[]" id="{{ $prefix }}RecipientIds" class="form-control select2" multiple>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->name }}{{ $user->jabatan ? ' - ' . $user->jabatan->nama : '' }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Urutan penerima akan mengikuti hirarki user.</small>
                    </div>

                    <div class="form-group mb-0">
                        <label>Catatan</label>
                        <textarea name="catatan" id="{{ $prefix }}Catatan" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
