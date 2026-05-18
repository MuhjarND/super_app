<div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th>Nomor Surat</th>
                <th>Perihal / Isi Ringkas</th>
                <th>Tujuan / Penerima</th>
                <th>Tanggal</th>
                <th>Lampiran</th>
                <th>Dibuat Oleh</th>
                <th>Status</th>
                <th style="width: 80px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($suratKeluar as $index => $surat)
                <tr>
                    <td data-label="#">{{ $suratKeluar->firstItem() + $index }}</td>
                    <td data-label="Nomor Surat">
                        <strong class="text-primary d-block">{{ $surat->nomor_surat }}</strong>
                        <small class="text-muted">{{ $surat->deskripsi_kode }}</small>
                    </td>
                    <td style="max-width: 200px;" data-label="Perihal / Isi Ringkas">
                        <span title="{{ $surat->perihal }}">{{ Str::limit($surat->perihal, 50) }}</span>
                    </td>
                    <td data-label="Tujuan / Penerima">
                        <span class="badge badge-{{ $surat->opsi_penerima == 'internal' ? 'info' : 'secondary' }}"
                            style="font-size: 0.7rem;">
                            {{ ucfirst($surat->opsi_penerima) }}
                        </span>
                        <div class="mt-1" style="font-size: 0.82rem;">
                            @if($surat->opsi_penerima == 'internal')
                                {{ $surat->penerimaInternal->pluck('name')->implode(', ') ?: '-' }}
                            @else
                                {{ $surat->penerima_external }}
                            @endif
                        </div>
                    </td>
                    <td data-label="Tanggal">{{ $surat->tanggal_surat->format('d/m/Y') }}</td>
                    <td data-label="Lampiran">
                        @if($surat->file_path || $surat->templateApproval || $surat->rapat || $surat->leaveRequest || ($surat->relationLoaded('pdfVerifications') && $surat->pdfVerifications->isNotEmpty()))
                            <a href="javascript:void(0)" onclick="viewFile('{{ route('surat-keluar.file', $surat) }}')"
                                class="app-icon-btn file" data-mobile-label="Berkas">
                                <i class="fas fa-file"></i>
                            </a>
                        @else
                            @if($surat->status == 'draft')
                                <button onclick="openUpload({{ $surat->id }})" class="app-icon-btn upload" data-mobile-label="Upload">
                                    <i class="fas fa-upload"></i>
                                </button>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        @endif
                    </td>
                    <td data-label="Dibuat Oleh">
                        <small>{{ $surat->creator->name }}</small>
                    </td>
                    <td data-label="Status">{!! $surat->status_badge !!}</td>
                    <td class="app-action-cell" data-label="Aksi">
                        <div class="app-action-group">
                        @if($surat->status == 'draft' && !$surat->file_path)
                            <button onclick="openUpload({{ $surat->id }})" class="app-icon-btn upload"
                                title="Upload Lampiran" data-mobile-label="Upload">
                                <i class="fas fa-upload"></i>
                            </button>
                        @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <i class="fas fa-paper-plane fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                        Tidak ada surat keluar ditemukan
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
