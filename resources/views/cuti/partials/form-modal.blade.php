@php
    $isEdit = $mode === 'edit';
    $modalId = $isEdit ? 'editLeaveRequestModal' : 'createLeaveRequestModal';
    $formId = $isEdit ? 'editLeaveRequestForm' : 'createLeaveRequestForm';
    $title = $isEdit ? 'Edit Pengajuan Cuti' : 'Buat Pengajuan Cuti';
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" role="dialog" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <form id="{{ $formId }}" action="{{ $isEdit ? '#' : route('cuti.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @if($isEdit)
                    @method('PUT')
                @endif
                <input type="hidden" name="_leave_form_mode" value="{{ $mode }}">
                <input type="hidden" name="_leave_request_id" value="{{ $isEdit ? old('_leave_request_id') : '' }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="{{ $modalId }}Label">{{ $title }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>Jenis Cuti</label>
                        <select name="leave_type_id" class="form-control" required>
                            <option value="">-- Pilih --</option>
                            @foreach($leaveTypes as $leaveType)
                                <option value="{{ $leaveType->id }}" {{ !$isEdit && (string) old('leave_type_id') === (string) $leaveType->id ? 'selected' : '' }}>
                                    {{ $leaveType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label>Tanggal Mulai</label>
                            <input type="date" name="start_date" class="form-control" value="{{ !$isEdit ? old('start_date') : '' }}" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Tanggal Selesai</label>
                            <input type="date" name="end_date" class="form-control" value="{{ !$isEdit ? old('end_date') : '' }}" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Tujuan / Pokok Permohonan</label>
                        <input type="text" name="purpose" class="form-control" value="{{ !$isEdit ? old('purpose') : '' }}" required>
                    </div>

                    <div class="form-group">
                        <label>Alamat Selama Menjalankan Cuti</label>
                        <input type="text" name="leave_address" class="form-control" value="{{ !$isEdit ? old('leave_address') : '' }}" required>
                    </div>

                    <div class="form-group mb-0">
                        <label>Dokumen Pendukung</label>
                        <input type="file" name="documents[]" class="form-control-file" multiple>
                        <small class="text-muted">PDF/JPG/JPEG/PNG/DOC/DOCX, maksimal 10MB per file. Cuti sakit lebih dari 1 hari wajib surat dokter.</small>
                    </div>

                    @if($isEdit)
                        <div class="mt-3 d-none" id="editLeaveRequestExistingDocs">
                            <div class="font-weight-600 mb-2">Dokumen Saat Ini</div>
                            <div id="editLeaveRequestExistingDocsList" class="small"></div>
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan Draft</button>
                </div>
            </form>
        </div>
    </div>
</div>
