@extends('layouts.app')

@section('title', 'Approval')

@push('styles')
    <style>
        .approval-page-hero {
            border: 1px solid #dbeafe;
            border-radius: 22px;
            padding: 20px 22px;
            margin-bottom: 18px;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, 0.14), transparent 34%),
                linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 16px 34px rgba(15, 23, 42, 0.06);
        }

        .approval-page-hero-title {
            font-size: 1.15rem;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .approval-page-hero-subtitle {
            font-size: 0.84rem;
            color: #475569;
            line-height: 1.6;
            max-width: 760px;
        }

        .approval-summary-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .approval-summary-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 8px 12px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 0.8rem;
            font-weight: 800;
        }

        .approval-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 18px;
        }

        .approval-module-card {
            display: block;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            padding: 18px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
            color: inherit;
            position: relative;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .approval-module-card.active {
            border-color: #2563eb;
            box-shadow: 0 14px 30px rgba(37, 99, 235, 0.12);
        }

        .approval-module-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.09);
        }

        .approval-module-card.disabled-card {
            opacity: 0.78;
        }

        .approval-module-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1d4ed8;
            font-size: 1.1rem;
            margin-bottom: 14px;
            box-shadow: inset 0 1px 0 rgba(255,255,255,.6);
        }

        .approval-module-title {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .approval-module-subtitle {
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.5;
            min-height: 38px;
        }

        .approval-module-meta {
            margin-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 12px;
            padding-top: 14px;
            border-top: 1px dashed #dbe3ef;
        }

        .approval-module-count {
            font-size: 1.5rem;
            line-height: 1;
            font-weight: 900;
            color: #0f172a;
        }

        .approval-urgent-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            min-width: 32px;
            height: 32px;
            border-radius: 999px;
            padding: 0 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #dc2626;
            color: #fff;
            font-weight: 800;
            font-size: 0.8rem;
        }

        .approval-list-card {
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.05);
            overflow: hidden;
        }

        .approval-list-card .card-header {
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%) !important;
            padding: 18px 20px;
        }

        .approval-list-item {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 20px;
            border-bottom: 1px solid #eef2f7;
        }

        .approval-doc-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 0.74rem;
            font-weight: 800;
            margin-top: 10px;
        }

        .approval-list-item:last-child {
            border-bottom: none;
        }

        .approval-list-title {
            font-size: 0.98rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .approval-list-meta {
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.55;
        }

        .approval-empty {
            padding: 30px 20px;
            text-align: center;
            color: #64748b;
        }

        @media (max-width: 767.98px) {
            .approval-page-hero {
                padding: 16px 16px 15px;
                border-radius: 18px;
            }

            .approval-page-hero-title {
                font-size: 1.02rem;
            }

            .approval-page-hero-subtitle {
                font-size: 0.8rem;
            }

            .approval-card-grid {
                grid-template-columns: 1fr;
            }

            .approval-module-card {
                padding: 16px;
            }

            .approval-list-item {
                flex-direction: column;
                padding: 16px;
            }

            .approval-list-card .card-header {
                padding: 15px 16px;
                align-items: flex-start !important;
                gap: 10px;
            }

            .approval-list-item .app-action-group {
                width: 100%;
                justify-content: flex-start;
            }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="mb-1">Approval</h1>
            <div class="text-muted" style="font-size: 0.82rem;">Pusat dokumen yang perlu segera ditindaklanjuti approval.</div>
        </div>
    </div>
@endsection

@section('content')
    @php($totalPending = collect($cards)->sum('pending_count'))
    <div class="approval-page-hero">
        <div class="approval-page-hero-title">Pusat Approval Dokumen</div>
        <div class="approval-page-hero-subtitle">
            Seluruh dokumen yang membutuhkan approval akan masuk ke halaman ini. Pilih jenis dokumennya, lalu proses dokumen yang sedang menunggu tindakan Anda.
        </div>
        <div class="approval-summary-badges">
            <span class="approval-summary-chip"><i class="fas fa-bell"></i> {{ $totalPending }} dokumen pending</span>
            <span class="approval-summary-chip"><i class="fas fa-layer-group"></i> {{ collect($cards)->where('pending_count', '>', 0)->count() }} kategori aktif</span>
        </div>
    </div>

    <div class="approval-card-grid">
        @foreach($cards as $card)
            <a href="{{ route('approval.index', ['category' => $card['key']]) }}" class="approval-module-card {{ $category === $card['key'] ? 'active' : '' }} {{ !$card['is_active'] ? 'disabled-card' : '' }}">
                @if($card['pending_count'] > 0)
                    <span class="approval-urgent-badge">{{ $card['pending_count'] }}</span>
                @endif
                <div class="approval-module-icon">
                    <i class="{{ $card['icon'] }}"></i>
                </div>
                <div class="approval-module-title">{{ $card['label'] }}</div>
                <div class="approval-module-subtitle">{{ $card['description'] }}</div>
                <div class="approval-module-meta">
                    <div>
                        <div class="approval-module-count">{{ $card['pending_count'] }}</div>
                        <div class="text-muted" style="font-size:0.78rem;">Pending approval</div>
                    </div>
                    <div class="text-right text-muted" style="font-size:0.78rem;">
                        <div class="font-weight-bold">{{ $card['history_count'] }}</div>
                        <div>Riwayat</div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>

    <div class="card approval-list-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <strong>{{ $selectedCard['label'] ?? 'Pilih Kategori Dokumen' }}</strong>
                <div class="text-muted" style="font-size: 0.8rem;">
                    {{ $selectedCard ? 'Daftar dokumen yang harus dilakukan approval.' : 'Pilih salah satu card di atas untuk melihat daftar dokumen.' }}
                </div>
            </div>
            @if($selectedCard)
                <span class="badge badge-danger">{{ $documents->count() }}</span>
            @endif
        </div>
        <div class="card-body p-0">
            @if(!$selectedCard)
                <div class="approval-empty">Belum ada kategori yang dipilih.</div>
            @elseif($documents->isEmpty())
                <div class="approval-empty">Tidak ada dokumen pending pada kategori ini.</div>
            @else
                @foreach($documents as $document)
                    <div class="approval-list-item">
                        <div>
                            <div class="approval-list-title">{{ $document['title'] }}</div>
                            <div class="approval-list-meta">{{ $document['number'] }} | {{ $document['date'] ?: '-' }}</div>
                            <div class="approval-list-meta">{{ $document['subtitle'] }} | {{ $document['meta'] }}</div>
                            <div class="approval-list-meta">{{ $document['count_label'] }} | Status: {{ $document['status_label'] }}</div>
                            <span class="approval-doc-chip"><i class="fas fa-check-circle"></i> Perlu tindakan sekarang</span>
                        </div>
                        <div class="app-action-group">
                            <a href="{{ $document['detail_url'] }}" class="app-icon-btn process" data-mobile-label="Proses">
                                <i class="fas fa-file-signature"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endsection
