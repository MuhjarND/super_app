<div class="card surat-keluar-card">
    <div class="card-body">
        <div class="table-responsive surat-keluar-table-wrap">
            <table id="suratKeluarTable" class="table surat-keluar-style" style="width:100%">
                <thead>
                    <tr>
                        <th style="width: 3%;"></th>
                        <th style="width: 20%;">Nomor Surat</th>
                        <th style="width: 24%;">Perihal/Isi Ringkas</th>
                        <th style="width: 20%;">Tujuan / Penerima</th>
                        <th style="width: 10%;">Tanggal Surat</th>
                        <th style="width: 10%;">Diinput Tanggal</th>
                        <th style="width: 8%;">Lampiran</th>
                        <th style="width: 8%;">Dibuat Oleh</th>
                        <th style="width: 7%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($suratKeluar as $surat)
                        <tr class="main-row" data-surat-id="{{ $surat->id }}"
                            data-update-url="{{ route('surat-keluar.update', $surat) }}"
                            data-delete-url="{{ route('surat-keluar.destroy', $surat) }}"
                            data-file-url="{{ ($surat->file_path || $surat->templateApproval || $surat->rapat || $surat->leaveRequest || ($surat->pdf_verifications_count ?? 0) > 0) ? route('surat-keluar.file', $surat) : '' }}"
                            data-creator="{{ optional($surat->creator)->name ?: '-' }}"
                            data-tahun-surat="{{ $surat->tahun_surat }}"
                            data-nomenklatur-jabatan="{{ $surat->nomenklatur_jabatan }}"
                            data-klasifikasi-kode="{{ $surat->klasifikasi_kode_id }}"
                            data-kategori-surat="{{ $surat->kategori_surat_id }}"
                            data-kode-fungsi="{{ $surat->kode_fungsi_id }}"
                            data-kode-kegiatan="{{ $surat->kode_kegiatan_id }}"
                            data-kode-transaksi="{{ $surat->kode_transaksi_id }}"
                            data-opsi-penerima="{{ $surat->opsi_penerima }}"
                            data-penerima-external="{{ $surat->penerima_external }}"
                            data-penerima-internal="{{ $surat->penerimaInternal->pluck('id')->implode(',') }}"
                            data-perihal="{{ $surat->perihal }}"
                            data-tanggal-surat="{{ optional($surat->tanggal_surat)->format('Y-m-d') }}"
                            data-has-lampiran="{{ $surat->has_lampiran ? 'ya' : 'tidak' }}"
                            data-can-manage="{{ auth()->user()->canModifySuratKeluar($surat) ? 1 : 0 }}">
                            <td>
                                <button type="button" class="btn-expand dt-expand" aria-label="Buka aksi surat">
                                    <i class="fas fa-plus" style="font-size: 10px;"></i>
                                </button>
                            </td>
                            <td>
                                <div class="nomor-surat-text">{{ $surat->nomor_surat_formatted }}</div>
                                <div class="nomor-kode">{{ $surat->deskripsi_kode ?: '-' }}</div>
                            </td>
                            <td>
                                <div class="perihal-text">{{ Str::limit($surat->perihal, 65) }}</div>
                            </td>
                            <td>
                                <span class="recipient-pill {{ $surat->opsi_penerima === 'internal' ? 'internal' : 'external' }}">
                                    {{ $surat->opsi_penerima === 'internal' ? 'Internal' : 'External' }}
                                </span>
                                @if($surat->opsi_penerima === 'internal')
                                    <button type="button" class="recipient-name recipient-count-btn js-recipient-modal"
                                        data-surat-id="{{ $surat->id }}"
                                        data-nomor-surat="{{ $surat->nomor_surat_formatted }}">
                                        {{ $surat->penerima_internal_count ?? $surat->penerimaInternal->count() }} orang
                                    </button>
                                @else
                                    <div class="recipient-name">{{ Str::limit($surat->penerima_external ?: '-', 36) }}</div>
                                @endif
                            </td>
                            <td>{{ optional($surat->tanggal_surat)->format('Y-m-d') ?: '-' }}</td>
                            <td>{{ optional($surat->created_at)->format('y-m-d') ?: '-' }}</td>
                            <td>
                                @if($surat->file_path || $surat->templateApproval || $surat->rapat || $surat->leaveRequest || ($surat->pdf_verifications_count ?? 0) > 0)
                                    <a href="javascript:void(0)" class="lampiran-badge exists"
                                        onclick="viewFile('{{ route('surat-keluar.file', $surat) }}')">Berkas</a>
                                @else
                                    <span class="lampiran-badge empty">Kosong</span>
                                @endif
                            </td>
                            <td class="creator-text">{{ optional($surat->creator)->name ?: '-' }}</td>
                            <td>{!! $surat->status_badge !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap mt-3">
            <div class="text-muted small mb-2 mb-md-0">
                Menampilkan {{ $suratKeluar->firstItem() ?: 0 }} - {{ $suratKeluar->lastItem() ?: 0 }}
                dari {{ $suratKeluar->total() }} surat
            </div>
            <div>{{ $suratKeluar->links() }}</div>
        </div>
    </div>
</div>
