    <div class="card surat-masuk-card">
        <div class="card-body" style="padding-top: 20px;">
            <div class="table-responsive surat-masuk-table-wrap">
            <table id="suratMasukTable" class="table" style="width:100%">
                <thead>
                    <tr>
                        <th>No. Surat</th>
                        <th>Pengirim</th>
                        <th>Perihal / Isi Ringkas</th>
                        <th>Tanggal Surat</th>
                        <th>Diinput Pada</th>
                        <th>Dibuat Oleh</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($suratMasuk as $surat)
                        @php
                            $isSuratSelesai = $surat->status === 'selesai';
                            $latestDisposisi = $surat->disposisis->sortByDesc('created_at')->first();
                            $pendingForMe = $isSuratSelesai
                                ? null
                                : auth()->user()->activePendingDisposisiForSurat($surat);
                            $needsDisposition = !$isSuratSelesai
                                && (
                                    ($surat->status === 'baru' && auth()->user()->canForwardSuratMasuk($surat))
                                    || (bool) $pendingForMe
                                );
                            $canNaikanSurat = auth()->user()->canNaikanSuratMasuk();
                            $assignmentContext = $surat->assignmentContextFor(auth()->user());
                            $showAssignmentContext = auth()->user()->hasActiveJabatanDelegation() && $assignmentContext;
                        @endphp
                        <tr class="main-row {{ $needsDisposition ? 'surat-needs-disposition' : '' }}" data-surat-id="{{ $surat->id }}" data-creator="{{ optional($surat->creator)->name ?: '-' }}"
                            data-show-url="{{ route('surat-masuk.show', $surat) }}"
                            data-download-url="{{ route('surat-masuk.download', $surat) }}"
                            data-preview-url="{{ route('surat-masuk.preview', $surat) }}"
                            data-update-url="{{ route('surat-masuk.update', $surat) }}"
                            data-delete-url="{{ route('surat-masuk.destroy', $surat) }}" data-nomor="{{ $surat->nomor_surat }}"
                            data-opsi-pengirim="{{ $surat->opsi_pengirim }}"
                            data-kategori-surat="{{ $surat->kategori_surat_id }}"
                            data-kategori-surat-label="{{ optional($surat->kategoriSurat)->kode ? optional($surat->kategoriSurat)->kode . ' - ' . optional($surat->kategoriSurat)->nama : '' }}"
                            data-klasifikasi="{{ $surat->klasifikasi_kode_id }}" data-pengirim="{{ $surat->pengirim }}"
                            data-perihal="{{ $surat->perihal }}" data-tanggal="{{ $surat->tanggal_surat->format('Y-m-d') }}"
                            data-sifat="{{ $surat->sifat }}" data-status="{{ $surat->status }}"
                            data-file-path="{{ $surat->file_path }}"
                            data-agenda-title="{{ optional($surat->agendaPimpinan)->judul_agenda }}"
                            data-agenda-date="{{ optional(optional($surat->agendaPimpinan)->tanggal_kegiatan)->format('Y-m-d') }}"
                            data-agenda-time="{{ optional($surat->agendaPimpinan)->waktu_formatted }}"
                            data-agenda-place="{{ optional($surat->agendaPimpinan)->tempat }}"
                            data-agenda-clothing="{{ optional($surat->agendaPimpinan)->seragam_pakaian }}"
                            data-can-forward="{{ auth()->user()->canForwardSuratMasuk($surat) ? 1 : 0 }}"
                            data-can-edit="{{ auth()->user()->canEditSuratMasuk($surat) ? 1 : 0 }}"
                            data-can-delete="{{ auth()->user()->canDeleteSuratMasuk($surat) ? 1 : 0 }}"
                            data-can-follow-up="{{ auth()->user()->canOpenTindakLanjutSuratMasuk($surat) ? 1 : 0 }}"
                            data-assignment-mode="{{ data_get($assignmentContext, 'mode') }}"
                            data-assignment-badge="{{ data_get($assignmentContext, 'badge') }}"
                            data-assignment-description="{{ data_get($assignmentContext, 'description') }}"
                            data-assignment-action-label="{{ data_get($assignmentContext, 'action_label') }}"
                            data-pending-disposisi-id="{{ $pendingForMe ? $pendingForMe->id : '' }}">
                            <td>
                                <button type="button" class="surat-mobile-row-toggle" aria-label="Lihat detail surat" aria-expanded="false">
                                    <i class="fas fa-plus"></i>
                                </button>
                                @if($surat->klasifikasiKode)
                                    <span class="klasifikasi-prefix">{{ $surat->klasifikasiKode->kode }} -
                                        {{ $surat->klasifikasiKode->nama }}</span><br>
                                @endif
                                <span class="nomor-surat-text">{{ $surat->nomor_surat }}</span><br>
                                @php
                                    $sifatClass = ['biasa' => 'badge-sifat-biasa', 'rahasia' => 'badge-sifat-rahasia', 'sangat_rahasia' => 'badge-sifat-sangat-rahasia'];
                                    $sifatLabel = ['biasa' => 'Biasa', 'rahasia' => 'Rahasia', 'sangat_rahasia' => 'Sangat Rahasia'];
                                @endphp
                                <span
                                    class="{{ $sifatClass[$surat->sifat] ?? 'badge-sifat-biasa' }}">{{ $sifatLabel[$surat->sifat] ?? $surat->sifat }}</span>
                                @if($showAssignmentContext)
                                    <div>
                                        <span class="surat-assignment-badge {{ data_get($assignmentContext, 'mode') === 'delegated' ? 'is-delegated' : '' }}">
                                            <i class="fas {{ data_get($assignmentContext, 'mode') === 'delegated' ? 'fa-user-shield' : 'fa-user-check' }}"></i>
                                            {{ data_get($assignmentContext, 'badge') }}
                                        </span>
                                        @if(data_get($assignmentContext, 'mode') === 'delegated')
                                            <div class="surat-assignment-note">{{ data_get($assignmentContext, 'description') }}</div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="surat-mobile-extra">
                                <span class="{{ $surat->opsi_pengirim == 'mahkamah_agung' ? 'badge-ma' : 'badge-non-ma' }}">
                                    {{ $surat->opsi_pengirim == 'mahkamah_agung' ? 'Mahkamah Agung' : 'Non Mahkamah Agung' }}
                                </span>
                                <div class="pengirim-nama">{{ $surat->pengirim }}</div>
                            </td>
                            <td style="max-width: 220px;">
                                {{ Str::limit($surat->perihal, 80) }}
                            </td>
                            <td class="surat-mobile-extra">{{ $surat->tanggal_surat->format('Y-m-d') }}</td>
                            <td class="surat-mobile-extra">{{ $surat->created_at->format('Y-m-d') }}</td>
                            <td class="surat-mobile-extra">{{ optional($surat->creator)->name ?: '-' }}</td>
                            <td>
                                @if($needsDisposition)
                                    <span class="badge-needs-disposition mb-1">
                                        <i class="fas fa-exclamation-circle"></i> Perlu Disposisi/Tindaklanjut
                                    </span><br>
                                @endif
                                @if($surat->status == 'baru')
                                    <span class="status-text">Baru</span><br>
                                    <span class="badge-new-status">New</span>
                                @elseif($surat->status == 'didisposisi')
                                    @if($latestDisposisi)
                                        <span
                                            class="status-text">{{ $latestDisposisi->tipe == 'naikan' ? 'Dinaikan' : 'Diteruskan' }}</span><br>
                                    @else
                                        <span class="status-text">Didisposisi</span><br>
                                    @endif
                                    <span class="badge-on-process">On-Process</span>
                                @else
                                    <span class="status-text">Selesai</span><br>
                                    <span class="badge-done">Done</span>
                                @endif
                            </td>
                            <td class="surat-actions-cell">
                                <div class="dropdown surat-action-dropdown">
                                    <button class="btn dropdown-toggle" type="button" id="suratMasukAction{{ $surat->id }}" data-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-ellipsis-h"></i> Aksi
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right surat-action-menu" aria-labelledby="suratMasukAction{{ $surat->id }}">
                                        @if(data_get($assignmentContext, 'mode') === 'delegated')
                                            <div class="px-3 py-2 small text-muted" style="max-width: 260px; white-space: normal;">
                                                <i class="fas fa-user-shield mr-1 text-warning"></i>{{ data_get($assignmentContext, 'action_label') }}
                                            </div>
                                            <div class="dropdown-divider"></div>
                                        @endif
                                        @if(auth()->user()->canForwardSuratMasuk($surat))
                                            @if(auth()->user()->isKasubagTurt())
                                                <button type="button" class="dropdown-item" onclick="openDisposisi({{ $surat->id }}, 'teruskan')">
                                                    <i class="fas fa-share"></i> Teruskan
                                                </button>
                                            @else
                                                <button type="button" class="dropdown-item" onclick="openDisposisi({{ $surat->id }}, 'disposisi')">
                                                    <i class="fas fa-share"></i> Disposisi
                                                </button>
                                            @endif

                                            @if($canNaikanSurat)
                                                <button type="button" class="dropdown-item" onclick="openDisposisi({{ $surat->id }}, 'naikan')">
                                                    <i class="fas fa-level-up-alt"></i> Naikan
                                                </button>
                                            @endif
                                            <div class="dropdown-divider"></div>
                                        @endif

                                        @if(auth()->user()->canOpenTindakLanjutSuratMasuk($surat))
                                            <button type="button" class="dropdown-item" onclick="openTindakLanjut({{ $surat->id }})">
                                                <i class="fas fa-flag"></i> Tindaklanjuti
                                            </button>
                                        @endif

                                        @if($surat->status === 'selesai')
                                            <button type="button" class="dropdown-item" onclick="openDetail({{ $surat->id }})">
                                                <i class="fas fa-clipboard-check"></i> Preview Tindak Lanjut
                                            </button>
                                        @endif

                                        <button type="button" class="dropdown-item" onclick="openDetail({{ $surat->id }})">
                                            <i class="fas fa-eye"></i> Detail
                                        </button>

                                        @if(auth()->user()->canEditSuratMasuk($surat))
                                            <button type="button" class="dropdown-item" onclick="openEdit({{ $surat->id }})">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        @endif

                                        @if(auth()->user()->canDeleteSuratMasuk($surat))
                                            <div class="dropdown-divider"></div>
                                            <button type="button" class="dropdown-item text-danger" data-delete-url="{{ route('surat-masuk.destroy', $surat) }}" onclick="deleteSurat({{ $surat->id }}, this.dataset.deleteUrl)">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            @if(method_exists($suratMasuk, 'links'))
                <div class="d-flex justify-content-between align-items-center flex-wrap mt-3">
                    <div class="text-muted small mb-2 mb-md-0">
                        Menampilkan {{ $suratMasuk->firstItem() ?: 0 }} - {{ $suratMasuk->lastItem() ?: 0 }} dari {{ $suratMasuk->total() }} surat
                    </div>
                    <div>
                        {!! $suratMasuk->links() !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
