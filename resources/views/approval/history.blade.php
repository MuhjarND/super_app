@extends('layouts.app')

@section('title', 'Riwayat Approval')

@push('styles')
    <style>
        .approval-page-hero {
            border: 1px solid #dbeafe;
            border-radius: 22px;
            padding: 20px 22px;
            margin-bottom: 18px;
            background:
                radial-gradient(circle at top right, rgba(37, 99, 235, 0.12), transparent 34%),
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
        }

        .approval-module-title {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
        }

        .approval-module-count {
            font-size: 1.45rem;
            line-height: 1;
            font-weight: 900;
            color: #0f172a;
        }

        .approval-module-subtitle {
            font-size: 0.82rem;
            color: #64748b;
            line-height: 1.5;
            min-height: 38px;
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
            .approval-list-item {
                flex-direction: column;
            }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="mb-1">Riwayat Approval</h1>
            <div class="text-muted" style="font-size: 0.82rem;">Pusat riwayat approval untuk seluruh jenis dokumen.</div>
        </div>
    </div>
@endsection

@section('content')
    @php($totalHistory = collect($cards)->sum('history_count'))
    <div class="approval-page-hero">
        <div class="approval-page-hero-title">Riwayat Approval Dokumen</div>
        <div class="approval-page-hero-subtitle">
            Seluruh riwayat approval dikumpulkan di halaman ini. Pilih kategori dokumen untuk melihat jejak tindakan, catatan, dan hasil approval yang sudah tercatat.
        </div>
        <div class="mt-3">
            <span class="badge badge-primary px-3 py-2" style="font-size:.82rem;">{{ $totalHistory }} riwayat approval tercatat</span>
        </div>
    </div>

    <div class="approval-card-grid">
        @foreach($cards as $card)
            <a href="{{ route('approval.history', ['category' => $card['key']]) }}" class="approval-module-card {{ $category === $card['key'] ? 'active' : '' }}">
                <div class="approval-module-icon">
                    <i class="{{ $card['icon'] }}"></i>
                </div>
                <div class="approval-module-title">{{ $card['label'] }}</div>
                <div class="approval-module-subtitle">{{ $card['description'] }}</div>
                <div class="mt-3">
                    <div class="approval-module-count">{{ $card['history_count'] }}</div>
                    <div class="text-muted" style="font-size:0.78rem;">Riwayat approval</div>
                </div>
            </a>
        @endforeach
    </div>

    <div class="card approval-list-card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <strong>{{ $selectedCard['label'] ?? 'Pilih Kategori Dokumen' }}</strong>
                <div class="text-muted" style="font-size: 0.8rem;">
                    {{ $selectedCard ? 'Riwayat approval untuk kategori dokumen terpilih.' : 'Pilih salah satu card di atas untuk melihat riwayat approval.' }}
                </div>
            </div>
            @if($selectedCard)
                <span class="badge badge-primary">{{ $historyItems->count() }}</span>
            @endif
        </div>
        <div class="card-body p-0">
            @if(!$selectedCard)
                <div class="approval-empty">Belum ada kategori yang dipilih.</div>
            @elseif($historyItems->isEmpty())
                <div class="approval-empty">Belum ada riwayat approval pada kategori ini.</div>
            @else
                @foreach($historyItems as $entry)
                    <div class="approval-list-item">
                        <div>
                            <div class="approval-list-title">{{ $entry['title'] }}</div>
                            <div class="approval-list-meta">{{ $entry['number'] }}</div>
                            <div class="approval-list-meta">{{ $entry['action'] }} oleh {{ $entry['actor'] }} | {{ $entry['acted_at'] }}</div>
                            @if($entry['note'])
                                <div class="approval-list-meta">Catatan: {{ $entry['note'] }}</div>
                            @endif
                        </div>
                        <div class="d-flex align-items-center">
                            @if($entry['detail_url'])
                                <a href="{{ $entry['detail_url'] }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-eye mr-1"></i> Lihat Detail
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
@endsection
