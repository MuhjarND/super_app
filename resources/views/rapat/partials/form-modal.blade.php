@php
    $prefix = \Illuminate\Support\Str::contains($formId, 'edit') ? 'edit' : 'create';
    $isCreate = $prefix === 'create';
@endphp

<div class="modal fade rapat-form-modal" id="{{ $modalId }}" tabindex="-1">
    <div class="modal-dialog {{ $isCreate ? 'modal-lg' : 'modal-xl' }} rapat-modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">{{ $title }}</h5>
                    <div class="rapat-modal-subtitle">
                        {{ $isCreate ? 'Isi jadwal, tempat, dan peserta. Opsi lain bisa dibuka jika diperlukan.' : 'Perbarui informasi rapat dan pengaturan undangan.' }}
                    </div>
                </div>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="{{ $formId }}" data-action="{{ $action }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="rapat-form-section">
                        <div class="rapat-form-section-title">Informasi Rapat</div>
                        <div class="row">
                            <div class="col-md-12 form-group">
                                <label>Judul <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="judul" id="{{ $prefix }}Judul" required placeholder="Contoh: Rapat monitoring bulanan">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Kode Surat <span class="text-danger">*</span></label>
                                <select class="form-control" name="kategori_surat_kode_id" id="{{ $prefix }}KategoriSuratKode" required>
                                    <option value="">Pilih kode</option>
                                    @foreach($kategoriSuratOptions as $kategori)
                                        <option value="{{ $kategori['id'] }}" data-butuh-pakaian="{{ $kategori['butuh_pakaian'] ? 1 : 0 }}" title="{{ $kategori['full_label'] }}">
                                            {{ $kategori['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Nomenklatur <span class="text-danger">*</span></label>
                                <select class="form-control" name="nomenklatur_jabatan" id="{{ $prefix }}NomenklaturJabatan" required>
                                    <option value="ketua">Ketua (KPTA)</option>
                                    <option value="wakil_ketua">Wakil Ketua (WKPTA)</option>
                                    <option value="sekretaris" selected>Sekretaris (SEK)</option>
                                    <option value="panitera">Panitera (PAN)</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Tanggal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal" id="{{ $prefix }}Tanggal" required value="{{ $isCreate ? now()->format('Y-m-d') : '' }}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Waktu WIT <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" name="waktu_mulai" id="{{ $prefix }}WaktuMulai" required step="60" value="{{ $isCreate ? now()->timezone('Asia/Jayapura')->format('H:i') : '' }}">
                            </div>
                            <div class="col-md-12 form-group">
                                <label>Tempat <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="tempat" id="{{ $prefix }}Tempat" required placeholder="Contoh: Ruang Sidang Utama">
                            </div>
                            <div class="col-md-12 form-group mb-0">
                                <label>Deskripsi</label>
                                <textarea class="form-control" name="deskripsi" id="{{ $prefix }}Deskripsi" rows="2" placeholder="Opsional"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="rapat-form-section">
                        <div class="rapat-form-section-title">Peserta</div>
                        <div class="form-group mb-0 rapat-participant-picker">
                            <div class="rapat-participant-toolbar">
                                <label>Peserta Undangan <span class="text-danger">*</span></label>
                                <div class="rapat-participant-actions">
                                    @if(isset($participantUnits) && collect($participantUnits)->isNotEmpty())
                                        <div class="dropdown">
                                            <button type="button" class="btn rapat-select-unit-trigger dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-sitemap mr-1"></i> Pilih Unit
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right rapat-unit-menu">
                                                @foreach($participantUnits as $participantUnit)
                                                    <button type="button" class="dropdown-item rapat-select-unit-participants"
                                                        data-target="#{{ $prefix }}PesertaIds" data-unit-id="{{ $participantUnit['id'] }}">
                                                        <span><i class="far fa-square mr-2"></i>{{ $participantUnit['name'] }}</span>
                                                        <span class="rapat-unit-count">{{ $participantUnit['count'] }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    <button type="button" class="btn rapat-select-all-participants" data-target="#{{ $prefix }}PesertaIds">
                                        <i class="fas fa-check-double mr-1"></i> Pilih Semua
                                    </button>
                                </div>
                            </div>
                            <select class="form-control select2" name="peserta_ids[]" id="{{ $prefix }}PesertaIds" multiple required data-participant-select="1">
                                @foreach($participants as $participant)
                                    <option value="{{ $participant->id }}" data-unit-id="{{ $participant->unit_id }}">{{ $participant->name }}</option>
                                @endforeach
                            </select>
                            <small class="form-hint">Pilih pegawai yang diundang.</small>
                        </div>
                    </div>

                    <details class="rapat-advanced" {{ $isCreate ? '' : 'open' }}>
                        <summary>
                            <span>Pengaturan lanjutan</span>
                            <small>Approval, pakaian, virtual, berkala, dan lampiran</small>
                        </summary>

                        <div class="rapat-advanced-body">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Approver 1</label>
                                    <select class="form-control select2" name="approver_1_id" id="{{ $prefix }}Approver1Id">
                                        <option value="">Pilih approver</option>
                                        @foreach($approvers as $approver)
                                            <option value="{{ $approver->id }}">{{ $approver->name }}{{ $approver->jabatan ? ' - ' . $approver->jabatan->nama : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Approver 2</label>
                                    <select class="form-control select2" name="approver_2_id" id="{{ $prefix }}Approver2Id">
                                        <option value="">Pilih approver</option>
                                        @foreach($approvers as $approver)
                                            <option value="{{ $approver->id }}">{{ $approver->name }}{{ $approver->jabatan ? ' - ' . $approver->jabatan->nama : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Jabatan Manual Approver 1</label>
                                    <input type="text" class="form-control" name="approval1_jabatan_manual" id="{{ $prefix }}Approval1JabatanManual" placeholder="Opsional">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Tujuan Surat <span class="text-danger" id="{{ $prefix }}TujuanSuratRequired" style="display:none;">*</span></label>
                                    <textarea class="form-control" name="tujuan_surat" id="{{ $prefix }}TujuanSurat" rows="2" placeholder="Opsional"></textarea>
                                </div>
                            </div>

                            <div class="rapat-option-grid">
                                <label class="rapat-option-card" for="{{ $prefix }}IncludePakaian">
                                    <input type="checkbox" name="include_pakaian" value="1" id="{{ $prefix }}IncludePakaian">
                                    <span>
                                        <strong>Pakaian</strong>
                                        <small>Tambahkan informasi pakaian.</small>
                                    </span>
                                </label>
                                <label class="rapat-option-card" for="{{ $prefix }}IncludeDetailTambahan">
                                    <input type="checkbox" name="include_detail_tambahan" value="1" id="{{ $prefix }}IncludeDetailTambahan">
                                    <span>
                                        <strong>Detail pembuka</strong>
                                        <small>Tambahan kalimat pembuka surat.</small>
                                    </span>
                                </label>
                                <label class="rapat-option-card" for="{{ $prefix }}IsVirtual">
                                    <input type="checkbox" name="is_virtual" value="1" id="{{ $prefix }}IsVirtual">
                                    <span>
                                        <strong>Virtual</strong>
                                        <small>Tambahkan ID dan passcode.</small>
                                    </span>
                                </label>
                                <label class="rapat-option-card" for="{{ $prefix }}IsRecurring">
                                    <input type="checkbox" name="is_recurring" value="1" id="{{ $prefix }}IsRecurring">
                                    <span>
                                        <strong>Berkala</strong>
                                        <small>Atur pola jadwal.</small>
                                    </span>
                                </label>
                                <label class="rapat-option-card" for="{{ $prefix }}GunakanLampiran">
                                    <input type="checkbox" name="gunakan_lampiran_tambahan" value="1" id="{{ $prefix }}GunakanLampiran">
                                    <span>
                                        <strong>Lampiran</strong>
                                        <small>Upload dokumen tambahan.</small>
                                    </span>
                                </label>
                            </div>

                            <div id="{{ $prefix }}PakaianGroup" class="rapat-conditional-field" style="display:none;">
                                <label>Jenis Pakaian</label>
                                <input type="text" class="form-control" name="jenis_pakaian" id="{{ $prefix }}JenisPakaian" placeholder="Contoh: PSL / Batik Korpri">
                            </div>

                            <div id="{{ $prefix }}DetailTambahanGroup" class="rapat-conditional-field" style="display:none;">
                                <label>Detail Tambahan</label>
                                <textarea class="form-control" name="detail_tambahan" id="{{ $prefix }}DetailTambahan" rows="2" placeholder="Contoh: pelaksanaan rapat koordinasi internal menjelang evaluasi triwulan."></textarea>
                            </div>

                            <div id="{{ $prefix }}VirtualGroup" class="rapat-conditional-field" style="display:none;">
                                <div class="row">
                                    <div class="col-md-6 form-group mb-md-0">
                                        <label>Meeting ID</label>
                                        <input type="text" class="form-control" name="meeting_id" id="{{ $prefix }}MeetingId">
                                    </div>
                                    <div class="col-md-6 form-group mb-0">
                                        <label>Meeting Passcode</label>
                                        <input type="text" class="form-control" name="meeting_passcode" id="{{ $prefix }}MeetingPasscode">
                                    </div>
                                </div>
                            </div>

                            <div id="{{ $prefix }}RecurringGroup" class="rapat-conditional-field" style="display:none;">
                                <div class="row">
                                    <div class="col-md-6 form-group mb-md-0">
                                        <label>Pola Jadwal</label>
                                        <select class="form-control" name="recurring_pattern" id="{{ $prefix }}RecurringPattern">
                                            <option value="">Pilih pola</option>
                                            <option value="harian">Harian</option>
                                            <option value="mingguan">Mingguan</option>
                                            <option value="bulanan">Bulanan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 form-group mb-0">
                                        <label>Sampai Tanggal</label>
                                        <input type="date" class="form-control" name="recurring_until" id="{{ $prefix }}RecurringUntil">
                                    </div>
                                </div>
                            </div>

                            <div id="{{ $prefix }}LampiranGroup" class="rapat-conditional-field" style="display:none;">
                                <label>Upload Dokumen Tambahan</label>
                                <input type="file" class="form-control-file" name="lampiran_tambahan" id="{{ $prefix }}Lampiran" accept=".pdf,.jpg,.jpeg,.png">
                                <small class="form-hint">PDF atau gambar JPG/PNG. Maksimal 10MB.</small>
                                @if($prefix === 'edit')
                                    <div class="form-check mt-2">
                                        <input type="checkbox" class="form-check-input" name="hapus_lampiran_tambahan" value="1" id="{{ $prefix }}HapusLampiranTambahan">
                                        <label class="form-check-label" for="{{ $prefix }}HapusLampiranTambahan">Hapus lampiran tambahan saat ini</label>
                                    </div>
                                    <small id="{{ $prefix }}LampiranInfo" class="form-hint" style="display:none;">Lampiran saat ini tersedia dan bisa diganti atau dihapus.</small>
                                @endif
                            </div>
                        </div>
                    </details>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn app-create-btn">{{ $submitLabel }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
