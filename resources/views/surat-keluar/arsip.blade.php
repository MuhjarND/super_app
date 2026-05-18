@extends('layouts.app')

@section('title', 'Arsip Surat')

@push('styles')
    <style>
        @media (max-width: 767.98px) {
            .surat-arsip-filter .row {
                display: block;
            }

            .surat-arsip-filter .btn {
                width: 100%;
            }

            .surat-arsip-table,
            .surat-arsip-table thead,
            .surat-arsip-table tbody,
            .surat-arsip-table tr,
            .surat-arsip-table th,
            .surat-arsip-table td {
                display: block;
                width: 100%;
            }

            .surat-arsip-table thead {
                display: none;
            }

            .surat-arsip-table tbody tr {
                padding: 14px 14px 12px;
                border-bottom: 1px solid #e8eaed;
            }

            .surat-arsip-table tbody tr:last-child {
                border-bottom: 0;
            }

            .surat-arsip-table td {
                padding: 0 0 10px;
                border: 0;
                max-width: none !important;
            }

            .surat-arsip-table td:last-child {
                padding-bottom: 0;
            }

            .surat-arsip-table td::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 4px;
                font-size: 0.74rem;
                font-weight: 700;
                letter-spacing: 0.05em;
                text-transform: uppercase;
                color: #94a3b8;
            }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-archive mr-2" style="color: var(--accent);"></i>Arsip Surat</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Arsip</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <!-- Filter Section -->
    <div class="filter-section surat-arsip-filter">
        <form id="filterForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group mb-0">
                        <label>Pencarian</label>
                        <input type="text" class="form-control" name="search" placeholder="Cari nomor surat atau perihal..."
                            id="searchArsip">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-search mr-1"></i> Cari
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header d-flex align-items-center">
            <h3 class="card-title"><i class="fas fa-folder-open mr-2"></i>Surat Keluar Lengkap (Arsip)</h3>
            <span class="badge badge-success ml-2">{{ $arsip->total() }} surat</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 surat-arsip-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nomor Surat</th>
                            <th>Perihal</th>
                            <th>Tujuan</th>
                            <th>Tanggal</th>
                            <th>Berkas</th>
                            <th>Dibuat Oleh</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($arsip as $index => $surat)
                            <tr>
                                <td data-label="#">{{ $arsip->firstItem() + $index }}</td>
                                <td data-label="Nomor Surat">
                                    <strong class="text-primary">{{ $surat->nomor_surat }}</strong>
                                    <br><small class="text-muted">{{ $surat->deskripsi_kode }}</small>
                                </td>
                                <td style="max-width: 250px;" data-label="Perihal">{{ Str::limit($surat->perihal, 60) }}</td>
                                <td data-label="Tujuan">
                                    <span class="badge badge-{{ $surat->opsi_penerima == 'internal' ? 'info' : 'secondary' }}">
                                        {{ ucfirst($surat->opsi_penerima) }}
                                    </span>
                                    <div class="mt-1" style="font-size: 0.82rem;">
                                        @if($surat->opsi_penerima == 'internal')
                                            {{ $surat->penerimaInternal->pluck('name')->implode(', ') }}
                                        @else
                                            {{ $surat->penerima_external }}
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Tanggal">{{ $surat->tanggal_surat->format('d/m/Y') }}</td>
                                <td data-label="Berkas">
                                    @if($surat->file_path || $surat->templateApproval || $surat->rapat || $surat->leaveRequest || ($surat->relationLoaded('pdfVerifications') && $surat->pdfVerifications->isNotEmpty()))
                                        <a href="{{ route('surat-keluar.file', $surat) }}" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-download mr-1"></i>Download
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td data-label="Dibuat Oleh"><small>{{ $surat->creator->name }}</small></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-archive fa-3x mb-3 d-block" style="opacity: 0.3;"></i>
                                    Belum ada surat keluar di arsip
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $arsip->links() }}
    </div>
@endsection
