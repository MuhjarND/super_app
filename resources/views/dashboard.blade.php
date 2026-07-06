@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <style>
        .dashboard-shell {
            display: grid;
            gap: 18px;
        }

        .dashboard-hero {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 40%, #8b5cf6 100%);
            color: #ffffff;
            border-radius: 20px;
            padding: 30px 32px;
            border: none;
            box-shadow: 0 8px 32px rgba(79, 70, 229, 0.20);
            position: relative;
            overflow: hidden;
        }

        .dashboard-hero::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .dashboard-hero::after {
            content: '';
            position: absolute;
            bottom: -40%;
            left: 10%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 70%);
            border-radius: 50%;
        }

        .dashboard-hero-title {
            font-size: 1.55rem;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .dashboard-hero-meta {
            color: rgba(255,255,255,0.75);
            font-size: 0.92rem;
        }

        .hero-chip-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .hero-chip {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 999px;
            padding: 9px 16px;
            min-height: 44px;
            backdrop-filter: blur(8px);
            transition: all 0.2s ease;
            position: relative;
            z-index: 1;
        }

        .hero-chip:hover {
            background: rgba(255, 255, 255, 0.22);
            transform: translateY(-1px);
        }

        .hero-chip i {
            color: #fbbf24;
        }

        .hero-chip strong {
            font-size: 1rem;
            line-height: 1;
            display: block;
        }

        .hero-chip span {
            font-size: 0.74rem;
            color: rgba(255,255,255,0.8);
            display: block;
            margin-top: 2px;
        }

        .module-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .module-card {
            background: linear-gradient(180deg, #ffffff 0%, #fdfcff 100%);
            border: 1px solid #e8eaef;
            border-radius: 18px;
            padding: 22px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
            display: grid;
            gap: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .module-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(99, 102, 241, 0.1);
            border-color: #c7d2fe;
        }

        .module-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .module-card-title {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .module-card-subtitle {
            color: #64748b;
            font-size: 0.82rem;
        }

        .module-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            border-radius: 14px;
            font-size: 1.15rem;
            color: #fff;
            flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
        }

        .module-pill.persuratan { background: linear-gradient(135deg, #6366f1, #4f46e5); }
        .module-pill.rapat { background: linear-gradient(135deg, #0f766e, #0d9488); }
        .module-pill.cuti { background: linear-gradient(135deg, #15803d, #16a34a); }
        .module-pill.zi { background: linear-gradient(135deg, #8b5cf6, #4f46e5); }
        .module-pill.persediaan { background: linear-gradient(135deg, #b45309, #d97706); }

        .mobile-app-launcher {
            display: none;
        }

        .mobile-app-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .mobile-app-tile {
            position: relative;
            min-height: 104px;
            padding: 14px 10px 12px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            background: rgba(255, 255, 255, 0.92);
            color: #0f172a;
            text-decoration: none !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.07);
            transition: transform 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
        }

        .mobile-app-tile:active {
            transform: scale(0.97);
        }

        .mobile-app-icon {
            width: 48px;
            height: 48px;
            border-radius: 17px;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            box-shadow: 0 10px 24px rgba(79, 70, 229, 0.20);
        }

        .mobile-app-title {
            min-height: 28px;
            color: #172033;
            font-size: 0.76rem;
            font-weight: 800;
            line-height: 1.18;
            text-align: center;
        }

        .mobile-app-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: #ef4444;
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.62rem;
            font-weight: 900;
            border: 2px solid #ffffff;
        }

        .mobile-app-icon.dashboard { background: linear-gradient(135deg, #4f46e5, #8b5cf6); }
        .mobile-app-icon.action { background: linear-gradient(135deg, #ef4444, #f97316); }
        .mobile-app-icon.calendar { background: linear-gradient(135deg, #2563eb, #06b6d4); }
        .mobile-app-icon.approval { background: linear-gradient(135deg, #0f766e, #14b8a6); }
        .mobile-app-icon.mail { background: linear-gradient(135deg, #4f46e5, #2563eb); }
        .mobile-app-icon.meeting { background: linear-gradient(135deg, #0891b2, #0f766e); }
        .mobile-app-icon.leave { background: linear-gradient(135deg, #dc2626, #ef4444); }
        .mobile-app-icon.asset { background: linear-gradient(135deg, #d97706, #f59e0b); }
        .mobile-app-icon.supply { background: linear-gradient(135deg, #059669, #10b981); }
        .mobile-app-icon.zi { background: linear-gradient(135deg, #8b5cf6, #4f46e5); }
        .mobile-app-icon.archive { background: linear-gradient(135deg, #475569, #0f172a); }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .metric-box {
            border-radius: 14px;
            border: 1px solid #e8eaef;
            background: linear-gradient(135deg, #f8fafc, #fdfcff);
            padding: 14px 14px;
            min-height: 76px;
            transition: all 0.2s ease;
        }

        .metric-box:hover {
            border-color: #c7d2fe;
            background: linear-gradient(135deg, #eef2ff, #f5f3ff);
        }

        .metric-box .value {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
            margin-bottom: 6px;
        }

        .metric-box .label {
            font-size: 0.78rem;
            color: #64748b;
            line-height: 1.25;
        }

        .module-link-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .module-link-row a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 10px;
            background: #eef2ff;
            color: #4f46e5;
            padding: 9px 14px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .module-link-row a:hover {
            background: #4f46e5;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }

        .module-link-row a.alt:hover {
            background: #15803d;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(21, 128, 61, 0.25);
        }

        .module-link-row a.alt {
            background: #f0fdf4;
            color: #15803d;
        }

        .dashboard-row {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 18px;
        }

        .dash-panel {
            background: linear-gradient(180deg, #ffffff 0%, #fdfcff 100%);
            border: 1px solid #e8eaef;
            border-radius: 18px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .dash-panel:hover {
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.06);
        }

        .dash-panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 20px 22px 14px;
            border-bottom: 1px solid #eef2f7;
            background: linear-gradient(135deg, #fdfcff, #f8fafc);
        }

        .dash-panel-head h5 {
            margin: 0;
            font-size: 0.98rem;
            font-weight: 800;
            color: #0f172a;
        }

        .dash-panel-head p {
            margin: 3px 0 0;
            font-size: 0.78rem;
            color: #64748b;
        }

        .dash-panel-body {
            padding: 8px 20px 18px;
        }

        .action-list,
        .recent-list,
        .upcoming-list {
            display: grid;
            gap: 10px;
        }

        .action-item,
        .recent-item,
        .upcoming-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 12px;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .recent-item,
        .upcoming-item {
            grid-template-columns: 1fr auto;
        }

        .action-item:last-child,
        .recent-item:last-child,
        .upcoming-item:last-child {
            border-bottom: none;
        }

        .action-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            color: #fff;
            margin-top: 2px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .tone-blue { background: linear-gradient(135deg, #6366f1, #4f46e5); }
        .tone-amber { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .tone-green { background: linear-gradient(135deg, #22c55e, #15803d); }
        .tone-red { background: linear-gradient(135deg, #ef4444, #b91c1c); }
        .tone-purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }

        .item-title {
            font-size: 0.87rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 3px;
        }

        .item-subtitle {
            font-size: 0.78rem;
            color: #334155;
            margin-bottom: 3px;
        }

        .item-description,
        .item-meta {
            font-size: 0.76rem;
            color: #64748b;
            line-height: 1.35;
        }

        .item-link {
            align-self: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #eef2ff;
            color: #4f46e5;
            text-decoration: none;
            flex-shrink: 0;
            transition: all 0.2s ease;
        }

        .item-link:hover {
            background: #4f46e5;
            color: #ffffff;
            transform: scale(1.08);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .list-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 64px;
            padding-left: 10px;
            margin-left: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 28px 12px;
            color: #94a3b8;
            font-size: 0.86rem;
        }

        .recent-columns {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .calendar-stat-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }

        .calendar-stat-card {
            border-radius: 14px;
            border: 1px solid #e8eaef;
            background: linear-gradient(135deg, #ffffff 0%, #fdfcff 100%);
            padding: 16px 16px;
            min-height: 84px;
            transition: all 0.2s ease;
        }

        .calendar-stat-card:hover {
            border-color: #c7d2fe;
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.06);
            transform: translateY(-2px);
        }

        .calendar-stat-value {
            font-size: 1.5rem;
            font-weight: 800;
            color: #4f46e5;
            line-height: 1;
            margin-bottom: 8px;
        }

        .calendar-stat-label {
            font-size: 0.78rem;
            color: #64748b;
            line-height: 1.35;
        }

        .calendar-overview-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 18px;
        }

        .calendar-side-stack {
            display: grid;
            gap: 16px;
        }

        .calendar-section-title {
            font-size: 0.82rem;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 10px;
        }

        .calendar-mini-list {
            display: grid;
            gap: 10px;
        }

        .calendar-mini-item {
            border: 1px solid #edf2f7;
            border-radius: 14px;
            background: #fff;
            padding: 12px 14px;
        }

        .calendar-mini-title {
            font-size: 0.84rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .calendar-mini-meta {
            font-size: 0.76rem;
            color: #64748b;
            line-height: 1.45;
        }

        .calendar-conflict-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border-radius: 999px;
            background: #fff7ed;
            color: #c2410c;
            font-size: 0.74rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .calendar-month-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            padding: 14px;
        }

        .calendar-weekday-grid,
        .calendar-day-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 8px;
        }

        .calendar-weekday {
            text-align: center;
            font-size: 0.74rem;
            font-weight: 700;
            color: #64748b;
            padding-bottom: 2px;
        }

        .calendar-week-row {
            display: grid;
            gap: 8px;
            margin-top: 8px;
        }

        .calendar-day-cell {
            min-height: 76px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #fff;
            padding: 10px 9px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .calendar-day-cell.outside {
            background: #f8fafc;
            color: #94a3b8;
        }

        .calendar-day-cell.today {
            border-color: #818cf8;
            box-shadow: inset 0 0 0 2px rgba(99, 102, 241, 0.2), 0 2px 8px rgba(99, 102, 241, 0.08);
            background: linear-gradient(135deg, #fdfcff, #eef2ff);
        }

        .calendar-day-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .calendar-day-number {
            font-size: 0.82rem;
            font-weight: 800;
            color: #0f172a;
        }

        .calendar-day-cell.outside .calendar-day-number {
            color: #94a3b8;
        }

        .calendar-day-count {
            font-size: 0.7rem;
            font-weight: 700;
            color: #64748b;
            line-height: 1;
        }

        .calendar-day-dots {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .calendar-day-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            flex-shrink: 0;
        }

        .calendar-day-dot.rapat { background: #4f46e5; }
        .calendar-day-dot.agenda_pimpinan { background: #64748b; }
        .calendar-day-dot.cuti { background: #dc2626; }
        .calendar-day-dot.zi { background: #d97706; }
        .calendar-day-dot.surat_tugas { background: #16a34a; }

        .calendar-today-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 10px;
            border-radius: 999px;
            background: #eef2ff;
            color: #4f46e5;
            font-size: 0.74rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        @media (max-width: 1199.98px) {
            .module-grid,
            .recent-columns,
            .dashboard-row,
            .calendar-overview-grid {
                grid-template-columns: 1fr;
            }

            .calendar-stat-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .dashboard-shell {
                gap: 14px;
            }

            .mobile-app-launcher {
                display: block;
            }

            .dashboard-shell > .dashboard-hero,
            .dashboard-shell > .dash-panel,
            .dashboard-shell > .module-grid,
            .dashboard-shell > .dashboard-row,
            .dashboard-shell > .recent-columns {
                display: none;
            }

            .mobile-app-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .dashboard-hero {
                padding: 16px 14px;
                border-radius: 14px;
            }

            .dashboard-hero-title {
                font-size: 1.05rem;
                line-height: 1.3;
                margin-bottom: 4px;
            }

            .dashboard-hero-meta {
                font-size: 0.76rem;
            }

            .hero-chip-wrap {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
                margin-top: 14px;
            }

            .hero-chip {
                width: 100%;
                min-height: 0;
                padding: 9px 10px;
                border-radius: 16px;
                gap: 8px;
                align-items: flex-start;
            }

            .hero-chip strong {
                font-size: 0.94rem;
            }

            .hero-chip span {
                font-size: 0.7rem;
                line-height: 1.25;
            }

            .metric-grid {
                grid-template-columns: 1fr;
            }

            .dash-panel-head,
            .module-card-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .module-card,
            .dash-panel {
                border-radius: 14px;
            }

            .module-card {
                padding: 16px;
            }

            .action-item,
            .recent-item,
            .upcoming-item {
                grid-template-columns: 1fr;
            }

            .calendar-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .calendar-weekday-grid,
            .calendar-day-grid {
                gap: 6px;
            }

            .calendar-day-cell {
                min-height: 68px;
                padding: 8px;
            }
        }

        @media (max-width: 390px) {
            .mobile-app-grid {
                gap: 10px;
            }

            .mobile-app-tile {
                min-height: 98px;
                padding-left: 8px;
                padding-right: 8px;
            }

            .mobile-app-icon {
                width: 44px;
                height: 44px;
                border-radius: 15px;
                font-size: 1.05rem;
            }

            .mobile-app-title {
                font-size: 0.7rem;
            }

        }

        .dashboard-compact {
            gap: 14px;
        }

        .dashboard-compact .dashboard-hero {
            display: grid;
            grid-template-columns: minmax(220px, 280px) minmax(0, 1fr);
            grid-template-rows: auto auto;
            align-items: center;
            gap: 18px;
            padding: 16px;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
        }

        .dashboard-compact .dashboard-hero::before,
        .dashboard-compact .dashboard-hero::after {
            content: none;
        }

        .dashboard-compact .dashboard-hero-title {
            grid-column: 1;
            grid-row: 1;
            margin: 0;
            color: #0f172a;
            font-size: 1.05rem;
            line-height: 1.25;
            align-self: end;
        }

        .dashboard-compact .dashboard-hero-meta {
            grid-column: 1;
            grid-row: 2;
            margin-top: 4px;
            color: #64748b;
            font-size: 0.78rem;
            align-self: start;
        }

        .dashboard-compact .hero-chip-wrap {
            grid-column: 2;
            grid-row: 1 / span 2;
            display: grid;
            grid-template-columns: repeat(6, minmax(112px, 1fr));
            gap: 8px;
            margin: 0;
            min-width: 0;
        }

        .dashboard-compact .hero-chip {
            min-height: 66px;
            padding: 10px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: #fbfcff;
            color: #0f172a;
            box-shadow: none;
            backdrop-filter: none;
            min-width: 0;
        }

        .dashboard-compact .hero-chip:hover {
            transform: none;
            background: #eef2ff;
            border-color: #c7d2fe;
        }

        .dashboard-compact .hero-chip i {
            color: #6957f5;
            font-size: 0.85rem;
        }

        .dashboard-compact .hero-chip strong {
            color: #111827;
            font-size: 1.08rem;
        }

        .dashboard-compact .hero-chip span {
            color: #64748b;
            font-size: 0.68rem;
            line-height: 1.2;
        }

        .dashboard-compact .module-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
        }

        .dashboard-compact .module-card {
            gap: 10px;
            padding: 12px;
            border-radius: 12px;
            border-color: #edf2f7;
            background: #ffffff;
            box-shadow: none;
        }

        .dashboard-compact .module-card:hover {
            transform: none;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.07);
        }

        .dashboard-compact .module-card-head {
            align-items: center;
        }

        .dashboard-compact .module-card-title {
            margin: 0;
            font-size: 0.82rem;
            line-height: 1.25;
        }

        .dashboard-compact .module-pill {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            font-size: 0.74rem;
            box-shadow: none;
        }

        .dashboard-compact .metric-grid {
            grid-template-columns: 1fr;
            gap: 0;
            border-top: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
        }

        .dashboard-compact .metric-box {
            min-height: 0;
            display: grid;
            grid-template-columns: 44px 1fr;
            align-items: center;
            gap: 8px;
            padding: 7px 0;
            border: 0;
            border-radius: 0;
            background: transparent;
        }

        .dashboard-compact .metric-box + .metric-box {
            border-top: 1px solid #f1f5f9;
        }

        .dashboard-compact .metric-box:hover {
            background: transparent;
            border-color: #f1f5f9;
        }

        .dashboard-compact .metric-box .value {
            margin: 0;
            color: #0f172a;
            font-size: 0.98rem;
            text-align: right;
        }

        .dashboard-compact .metric-box .label {
            color: #64748b;
            font-size: 0.68rem;
            line-height: 1.15;
        }

        .dashboard-compact .module-link-row {
            gap: 6px;
        }

        .dashboard-compact .module-link-row a {
            width: 30px;
            height: 30px;
            justify-content: center;
            padding: 0;
            border-radius: 8px;
            font-size: 0;
        }

        .dashboard-compact .module-link-row a i {
            margin: 0;
            font-size: 0.74rem;
        }

        .dashboard-compact .dash-panel {
            border-radius: 14px;
            box-shadow: none;
        }

        .dashboard-compact .dash-panel:hover {
            box-shadow: none;
        }

        .dashboard-compact .dash-panel-head {
            padding: 13px 15px 11px;
        }

        .dashboard-compact .dash-panel-head h5 {
            font-size: 0.88rem;
        }

        .dashboard-compact .dash-panel-body {
            padding: 4px 15px 12px;
        }

        .dashboard-compact .action-list,
        .dashboard-compact .upcoming-list {
            gap: 0;
        }

        .dashboard-compact .action-item,
        .dashboard-compact .upcoming-item {
            padding: 10px 0;
        }

        .dashboard-compact .action-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            font-size: 0.78rem;
        }

        .dashboard-compact .item-title {
            margin-bottom: 2px;
            font-size: 0.8rem;
        }

        .dashboard-compact .item-subtitle,
        .dashboard-compact .item-description,
        .dashboard-compact .item-meta {
            font-size: 0.7rem;
            line-height: 1.28;
        }

        .dashboard-compact .item-description {
            display: none;
        }

        .dashboard-compact .item-link {
            width: 32px;
            height: 32px;
            border-radius: 9px;
        }

        .dashboard-calendar-panel,
        .dashboard-compact .recent-columns {
            display: none;
        }

        @media (max-width: 1199.98px) {
            .dashboard-compact .dashboard-hero {
                grid-template-columns: 1fr;
                grid-template-rows: auto;
            }

            .dashboard-compact .dashboard-hero-title,
            .dashboard-compact .dashboard-hero-meta,
            .dashboard-compact .hero-chip-wrap {
                grid-column: 1;
                grid-row: auto;
            }

            .dashboard-compact .hero-chip-wrap {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .dashboard-compact .module-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .dashboard-compact .dashboard-hero,
            .dashboard-compact .dash-panel,
            .dashboard-compact .module-grid,
            .dashboard-compact .dashboard-row,
            .dashboard-compact .recent-columns {
                display: none;
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
    <div class="dashboard-shell dashboard-compact">
        @php($dashboardUser = auth()->user())
        @php($dashboardIsSuperAdmin = $dashboardUser && $dashboardUser->isSuperAdmin())
        @if($dashboardUser && !$dashboardUser->hasProfileSignature())
            <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;border-radius:12px;border:1px solid #fde68a;background:#fffbeb;color:#92400e;">
                <div>
                    <strong>Tanda tangan profil belum tersimpan.</strong>
                    <span>Simpan tanda tangan agar approval dan PDF memakai tanda tangan otomatis.</span>
                </div>
                <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-warning font-weight-bold">
                    <i class="fas fa-signature mr-1"></i> Simpan TTD
                </a>
            </div>
        @endif
        <section class="mobile-app-launcher">
            <div class="mobile-app-grid">
                <a href="{{ route('mobile.menu.show', 'dashboard') }}" class="mobile-app-tile">
                    <span class="mobile-app-icon dashboard"><i class="fas fa-th-large"></i></span>
                    <span class="mobile-app-title">Dashboard</span>
                </a>

                @if($dashboardUser && $dashboardUser->canAccessUnifiedActionCenter())
                    <a href="{{ route('mobile.menu.show', 'action') }}" class="mobile-app-tile">
                        @if(($dashboardSummary['action_count'] ?? 0) > 0)
                            <span class="mobile-app-badge">{{ ($dashboardSummary['action_count'] ?? 0) > 99 ? '99+' : ($dashboardSummary['action_count'] ?? 0) }}</span>
                        @endif
                        <span class="mobile-app-icon action"><i class="fas fa-bell"></i></span>
                        <span class="mobile-app-title">Tindak Lanjut</span>
                    </a>
                @endif

                @if($dashboardUser && $dashboardUser->canAccessIntegratedCalendar())
                    <a href="{{ route('mobile.menu.show', 'calendar') }}" class="mobile-app-tile">
                        <span class="mobile-app-icon calendar"><i class="far fa-calendar-alt"></i></span>
                        <span class="mobile-app-title">Kalender</span>
                    </a>
                @endif

                @if($dashboardIsSuperAdmin || ($dashboardUser && $dashboardUser->canAccessApprovalCenter()))
                    <a href="{{ route('mobile.menu.show', 'approval') }}" class="mobile-app-tile">
                        <span class="mobile-app-icon approval"><i class="fas fa-check-double"></i></span>
                        <span class="mobile-app-title">Approval</span>
                    </a>
                @endif

                @if($persuratan['enabled'])
                    <a href="{{ route('mobile.menu.show', 'persuratan') }}" class="mobile-app-tile">
                        @if(($dashboardSummary['today_masuk'] ?? 0) > 0)
                            <span class="mobile-app-badge">{{ ($dashboardSummary['today_masuk'] ?? 0) > 99 ? '99+' : ($dashboardSummary['today_masuk'] ?? 0) }}</span>
                        @endif
                        <span class="mobile-app-icon mail"><i class="fas fa-envelope-open-text"></i></span>
                        <span class="mobile-app-title">Persuratan</span>
                    </a>
                @endif

                @if($meeting['enabled'])
                    <a href="{{ route('mobile.menu.show', 'rapat') }}" class="mobile-app-tile">
                        <span class="mobile-app-icon meeting"><i class="fas fa-users"></i></span>
                        <span class="mobile-app-title">Rapat</span>
                    </a>
                @endif

                @if($leave['enabled'])
                    <a href="{{ route('mobile.menu.show', 'cuti') }}" class="mobile-app-tile">
                        @if(($dashboardSummary['pending_leave_approvals'] ?? 0) > 0)
                            <span class="mobile-app-badge">{{ ($dashboardSummary['pending_leave_approvals'] ?? 0) > 99 ? '99+' : ($dashboardSummary['pending_leave_approvals'] ?? 0) }}</span>
                        @endif
                        <span class="mobile-app-icon leave"><i class="fas fa-calendar-check"></i></span>
                        <span class="mobile-app-title">Cuti</span>
                    </a>
                @endif

                @if($inventory['enabled'])
                    <a href="{{ route('mobile.menu.show', 'perawatan') }}" class="mobile-app-tile">
                        <span class="mobile-app-icon asset"><i class="fas fa-tools"></i></span>
                        <span class="mobile-app-title">Perawatan</span>
                    </a>
                @endif

                @if($dashboardIsSuperAdmin || ($dashboardUser && $dashboardUser->canAccessSupplyModule()))
                    <a href="{{ route('mobile.menu.show', 'persediaan') }}" class="mobile-app-tile">
                        <span class="mobile-app-icon supply"><i class="fas fa-warehouse"></i></span>
                        <span class="mobile-app-title">Persediaan</span>
                    </a>
                @endif

                @if($progressZi['enabled'])
                    <a href="{{ route('mobile.menu.show', 'zi') }}" class="mobile-app-tile">
                        <span class="mobile-app-icon zi"><i class="fas fa-chart-line"></i></span>
                        <span class="mobile-app-title">Progress ZI</span>
                    </a>
                @endif

                @if($dashboardIsSuperAdmin || ($dashboardUser && $dashboardUser->canAccessArchiveMenu()))
                    <a href="{{ route('mobile.menu.show', 'arsip') }}" class="mobile-app-tile">
                        <span class="mobile-app-icon archive"><i class="far fa-folder-open"></i></span>
                        <span class="mobile-app-title">Arsip</span>
                    </a>
                @endif
            </div>
        </section>
        <section class="dashboard-hero">
            <div class="dashboard-hero-title">{{ auth()->user()->name }}</div>
            <div class="dashboard-hero-meta">{{ now()->translatedFormat('l, d F Y') }}</div>
            <div class="hero-chip-wrap">
                <div class="hero-chip">
                    <i class="fas fa-bell"></i>
                    <div>
                        <strong>{{ $dashboardSummary['action_count'] }}</strong>
                        <span>Tindak lanjut</span>
                    </div>
                </div>
                <div class="hero-chip">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>{{ $dashboardSummary['action_high_count'] }}</strong>
                        <span>Prioritas tinggi</span>
                    </div>
                </div>
                <div class="hero-chip">
                    <i class="fas fa-hourglass-end"></i>
                    <div>
                        <strong>{{ $dashboardSummary['action_overdue_count'] }}</strong>
                        <span>Item overdue</span>
                    </div>
                </div>
                <div class="hero-chip">
                    <i class="fas fa-inbox"></i>
                    <div>
                        <strong>{{ $dashboardSummary['today_masuk'] }}</strong>
                        <span>Surat masuk</span>
                    </div>
                </div>
                <div class="hero-chip">
                    <i class="fas fa-calendar-alt"></i>
                    <div>
                        <strong>{{ $dashboardSummary['upcoming_meetings'] }}</strong>
                        <span>Agenda mendatang</span>
                    </div>
                </div>
                <div class="hero-chip">
                    <i class="fas fa-calendar-check"></i>
                    <div>
                        <strong>{{ $dashboardSummary['pending_leave_approvals'] }}</strong>
                        <span>Cuti pending</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="dash-panel dashboard-calendar-panel">
            <div class="dash-panel-head">
                <div>
                    <h5>Kalender Bulan Ini</h5>
                    <p>{{ $calendarOverview['month_label'] }}</p>
                </div>
                <div class="module-link-row" style="margin: 0;">
                    <a href="{{ route('calendar.integrated.index') }}"><i class="fas fa-calendar-alt"></i> Buka Kalender</a>
                </div>
            </div>
            <div class="dash-panel-body">
                <div class="calendar-stat-grid">
                    <div class="calendar-stat-card">
                        <div class="calendar-stat-value">{{ $calendarOverview['event_count'] }}</div>
                        <div class="calendar-stat-label">Event bulan ini</div>
                    </div>
                    <div class="calendar-stat-card">
                        <div class="calendar-stat-value">{{ $calendarOverview['days_with_events'] }}</div>
                        <div class="calendar-stat-label">Hari dengan agenda</div>
                    </div>
                    <div class="calendar-stat-card">
                        <div class="calendar-stat-value">{{ $calendarOverview['meeting_count'] }}</div>
                        <div class="calendar-stat-label">Rapat & agenda</div>
                    </div>
                    <div class="calendar-stat-card">
                        <div class="calendar-stat-value">{{ $calendarOverview['agenda_pimpinan_count'] }}</div>
                        <div class="calendar-stat-label">Agenda pimpinan</div>
                    </div>
                    <div class="calendar-stat-card">
                        <div class="calendar-stat-value">{{ $calendarOverview['leave_count'] }}</div>
                        <div class="calendar-stat-label">Cuti pegawai</div>
                    </div>
                    <div class="calendar-stat-card">
                        <div class="calendar-stat-value">{{ $calendarOverview['zi_count'] }}</div>
                        <div class="calendar-stat-label">Progress ZI</div>
                    </div>
                    <div class="calendar-stat-card">
                        <div class="calendar-stat-value">{{ $calendarOverview['surat_tugas_count'] }}</div>
                        <div class="calendar-stat-label">Surat tugas</div>
                    </div>
                    <div class="calendar-stat-card">
                        <div class="calendar-stat-value">{{ $calendarOverview['conflict_count'] }}</div>
                        <div class="calendar-stat-label">Benturan jadwal</div>
                    </div>
                </div>

                <div class="calendar-overview-grid">
                    <div>
                        <div class="calendar-section-title">Kalender Mini</div>
                        <div class="calendar-month-card">
                            <div class="calendar-weekday-grid">
                                @foreach($calendarOverview['weekday_labels'] as $label)
                                    <div class="calendar-weekday">{{ $label }}</div>
                                @endforeach
                            </div>

                            @foreach($calendarOverview['month_days'] as $week)
                                <div class="calendar-week-row">
                                    <div class="calendar-day-grid">
                                        @foreach($week as $day)
                                            <div class="calendar-day-cell {{ $day['in_month'] ? '' : 'outside' }} {{ $day['is_today'] ? 'today' : '' }}">
                                                <div class="calendar-day-top">
                                                    <div class="calendar-day-number">{{ $day['day'] }}</div>
                                                    @if($day['event_count'] > 0)
                                                        <div class="calendar-day-count">{{ $day['event_count'] }}</div>
                                                    @endif
                                                </div>
                                                <div class="calendar-day-dots">
                                                    @foreach($day['module_keys'] as $moduleKey)
                                                        <span class="calendar-day-dot {{ $moduleKey }}"></span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <div class="calendar-side-stack">
                            <div>
                                <div class="calendar-section-title">Event Saya Hari Ini</div>
                                @if($calendarOverview['today_events']->isEmpty())
                                    <div class="empty-state" style="padding: 20px 12px;">Tidak ada event hari ini.</div>
                                @else
                                    <div class="calendar-mini-list">
                                        @foreach($calendarOverview['today_events'] as $item)
                                            <div class="calendar-mini-item">
                                                <div class="calendar-today-chip">
                                                    <i class="fas fa-calendar-day"></i>
                                                    {{ $item['module'] }} &bull; {{ $item['status'] }}
                                                </div>
                                                <div class="calendar-mini-title">{{ $item['title'] }}</div>
                                                <div class="calendar-mini-meta">{{ $item['time'] }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div>
                                <div class="calendar-section-title">Benturan Jadwal</div>
                                @if($calendarOverview['conflicts']->isEmpty())
                                    <div class="empty-state" style="padding: 20px 12px;">Tidak ada benturan jadwal.</div>
                                @else
                                    <div class="calendar-mini-list">
                                        @foreach($calendarOverview['conflicts'] as $item)
                                            <div class="calendar-mini-item">
                                                <div class="calendar-conflict-chip">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    {{ $item['count'] }} agenda di {{ $item['date'] }}
                                                </div>
                                                <div class="calendar-mini-meta">{{ implode(' &bull; ', $item['titles']) }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 18px;">
                    <div class="calendar-section-title">Agenda Mendatang</div>
                    @if($calendarOverview['upcoming']->isEmpty())
                        <div class="empty-state" style="padding: 20px 12px;">Tidak ada agenda mendatang.</div>
                    @else
                        <div class="calendar-mini-list">
                            @foreach($calendarOverview['upcoming'] as $item)
                                <div class="calendar-mini-item">
                                    <div class="calendar-mini-title">{{ $item['title'] }}</div>
                                    <div class="calendar-mini-meta">{{ $item['date'] }} &bull; {{ $item['module'] }} &bull; {{ $item['status'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="module-grid">
            @if($persuratan['enabled'])
                <article class="module-card">
                    <div class="module-card-head">
                        <div>
                            <div class="module-card-title">Persuratan</div>

                        </div>
                        <div class="module-pill persuratan"><i class="fas fa-envelope-open-text"></i></div>
                    </div>
                    <div class="metric-grid">
                        <div class="metric-box">
                            <div class="value">{{ $persuratan['stats']['total_masuk'] }}</div>
                            <div class="label">Surat masuk</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $persuratan['stats']['surat_baru'] }}</div>
                            <div class="label">Surat masuk baru</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $persuratan['stats']['disposisi_pending'] }}</div>
                            <div class="label">Disposisi pending</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $persuratan['stats']['keluar_draft'] }}</div>
                            <div class="label">Surat keluar draft</div>
                        </div>
                    </div>
                    <div class="module-link-row">
                        <a href="{{ route('surat-masuk.index') }}"><i class="fas fa-inbox"></i> Surat Masuk</a>
                        <a href="{{ route('surat-keluar.index') }}" class="alt"><i class="fas fa-paper-plane"></i> Surat Keluar</a>
                    </div>
                </article>
            @endif

            @if($meeting['enabled'])
                <article class="module-card">
                    <div class="module-card-head">
                        <div>
                            <div class="module-card-title">Rapat / Agenda</div>

                        </div>
                        <div class="module-pill rapat"><i class="fas fa-calendar-week"></i></div>
                    </div>
                    <div class="metric-grid">
                        <div class="metric-box">
                            <div class="value">{{ $meeting['stats']['total_rapat'] }}</div>
                            <div class="label">Total rapat</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $meeting['stats']['total_agenda'] }}</div>
                            <div class="label">Agenda pimpinan</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $meeting['stats']['pending_undangan'] + $meeting['stats']['pending_notulensi'] }}</div>
                            <div class="label">Approval pending</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $meeting['stats']['pending_tindak_lanjut'] }}</div>
                            <div class="label">Tindak lanjut</div>
                        </div>
                    </div>
                    <div class="module-link-row">
                        <a href="{{ route('rapat.index') }}"><i class="fas fa-users"></i> Rapat</a>
                        <a href="{{ route('rapat.absensi.index') }}" class="alt"><i class="fas fa-clipboard-check"></i> Absensi</a>
                        <a href="{{ route('rapat.laporan.index') }}"><i class="fas fa-file-pdf"></i> Laporan</a>
                    </div>
                </article>
            @endif

            @if($leave['enabled'])
                <article class="module-card">
                    <div class="module-card-head">
                        <div>
                            <div class="module-card-title">Cuti</div>

                        </div>
                        <div class="module-pill cuti"><i class="fas fa-calendar-check"></i></div>
                    </div>
                    <div class="metric-grid">
                        <div class="metric-box">
                            <div class="value">{{ $leave['stats']['pengajuan_saya'] }}</div>
                            <div class="label">Pengajuan saya</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $leave['stats']['diproses'] }}</div>
                            <div class="label">Sedang diproses</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $leave['stats']['disetujui'] }}</div>
                            <div class="label">Disetujui</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $leave['stats']['approval_pending'] }}</div>
                            <div class="label">Cuti pending</div>
                        </div>
                    </div>
                    <div class="module-link-row">
                        <a href="{{ route('cuti.index') }}"><i class="fas fa-calendar-alt"></i> Pengajuan Cuti</a>
                        <a href="{{ route('cuti.reports.index') }}" class="alt"><i class="fas fa-chart-bar"></i> Laporan Cuti</a>
                    </div>
                </article>
            @endif

            @if($inventory['enabled'])
                <article class="module-card">
                    <div class="module-card-head">
                        <div>
                            <div class="module-card-title">Perawatan Alat dan Mesin</div>

                        </div>
                        <div class="module-pill persediaan"><i class="fas fa-boxes"></i></div>
                    </div>
                    <div class="metric-grid">
                        <div class="metric-box">
                            <div class="value">{{ $inventory['stats']['item_count'] }}</div>
                            <div class="label">Barang induk</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $inventory['stats']['detail_count'] }}</div>
                            <div class="label">Sub barang</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $inventory['stats']['room_count'] }}</div>
                            <div class="label">Ruang</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $inventory['stats']['maintenance_count'] }}</div>
                            <div class="label">Transaksi perawatan</div>
                        </div>
                    </div>
                    <div class="module-link-row">
                        <a href="{{ route('perawatan-alat-mesin.index') }}"><i class="fas fa-tools"></i> Modul</a>
                        <a href="{{ route('perawatan-alat-mesin.maintenance.index') }}" class="alt"><i class="fas fa-file-medical-alt"></i> Transaksi</a>
                    </div>
                </article>
            @endif

            @if($progressZi['enabled'])
                <article class="module-card">
                    <div class="module-card-head">
                        <div>
                            <div class="module-card-title">Progress ZI</div>

                        </div>
                        <div class="module-pill zi"><i class="fas fa-chart-line"></i></div>
                    </div>
                    <div class="metric-grid">
                        <div class="metric-box">
                            <div class="value">{{ $progressZi['stats']['area_count'] }}</div>
                            <div class="label">Area aktif</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $progressZi['stats']['activity_count'] }}</div>
                            <div class="label">Kegiatan</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ $progressZi['stats']['indicator_count'] }}</div>
                            <div class="label">Indikator</div>
                        </div>
                        <div class="metric-box">
                            <div class="value">{{ rtrim(rtrim(number_format($progressZi['stats']['period_score'], 1), '0'), '.') }}%</div>
                            <div class="label">{{ $progressZi['stats']['period_name'] }}</div>
                        </div>
                    </div>
                    <div class="module-link-row">
                        <a href="{{ route('progress-zi.dashboard') }}"><i class="fas fa-chart-line"></i> Dashboard ZI</a>
                        <a href="{{ route('progress-zi.activities.index') }}" class="alt"><i class="fas fa-tasks"></i> Monitoring</a>
                    </div>
                </article>
            @endif
        </section>

        <section class="dashboard-row">
            <div class="dash-panel">
                <div class="dash-panel-head">
                    <div>
                        <h5>Yang Perlu Ditindaklanjuti</h5>
                    </div>
                </div>
                <div class="dash-panel-body">
                    @if($actionItems->isEmpty())
                        <div class="empty-state">Tidak ada tindak lanjut aktif saat ini.</div>
                    @else
                        <div class="action-list">
                            @foreach($actionItems as $item)
                                <div class="action-item">
                                    <div class="action-icon tone-{{ $item['tone'] }}">
                                        <i class="{{ $item['icon'] }}"></i>
                                    </div>
                                    <div>
                                        <div class="item-title">{{ $item['title'] }}</div>
                                        <div class="item-subtitle">{{ $item['subtitle'] }}</div>
                                        <div class="item-description">{{ $item['description'] }}</div>
                                        <div class="item-meta">{{ $item['module'] }} &bull; {{ $item['time'] }}</div>
                                    </div>
                                    <a href="{{ $item['url'] }}" class="item-link" title="Buka">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="dash-panel">
                <div class="dash-panel-head">
                    <div>
                        <h5>Agenda Terdekat</h5>
                    </div>
                </div>
                <div class="dash-panel-body">
                    @if(!$meeting['enabled'] || $meeting['upcoming']->isEmpty())
                        <div class="empty-state">Belum ada rapat atau agenda mendatang.</div>
                    @else
                        <div class="upcoming-list">
                            @foreach($meeting['upcoming'] as $item)
                                <div class="upcoming-item">
                                    <div>
                                        <div class="item-title">{{ $item['title'] }}</div>
                                        <div class="item-subtitle">{{ $item['meta'] }}</div>
                                        <div class="item-description">{{ $item['submeta'] }}</div>
                                    </div>
                                    <div class="list-badge">
                                        {!! $item['badge'] !!}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="recent-columns">
            @if($persuratan['enabled'])
                <div class="dash-panel">
                    <div class="dash-panel-head">
                        <div>
                            <h5>Persuratan Terbaru</h5>
                        </div>
                    </div>
                    <div class="dash-panel-body">
                        @if($persuratan['recent']->isEmpty())
                            <div class="empty-state">Belum ada data persuratan.</div>
                        @else
                            <div class="recent-list">
                                @foreach($persuratan['recent'] as $item)
                                    <div class="recent-item">
                                        <div>
                                            <div class="item-title">{{ $item['title'] }}</div>
                                            <div class="item-subtitle">{{ $item['type'] }} &bull; {{ $item['subtitle'] }}</div>
                                            <div class="item-meta">{{ $item['meta'] }}</div>
                                        </div>
                                        <div class="list-badge">{!! $item['badge'] !!}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($meeting['enabled'])
                <div class="dash-panel">
                    <div class="dash-panel-head">
                        <div>
                            <h5>Rapat / Agenda Terbaru</h5>
                        </div>
                    </div>
                    <div class="dash-panel-body">
                        @if($meeting['recent']->isEmpty())
                            <div class="empty-state">Belum ada data rapat atau agenda.</div>
                        @else
                            <div class="recent-list">
                                @foreach($meeting['recent'] as $item)
                                    <div class="recent-item">
                                        <div>
                                            <div class="item-title">{{ $item['title'] }}</div>
                                            <div class="item-subtitle">{{ $item['type'] }} &bull; {{ $item['subtitle'] }}</div>
                                            <div class="item-meta">{{ $item['meta'] }}</div>
                                        </div>
                                        <div class="list-badge">{!! $item['badge'] !!}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($leave['enabled'])
                <div class="dash-panel">
                    <div class="dash-panel-head">
                        <div>
                            <h5>Cuti Terbaru</h5>
                        </div>
                    </div>
                    <div class="dash-panel-body">
                        @if($leave['recent']->isEmpty())
                            <div class="empty-state">Belum ada data cuti.</div>
                        @else
                            <div class="recent-list">
                                @foreach($leave['recent'] as $item)
                                    <div class="recent-item">
                                        <div>
                                            <div class="item-title">{{ $item['title'] }}</div>
                                            <div class="item-subtitle">{{ $item['subtitle'] }}</div>
                                            <div class="item-meta">{{ $item['meta'] }}</div>
                                        </div>
                                        <div class="list-badge">{!! $item['badge'] !!}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($inventory['enabled'])
                <div class="dash-panel">
                    <div class="dash-panel-head">
                        <div>
                            <h5>Perawatan Terbaru</h5>
                        </div>
                    </div>
                    <div class="dash-panel-body">
                        @if($inventory['recent']->isEmpty())
                            <div class="empty-state">Belum ada data alat, mesin, atau transaksi perawatan.</div>
                        @else
                            <div class="recent-list">
                                @foreach($inventory['recent'] as $item)
                                    <div class="recent-item">
                                        <div>
                                            <div class="item-title">{{ $item['title'] }}</div>
                                            <div class="item-subtitle">{{ $item['type'] }} &bull; {{ $item['subtitle'] }}</div>
                                            <div class="item-meta">{{ $item['meta'] }}</div>
                                        </div>
                                        <div class="list-badge">{!! $item['badge'] !!}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    </div>
@endsection
