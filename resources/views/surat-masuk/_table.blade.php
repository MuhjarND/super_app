<div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th style="width: 50px;">#</th>
                <th>No. Surat</th>
                <th>Pengirim</th>
                <th>Perihal / Isi Ringkas</th>
                <th>Tanggal Surat</th>
                <th>Di-input Pada</th>
                <th>Status</th>
                <th style="width: 80px;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($suratMasuk as $index => $surat)
                <tr>
                    <td>{{ $suratMasuk->firstItem() + $index }}</td>
                    <td>
                        <div>
                            @if($surat->klasifikasiKode)
                                <small class="badge badge-light">{{ $surat->klasifikasiKode->kode }}</small>
                            @endif
                            <strong class="text-primary">{{ $surat->nomor_surat }}</strong>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-{{ $surat->opsi_pengirim == 'mahkamah_agung' ? 'primary' : 'secondary' }}"
                            style="font-size: 0.7rem;">
                            {{ $surat->opsi_pengirim == 'mahkamah_agung' ? 'MA' : 'Non-MA' }}
                        </span>
                        <div class="mt-1">{{ $surat->pengirim }}</div>
                    </td>
                    <td style="max-width: 250px;">
                        <span title="{{ $surat->perihal }}">{{ Str::limit($surat->perihal, 60) }}</span>
                    </td>
                    <td>{{ $surat->tanggal_surat->format('d/m/Y') }}</td>
                    <td>
                        <small>{{ $surat->created_at->format('d/m/Y H:i') }}</small>
                    </td>
                    <td>{!! $surat->status_badge !!}</td>
                    <td class="app-action-cell">
                        <div class="app-action-group">
                        <a href="{{ route('surat-masuk.show', $surat) }}" class="app-icon-btn detail"
                            title="Detail">
                            <i class="fas fa-eye"></i>
                        </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                        Tidak ada surat masuk ditemukan
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
