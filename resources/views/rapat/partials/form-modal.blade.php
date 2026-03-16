<div class="modal fade" id="{{ $modalId }}" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $title }}</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="{{ $formId }}" data-action="{{ $action }}" enctype="multipart/form-data">
                @csrf
                @php
                    $prefix = \Illuminate\Support\Str::contains($formId, 'edit') ? 'edit' : 'create';
                @endphp
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label>Judul <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="judul" id="{{ $prefix }}Judul" required>
                        </div>
                        <div class="col-md-12 form-group">
                            <label>Deskripsi</label>
                            <textarea class="form-control" name="deskripsi" id="{{ $prefix }}Deskripsi" rows="2"></textarea>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Kategori Surat <span class="text-danger">*</span></label>
                            <select class="form-control" name="kategori_surat_kode_id" id="{{ $prefix }}KategoriSuratKode" required>
                                <option value="">-- Pilih Kategori Surat --</option>
                                @foreach($kategoriSuratOptions as $kategori)
                                    <option value="{{ $kategori['id'] }}" data-butuh-pakaian="{{ $kategori['butuh_pakaian'] ? 1 : 0 }}" title="{{ $kategori['full_label'] }}">
                                        {{ $kategori['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Nomenklatur <span class="text-danger">*</span></label>
                            <select class="form-control" name="nomenklatur_jabatan" id="{{ $prefix }}NomenklaturJabatan" required>
                                <option value="ketua">Ketua (KPTA)</option>
                                <option value="wakil_ketua">Wakil Ketua (WKPTA)</option>
                                <option value="sekretaris" selected>Sekretaris (SEK)</option>
                                <option value="panitera">Panitera (PAN)</option>
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal" id="{{ $prefix }}Tanggal" required value="{{ $prefix === 'create' ? now()->format('Y-m-d') : '' }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Waktu Mulai WIT <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="waktu_mulai" id="{{ $prefix }}WaktuMulai" required step="60" value="{{ $prefix === 'create' ? now()->timezone('Asia/Jayapura')->format('H:i') : '' }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Tempat <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="tempat" id="{{ $prefix }}Tempat" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Status <span class="text-danger">*</span></label>
                            <select class="form-control" name="status" id="{{ $prefix }}Status" required>
                                <option value="draft">Draft</option>
                                <option value="terjadwal">Terjadwal</option>
                                <option value="pending_approval">Pending Approval</option>
                                <option value="disetujui">Disetujui</option>
                                <option value="ditolak">Ditolak</option>
                                <option value="dibatalkan">Dibatalkan</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </div>
                        <div class="col-md-12 form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="include_pakaian" value="1" id="{{ $prefix }}IncludePakaian">
                                <label class="form-check-label" for="{{ $prefix }}IncludePakaian">Tambahkan informasi pakaian</label>
                            </div>
                        </div>
                        <div class="col-md-12 form-group" id="{{ $prefix }}PakaianGroup" style="display:none;">
                            <label>Jenis Pakaian</label>
                            <input type="text" class="form-control" name="jenis_pakaian" id="{{ $prefix }}JenisPakaian" placeholder="Contoh: PSL / Batik Korpri">
                        </div>
                        <div class="col-md-12 form-group">
                            <label>Peserta Undangan <span class="text-danger">*</span></label>
                            <select class="form-control select2" name="peserta_ids[]" id="{{ $prefix }}PesertaIds" multiple required>
                                @foreach($participants as $participant)
                                    <option value="{{ $participant->id }}">
                                        {{ $participant->name }}{{ $participant->jabatan_keterangan ? ' - ' . $participant->jabatan_keterangan : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-hint">Daftar peserta diurutkan berdasarkan hirarki user.</small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Approver 1</label>
                            <select class="form-control select2" name="approver_1_id" id="{{ $prefix }}Approver1Id">
                                <option value="">-- Pilih Approver 1 --</option>
                                @foreach($approvers as $approver)
                                    <option value="{{ $approver->id }}">{{ $approver->name }}{{ $approver->jabatan ? ' - ' . $approver->jabatan->nama : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Approver 2</label>
                            <select class="form-control select2" name="approver_2_id" id="{{ $prefix }}Approver2Id">
                                <option value="">-- Pilih Approver 2 --</option>
                                @foreach($approvers as $approver)
                                    <option value="{{ $approver->id }}">{{ $approver->name }}{{ $approver->jabatan ? ' - ' . $approver->jabatan->nama : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Approval 1 Jabatan Manual</label>
                            <input type="text" class="form-control" name="approval1_jabatan_manual" id="{{ $prefix }}Approval1JabatanManual">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Tujuan Surat <span class="text-danger" id="{{ $prefix }}TujuanSuratRequired" style="display:none;">*</span></label>
                            <textarea class="form-control" name="tujuan_surat" id="{{ $prefix }}TujuanSurat" rows="2"></textarea>
                            <small class="form-hint">Wajib diisi jika lampiran tambahan digunakan. Isi tampil apa adanya pada undangan PDF.</small>
                        </div>
                        <div class="col-md-12 form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="include_detail_tambahan" value="1" id="{{ $prefix }}IncludeDetailTambahan">
                                <label class="form-check-label" for="{{ $prefix }}IncludeDetailTambahan">Tambahkan detail pembuka surat</label>
                            </div>
                        </div>
                        <div class="col-md-12 form-group" id="{{ $prefix }}DetailTambahanGroup" style="display:none;">
                            <label>Detail Tambahan</label>
                            <textarea class="form-control" name="detail_tambahan" id="{{ $prefix }}DetailTambahan" rows="2"></textarea>
                            <small class="form-hint">Contoh: pelaksanaan rapat koordinasi internal menjelang evaluasi triwulan.</small>
                        </div>
                        <div class="col-md-12 form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_virtual" value="1" id="{{ $prefix }}IsVirtual">
                                <label class="form-check-label" for="{{ $prefix }}IsVirtual">Rapat Virtual</label>
                            </div>
                        </div>
                        <div class="col-md-12" id="{{ $prefix }}VirtualGroup" style="display:none;">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Meeting ID</label>
                                    <input type="text" class="form-control" name="meeting_id" id="{{ $prefix }}MeetingId">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Meeting Passcode</label>
                                    <input type="text" class="form-control" name="meeting_passcode" id="{{ $prefix }}MeetingPasscode">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="is_recurring" value="1" id="{{ $prefix }}IsRecurring">
                                <label class="form-check-label" for="{{ $prefix }}IsRecurring">Rapat Berkala / Terjadwal</label>
                            </div>
                        </div>
                        <div class="col-md-12" id="{{ $prefix }}RecurringGroup" style="display:none;">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Pola Jadwal</label>
                                    <select class="form-control" name="recurring_pattern" id="{{ $prefix }}RecurringPattern">
                                        <option value="">-- Pilih Pola --</option>
                                        <option value="harian">Harian</option>
                                        <option value="mingguan">Mingguan</option>
                                        <option value="bulanan">Bulanan</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Sampai Tanggal</label>
                                    <input type="date" class="form-control" name="recurring_until" id="{{ $prefix }}RecurringUntil">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 form-group">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="gunakan_lampiran_tambahan" value="1" id="{{ $prefix }}GunakanLampiran">
                                <label class="form-check-label" for="{{ $prefix }}GunakanLampiran">Gunakan Lampiran Tambahan</label>
                            </div>
                        </div>
                        <div class="col-md-12 form-group" id="{{ $prefix }}LampiranGroup" style="display:none;">
                            <label>Upload Dokumen Tambahan</label>
                            <input type="file" class="form-control-file" name="lampiran_tambahan" id="{{ $prefix }}Lampiran" accept=".pdf,.jpg,.jpeg,.png">
                            <small class="form-hint">PDF atau gambar JPG/PNG. Maksimal 10MB. Lampiran ini akan menggantikan lampiran daftar peserta di PDF undangan.</small>
                            @if($prefix === 'edit')
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" name="hapus_lampiran_tambahan" value="1" id="{{ $prefix }}HapusLampiranTambahan">
                                    <label class="form-check-label" for="{{ $prefix }}HapusLampiranTambahan">Hapus lampiran tambahan saat ini</label>
                                </div>
                                <small id="{{ $prefix }}LampiranInfo" class="form-hint" style="display:none;">Lampiran saat ini tersedia dan bisa diganti atau dihapus.</small>
                            @endif
                        </div>
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
