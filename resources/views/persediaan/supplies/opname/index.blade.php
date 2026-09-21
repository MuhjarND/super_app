@extends('layouts.app')

@section('title', 'BA Opname Fisik Persediaan')

@push('styles')
@include('persediaan.supplies._styles')
<style>
    .opname-table input { min-width: 82px; }
    .opname-table .item-name { min-width: 220px; }
</style>
@endpush

@section('content')
@include('admin._alerts')
<div class="inventory-module-hero d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1 class="inventory-module-title mb-1">BA Opname Fisik Persediaan</h1>
        <div class="text-muted small">Dokumen mengikuti format Berita Acara dan lampiran yang ditetapkan.</div>
    </div>
    <a href="{{ route('persediaan.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
</div>

<div class="inventory-module-shell">
    <div class="inventory-module-board">
        <div class="inventory-module-board-header">
            <div class="inventory-module-board-title"><i class="fas fa-clipboard-check text-muted mr-1"></i> Data Opname</div>
        </div>
        <div class="inventory-module-board-body">
            <form method="POST" action="{{ route('persediaan.opname.download') }}" novalidate>
                @csrf
                <div class="form-row align-items-end mb-3">
                    <div class="form-group col-md-3 mb-2">
                        <label class="small text-muted font-weight-bold">Periode laporan</label>
                        <input type="month" name="period" value="{{ $period->format('Y-m') }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="form-group col-md-3 mb-2">
                        <label class="small text-muted font-weight-bold">Tanggal opname</label>
                        <input type="date" name="opname_date" value="{{ $opnameDate->format('Y-m-d') }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-6 mb-2 text-md-right">
                        @if($existing)
                            <span class="badge badge-light mr-2">Nomor: {{ $existing->nomor_surat }}</span>
                        @endif
                        <button class="btn btn-sm btn-primary"><i class="fas fa-file-word mr-1"></i> Simpan dan Cetak DOCX</button>
                    </div>
                </div>

                @php
                    $selectedCommittee = old('committee_user_ids', $committeeDefaults);
                    $selectedSignatory = old('signatory_user_id', $signatoryDefault);
                @endphp
                <div class="border rounded p-3 mb-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-2">
                        <div>
                            <div class="font-weight-bold">Panitia opname dan penandatangan</div>
                            <div class="small text-muted">Nama dan NIP diambil dari data pegawai aktif.</div>
                        </div>
                        @error('committee_user_ids')<div class="small text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-row">
                        @foreach(['Ketua', 'Sekretaris', 'Anggota 1', 'Anggota 2'] as $positionIndex => $positionLabel)
                            <div class="form-group col-md-3 mb-2">
                                <label for="committee-{{ $positionIndex }}" class="small text-muted font-weight-bold">{{ $positionLabel }}</label>
                                <select id="committee-{{ $positionIndex }}" name="committee_user_ids[]" class="form-control form-control-sm" required>
                                    <option value="">Pilih pegawai</option>
                                    @foreach($committeeUsers as $candidate)
                                        @php
                                            $candidateTitle = $candidate->jabatan_keterangan ?: optional($candidate->jabatan)->nama;
                                            $candidateLabel = $candidate->name . ($candidate->nip ? ' — NIP ' . $candidate->nip : '');
                                        @endphp
                                        <option value="{{ $candidate->id }}" {{ (string) ($selectedCommittee[$positionIndex] ?? '') === (string) $candidate->id ? 'selected' : '' }}>
                                            {{ $candidateLabel }}{{ $candidateTitle ? ' (' . $candidateTitle . ')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach
                        <div class="form-group col-md-6 mb-0">
                            <label for="signatory-user" class="small text-muted font-weight-bold">Pejabat penandatangan</label>
                            <select id="signatory-user" name="signatory_user_id" class="form-control form-control-sm" required>
                                <option value="">Pilih pejabat</option>
                                @foreach($committeeUsers as $candidate)
                                    @php
                                        $candidateTitle = $candidate->jabatan_keterangan ?: optional($candidate->jabatan)->nama;
                                        $candidateLabel = $candidate->name . ($candidate->nip ? ' — NIP ' . $candidate->nip : '');
                                    @endphp
                                    <option value="{{ $candidate->id }}" {{ (string) $selectedSignatory === (string) $candidate->id ? 'selected' : '' }}>
                                        {{ $candidateLabel }}{{ $candidateTitle ? ' (' . $candidateTitle . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('signatory_user_id')<div class="small text-danger mt-1">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="alert alert-info py-2 small">Saldo SAKTI diambil dari stok aktif saat dokumen dibuat. Isi hasil cek fisik dan kondisi bila berbeda.</div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered opname-table mb-0">
                        <thead class="thead-light">
                            <tr><th>No</th><th>Nama Barang</th><th>Kode</th><th>Satuan</th><th>Saldo SAKTI</th><th>Hasil cek fisik</th><th>Kondisi</th><th>Harga Satuan</th></tr>
                        </thead>
                        <tbody>
                        @php $grouped = $items->groupBy(function($item){ return $item->account_code ?: '117111'; }); @endphp
                        @forelse($grouped as $account => $group)
                            <tr class="table-secondary"><th colspan="8">{{ $account }} — {{ $account === '117113' ? 'Barang untuk Pemeliharaan' : ($account === '117111' ? 'Barang Konsumsi' : 'Barang Persediaan') }}</th></tr>
                            @foreach($group as $item)
                                @php $saved = $physical->get($item->id); @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="item-name">{{ $item->name }}</td>
                                    <td>{{ $item->code ?: '-' }}</td>
                                    <td>{{ $item->unit }}</td>
                                    <td>{{ $item->stock }}</td>
                                    <td><input type="number" min="0" name="physical[{{ $item->id }}]" value="{{ old('physical.' . $item->id, $saved['fisik'] ?? $item->stock) }}" class="form-control form-control-sm"></td>
                                    <td><input type="text" name="condition[{{ $item->id }}]" value="{{ old('condition.' . $item->id, $saved['kondisi'] ?? 'Baik') }}" class="form-control form-control-sm"></td>
                                    <td>Rp {{ number_format((float) ($item->unit_price ?: 0), 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada barang aktif.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
