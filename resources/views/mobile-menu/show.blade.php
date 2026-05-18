@extends('layouts.app')

@section('title', $menu['title'])

@push('styles')
    <style>
        .mobile-menu-page {
            max-width: 520px;
            margin: 0 auto;
            padding: 4px 0 18px;
        }

        .mobile-menu-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 800;
            text-decoration: none !important;
        }

        .mobile-menu-hero {
            position: relative;
            overflow: hidden;
            border-radius: 26px;
            padding: 22px;
            color: #ffffff;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            box-shadow: 0 20px 46px rgba(79, 70, 229, 0.26);
        }

        .mobile-menu-hero::after {
            content: '';
            position: absolute;
            right: -46px;
            bottom: -52px;
            width: 150px;
            height: 150px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
        }

        .mobile-menu-hero-icon {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.22);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 16px;
            backdrop-filter: blur(10px);
        }

        .mobile-menu-title {
            position: relative;
            z-index: 1;
            margin: 0 0 6px;
            font-size: 1.12rem;
            line-height: 1.2;
            font-weight: 900;
            letter-spacing: -0.02em;
        }

        .mobile-menu-subtitle {
            position: relative;
            z-index: 1;
            max-width: 320px;
            margin: 0;
            color: rgba(255, 255, 255, 0.78);
            font-size: 0.78rem;
            line-height: 1.45;
        }

        .mobile-menu-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 18px;
        }

        .mobile-menu-link {
            min-height: 104px;
            padding: 14px 9px 12px;
            border-radius: 22px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            color: #0f172a;
            text-decoration: none !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07);
            transition: transform 0.16s ease, box-shadow 0.16s ease;
        }

        .mobile-menu-link:active {
            transform: scale(0.97);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .mobile-menu-link-icon {
            width: 48px;
            height: 48px;
            border-radius: 17px;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.08rem;
            background: linear-gradient(135deg, #4f46e5, #8b5cf6);
            box-shadow: 0 10px 24px rgba(79, 70, 229, 0.18);
        }

        .mobile-menu-link-label {
            min-height: 28px;
            color: #172033;
            font-size: 0.7rem;
            font-weight: 850;
            line-height: 1.18;
            text-align: center;
        }

        .mobile-menu-link-icon.blue { background: linear-gradient(135deg, #2563eb, #06b6d4); }
        .mobile-menu-link-icon.indigo { background: linear-gradient(135deg, #4f46e5, #8b5cf6); }
        .mobile-menu-link-icon.green { background: linear-gradient(135deg, #059669, #10b981); }
        .mobile-menu-link-icon.red { background: linear-gradient(135deg, #dc2626, #ef4444); }
        .mobile-menu-link-icon.orange { background: linear-gradient(135deg, #d97706, #f59e0b); }
        .mobile-menu-link-icon.teal { background: linear-gradient(135deg, #0f766e, #14b8a6); }
        .mobile-menu-link-icon.slate { background: linear-gradient(135deg, #475569, #0f172a); }
        .mobile-menu-link-icon.purple { background: linear-gradient(135deg, #7c3aed, #4f46e5); }

        @media (max-width: 390px) {
            .mobile-menu-grid {
                gap: 10px;
            }

            .mobile-menu-link {
                min-height: 98px;
                padding-left: 7px;
                padding-right: 7px;
            }

            .mobile-menu-link-icon {
                width: 44px;
                height: 44px;
                border-radius: 15px;
                font-size: 1rem;
            }

            .mobile-menu-link-label {
                font-size: 0.66rem;
            }
        }
    </style>
@endpush

@section('content-header')
    <div class="content-header" style="padding-bottom: 0; min-height: 0;">
        <div class="container-fluid"></div>
    </div>
@endsection

@section('content')
    <div class="mobile-menu-page">
        <a href="{{ route('dashboard') }}" class="mobile-menu-back">
            <i class="fas fa-arrow-left"></i>
            <span>Kembali ke Beranda</span>
        </a>

        <div class="mobile-menu-hero">
            <div class="mobile-menu-hero-icon">
                <i class="{{ $menu['icon'] }}"></i>
            </div>
            <h1 class="mobile-menu-title">{{ $menu['title'] }}</h1>
            <p class="mobile-menu-subtitle">{{ $menu['subtitle'] }}</p>
        </div>

        <div class="mobile-menu-grid">
            @foreach($menu['items'] as $item)
                <a href="{{ $item['url'] }}" class="mobile-menu-link">
                    <span class="mobile-menu-link-icon {{ $item['tone'] }}">
                        <i class="{{ $item['icon'] }}"></i>
                    </span>
                    <span class="mobile-menu-link-label">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endsection
