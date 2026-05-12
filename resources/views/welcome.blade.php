<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Informasi PTA Papua Barat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --navy-900: #081a2c;
            --navy-800: #0f2640;
            --navy-700: #154066;
            --blue-500: #2c6bed;
            --blue-400: #4f89ff;
            --gold-500: #d9a441;
            --gold-400: #ebc16c;
            --text: #102132;
            --muted: #61748a;
            --line: rgba(15, 38, 64, 0.1);
            --surface: rgba(255, 255, 255, 0.86);
            --surface-strong: #ffffff;
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(217, 164, 65, 0.12), transparent 34%),
                radial-gradient(circle at right 15%, rgba(79, 137, 255, 0.14), transparent 26%),
                linear-gradient(180deg, #eef4fb 0%, #f8fbff 42%, #ffffff 100%);
        }

        a { color: inherit; text-decoration: none; }

        .container {
            width: min(1180px, calc(100% - 40px));
            margin: 0 auto;
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(14px);
            background: rgba(248, 251, 255, 0.72);
            border-bottom: 1px solid rgba(255, 255, 255, 0.6);
        }

        .site-header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            min-height: 78px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-mark {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--navy-800), var(--blue-500));
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 900;
            box-shadow: 0 14px 32px rgba(12, 39, 66, 0.22);
            overflow: hidden;
            flex-shrink: 0;
        }

        .brand-mark img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .brand-text strong {
            display: block;
            font-size: 0.95rem;
            letter-spacing: -0.02em;
        }

        .brand-text span {
            display: block;
            font-size: 0.78rem;
            color: var(--muted);
            margin-top: 2px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .link-pill,
        .btn-primary,
        .btn-secondary {
            border-radius: 999px;
            padding: 14px 22px;
            font-size: 0.9rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: transform .16s ease, box-shadow .16s ease, background .16s ease;
        }

        .link-pill {
            color: var(--navy-700);
            background: rgba(255,255,255,0.72);
            border: 1px solid rgba(15, 38, 64, 0.08);
        }

        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, #1a3a5c, #2c5282);
            box-shadow: 0 14px 30px rgba(26, 58, 92, 0.26);
            border: none;
        }

        .btn-secondary {
            color: var(--navy-800);
            background: linear-gradient(135deg, #fff, #edf4ff);
            border: 1px solid rgba(15, 38, 64, 0.08);
        }

        .link-pill:hover,
        .btn-primary:hover,
        .btn-secondary:hover {
            transform: translateY(-1px);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #15314e, #23476f);
            box-shadow: 0 16px 34px rgba(26, 58, 92, 0.32);
        }

        .hero {
            padding: 68px 0 42px;
        }

        .hero-shell {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 28px;
            align-items: stretch;
        }

        .hero-card {
            position: relative;
            overflow: hidden;
            border-radius: 30px;
            padding: 38px;
            min-height: 520px;
            background:
                radial-gradient(circle at top right, rgba(235, 193, 108, 0.16), transparent 26%),
                linear-gradient(145deg, var(--navy-900) 0%, var(--navy-800) 52%, var(--navy-700) 100%);
            color: #fff;
            box-shadow: 0 32px 70px rgba(8, 26, 44, 0.24);
        }

        .hero-card::after {
            content: '';
            position: absolute;
            right: -60px;
            bottom: -70px;
            width: 240px;
            height: 240px;
            border-radius: 40px;
            background: linear-gradient(135deg, rgba(79, 137, 255, 0.28), rgba(217, 164, 65, 0.24));
            transform: rotate(22deg);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 999px;
            padding: 8px 14px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .hero-title {
            margin: 20px 0 16px;
            font-size: clamp(2.4rem, 4vw, 4.35rem);
            line-height: 0.98;
            letter-spacing: -0.05em;
            font-weight: 900;
            max-width: 760px;
            position: relative;
            z-index: 1;
        }

        .hero-title span {
            color: var(--gold-400);
        }

        .hero-copy {
            font-size: 1rem;
            line-height: 1.8;
            max-width: 700px;
            color: rgba(255,255,255,0.82);
            position: relative;
            z-index: 1;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 26px;
            position: relative;
            z-index: 1;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-top: 28px;
            position: relative;
            z-index: 1;
        }

        .stat-card {
            border-radius: 18px;
            padding: 18px 18px 16px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .stat-card strong {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 6px;
        }

        .stat-card span {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.7);
            line-height: 1.5;
        }

        .hero-side {
            display: grid;
            gap: 18px;
        }

        .hero-brand-strip {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 16px;
            border-radius: 20px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.1);
            margin: 18px 0 4px;
            position: relative;
            z-index: 1;
        }

        .hero-brand-strip img {
            width: 58px;
            height: 58px;
            object-fit: contain;
            border-radius: 14px;
            background: rgba(255,255,255,0.92);
            padding: 8px;
            flex-shrink: 0;
        }

        .hero-brand-strip strong {
            display: block;
            font-size: 0.92rem;
            margin-bottom: 4px;
        }

        .hero-brand-strip span {
            display: block;
            font-size: 0.78rem;
            color: rgba(255,255,255,0.74);
            line-height: 1.5;
        }

        .surface-card {
            background: rgba(255,255,255,0.92);
            border: 1px solid rgba(15, 38, 64, 0.08);
            border-radius: 26px;
            padding: 26px;
            box-shadow: 0 18px 48px rgba(15, 38, 64, 0.08);
        }

        .surface-card h3 {
            margin: 0 0 8px;
            font-size: 1.1rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }

        .surface-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
            font-size: 0.92rem;
        }

        .module-list {
            display: grid;
            gap: 12px;
            margin-top: 18px;
        }

        .module-item {
            display: grid;
            grid-template-columns: 46px 1fr;
            gap: 14px;
            align-items: start;
            padding: 14px;
            border-radius: 18px;
            background: linear-gradient(135deg, #ffffff, #f5f9ff);
            border: 1px solid rgba(15,38,64,0.08);
        }

        .module-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(44,107,237,0.14), rgba(217,164,65,0.18));
            color: var(--navy-800);
        }

        .module-item strong {
            display: block;
            font-size: 0.95rem;
            margin-bottom: 4px;
        }

        .module-item span {
            display: block;
            color: var(--muted);
            font-size: 0.84rem;
            line-height: 1.55;
        }

        .section {
            padding: 22px 0 30px;
        }

        .section-head {
            display: flex;
            justify-content: space-between;
            align-items: end;
            gap: 16px;
            margin-bottom: 18px;
        }

        .section-head h2 {
            margin: 0;
            font-size: 1.75rem;
            letter-spacing: -0.03em;
        }

        .section-head p {
            margin: 8px 0 0;
            color: var(--muted);
            max-width: 700px;
            line-height: 1.7;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .preview-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 18px;
        }

        .preview-card {
            border-radius: 28px;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--line);
            box-shadow: 0 18px 40px rgba(15, 38, 64, 0.07);
        }

        .preview-head {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid rgba(15, 38, 64, 0.08);
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        }

        .preview-head strong {
            font-size: 0.95rem;
            letter-spacing: -0.01em;
        }

        .preview-body {
            padding: 20px;
        }

        .preview-browser {
            border-radius: 22px;
            border: 1px solid rgba(15, 38, 64, 0.08);
            background: linear-gradient(180deg, #ffffff 0%, #f6f9fe 100%);
            overflow: hidden;
        }

        .preview-browser-top {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: #f8fbff;
            border-bottom: 1px solid rgba(15, 38, 64, 0.08);
        }

        .browser-dots {
            display: flex;
            gap: 6px;
        }

        .browser-dots span {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #cbd5e1;
        }

        .browser-url {
            border-radius: 999px;
            background: #fff;
            border: 1px solid rgba(15, 38, 64, 0.08);
            color: #64748b;
            font-size: 0.74rem;
            padding: 8px 12px;
            flex: 1;
            text-align: center;
        }

        .preview-dashboard {
            padding: 18px;
            display: grid;
            gap: 16px;
        }

        .preview-hero {
            border-radius: 20px;
            padding: 18px;
            color: #fff;
            background: linear-gradient(135deg, var(--navy-800), var(--blue-500));
        }

        .preview-hero strong {
            display: block;
            font-size: 1rem;
            margin-bottom: 6px;
        }

        .preview-hero span {
            font-size: 0.78rem;
            opacity: 0.8;
        }

        .preview-kpis {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .preview-kpi {
            border-radius: 14px;
            padding: 12px;
            background: #fff;
            border: 1px solid rgba(15, 38, 64, 0.08);
        }

        .preview-kpi strong {
            display: block;
            font-size: 1rem;
            margin-bottom: 4px;
        }

        .preview-kpi span {
            display: block;
            font-size: 0.72rem;
            color: var(--muted);
            line-height: 1.45;
        }

        .preview-panel-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .preview-panel {
            border-radius: 18px;
            border: 1px solid rgba(15, 38, 64, 0.08);
            background: #fff;
            padding: 14px;
        }

        .preview-panel strong {
            display: block;
            font-size: 0.84rem;
            margin-bottom: 10px;
        }

        .preview-list {
            display: grid;
            gap: 8px;
        }

        .preview-list-item {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            font-size: 0.75rem;
            color: #334155;
        }

        .preview-list-item span:last-child {
            color: #64748b;
            white-space: nowrap;
        }

        .preview-side-stack {
            display: grid;
            gap: 18px;
        }

        .preview-module-stack {
            display: grid;
            gap: 12px;
        }

        .preview-module {
            border-radius: 20px;
            padding: 18px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border: 1px solid rgba(15, 38, 64, 0.08);
            display: grid;
            gap: 12px;
        }

        .preview-module-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .preview-chip {
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 0.68rem;
            font-weight: 800;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .preview-chip.danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .preview-chip.info {
            background: #e0e7ff;
            color: #4f46e5;
        }

        .preview-chip.success {
            background: #dcfce7;
            color: #16a34a;
        }

        .preview-line {
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(90deg, #e0e7ff, #eef2ff);
        }

        .preview-line.short { width: 56%; }
        .preview-line.medium { width: 74%; }
        .preview-line.long { width: 100%; }

        .feature-card {
            border-radius: 24px;
            padding: 24px;
            background: var(--surface-strong);
            border: 1px solid var(--line);
            box-shadow: 0 14px 34px rgba(15, 38, 64, 0.06);
        }

        .feature-card .module-icon {
            margin-bottom: 18px;
        }

        .feature-card h3 {
            margin: 0 0 10px;
            font-size: 1.08rem;
        }

        .feature-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.75;
            font-size: 0.9rem;
        }

        .flow-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .flow-card {
            position: relative;
            overflow: hidden;
            border-radius: 26px;
            padding: 24px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border: 1px solid var(--line);
            box-shadow: 0 14px 34px rgba(15, 38, 64, 0.06);
        }

        .flow-step {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--navy-800), var(--blue-500));
            color: #fff;
            font-weight: 800;
            margin-bottom: 18px;
        }

        .flow-card h3 {
            margin: 0 0 10px;
            font-size: 1.05rem;
        }

        .flow-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
            font-size: 0.9rem;
        }

        .cta-band {
            margin: 18px 0 56px;
            padding: 28px;
            border-radius: 30px;
            background:
                radial-gradient(circle at left top, rgba(217,164,65,0.12), transparent 22%),
                linear-gradient(135deg, #ffffff 0%, #f3f8ff 100%);
            border: 1px solid var(--line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            box-shadow: 0 18px 44px rgba(15,38,64,0.08);
        }

        .cta-band h3 {
            margin: 0 0 6px;
            font-size: 1.42rem;
        }

        .cta-band p {
            margin: 0;
            color: var(--muted);
            line-height: 1.7;
        }

        .site-footer {
            padding: 0 0 34px;
            color: var(--muted);
            font-size: 0.85rem;
        }

        .advantage-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .advantage-card {
            border-radius: 24px;
            padding: 24px;
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            border: 1px solid rgba(15, 38, 64, 0.08);
            box-shadow: 0 14px 34px rgba(15, 38, 64, 0.06);
        }

        .advantage-card .module-icon {
            margin-bottom: 16px;
        }

        .advantage-card h3 {
            margin: 0 0 10px;
            font-size: 1.05rem;
        }

        .advantage-card p {
            margin: 0;
            color: var(--muted);
            line-height: 1.75;
            font-size: 0.9rem;
        }

        .site-footer-inner {
            border-top: 1px solid rgba(15, 38, 64, 0.08);
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        @media (max-width: 1080px) {
            .hero-shell,
            .feature-grid,
            .flow-grid,
            .cta-band,
            .preview-grid,
            .advantage-grid {
                grid-template-columns: 1fr;
            }

            .hero-shell {
                grid-template-columns: 1fr;
            }

            .feature-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .preview-kpis,
            .preview-panel-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .flow-grid {
                grid-template-columns: 1fr;
            }

            .cta-band {
                display: grid;
            }
        }

        @media (max-width: 720px) {
            .container { width: min(100% - 24px, 1180px); }
            .site-header-inner { min-height: 72px; }
            .header-actions { gap: 8px; }
            .link-pill,
            .btn-primary,
            .btn-secondary { padding: 11px 16px; font-size: 0.84rem; }
            .hero { padding-top: 38px; }
            .hero-card,
            .surface-card,
            .feature-card,
            .flow-card,
            .cta-band,
            .preview-card,
            .advantage-card { padding: 22px; border-radius: 24px; }
            .feature-grid,
            .preview-kpis,
            .preview-panel-grid,
            .advantage-grid { grid-template-columns: 1fr; }
            .hero-stats { grid-template-columns: 1fr; }
            .site-footer-inner { flex-direction: column; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container site-header-inner">
            <a href="{{ url('/') }}" class="brand">
                <div class="brand-mark">
                    <img src="{{ asset('logo_app.png') }}" alt="Logo Sistem Informasi Terpadu PTA Papua Barat">
                </div>
                <div class="brand-text">
                    <strong>Sistem Informasi PTA Papua Barat</strong>
                    <span>Persuratan, rapat, cuti, dan monitoring Zona Integritas</span>
                </div>
            </a>
            <div class="header-actions">
                <a href="#modul" class="link-pill"><i class="fas fa-th-large"></i>Modul</a>
                <a href="#preview" class="link-pill"><i class="fas fa-desktop"></i>Visual</a>
                <a href="#keunggulan" class="link-pill"><i class="fas fa-star"></i>Keunggulan</a>
                <a href="{{ route('login') }}" class="btn-primary"><i class="fas fa-sign-in-alt"></i>Masuk ke Sistem</a>
            </div>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-shell">
                <section class="hero-card">
                    <div class="eyebrow"><i class="fas fa-landmark"></i>PTA Papua Barat</div>
                    <div class="hero-brand-strip">
                        <img src="{{ asset('logo_app.png') }}" alt="Logo Resmi PTA Papua Barat">
                        <div>
                            <strong>Pengadilan Tinggi Agama Papua Barat</strong>
                            <span>Sistem informasi terpadu untuk persuratan, rapat, layanan kepegawaian, dan rekapan Zona Integritas.</span>
                        </div>
                    </div>
                    <h1 class="hero-title">Satu pintu kerja digital untuk <span>persuratan</span>, <span>rapat</span>, <span>cuti</span>, dan <span>Zona Integritas</span>.</h1>
                    <p class="hero-copy">
                        Aplikasi ini dirancang untuk menata alur administrasi internal secara lebih cepat, terdokumentasi,
                        dan siap ditelusuri. Pengajuan, approval, eviden, arsip, dan monitoring berada dalam satu ekosistem kerja.
                    </p>
                    <div class="hero-actions">
                        <a href="{{ route('login') }}" class="btn-primary"><i class="fas fa-arrow-right"></i>Mulai Bekerja</a>
                        <a href="#alur" class="btn-secondary"><i class="fas fa-stream"></i>Lihat Alur Sistem</a>
                    </div>
                    <div class="hero-stats">
                        <div class="stat-card">
                            <strong>4</strong>
                            <span>Modul utama yang saling terhubung dan saling memberi eviden.</span>
                        </div>
                        <div class="stat-card">
                            <strong>1</strong>
                            <span>Pusat approval untuk tindak lanjut dokumen, review, dan persetujuan pimpinan.</span>
                        </div>
                        <div class="stat-card">
                            <strong>âˆž</strong>
                            <span>Siap dikembangkan untuk template surat, integrasi eviden, dan dashboard lintas unit.</span>
                        </div>
                    </div>
                </section>

                <aside class="hero-side">
                    <section class="surface-card">
                        <h3>Modul yang sudah berjalan</h3>
                        <p>Setiap modul sudah memakai pola kerja yang konsisten: pencatatan, approval, arsip, preview, dan pelacakan status.</p>
                        <div class="module-list">
                            <div class="module-item">
                                <div class="module-icon"><i class="fas fa-envelope-open-text"></i></div>
                                <div>
                                    <strong>Persuratan</strong>
                                    <span>Surat masuk, surat keluar, template surat, approval, dan arsip terintegrasi.</span>
                                </div>
                            </div>
                            <div class="module-item">
                                <div class="module-icon"><i class="fas fa-users"></i></div>
                                <div>
                                    <strong>Rapat / Agenda</strong>
                                    <span>Undangan, absensi, notulensi, tindak lanjut, agenda pimpinan, dan laporan.</span>
                                </div>
                            </div>
                            <div class="module-item">
                                <div class="module-icon"><i class="fas fa-calendar-check"></i></div>
                                <div>
                                    <strong>Cuti</strong>
                                    <span>Pengajuan cuti ASN, approval berjenjang, form final, saldo, dan nomor surat.</span>
                                </div>
                            </div>
                            <div class="module-item">
                                <div class="module-icon"><i class="fas fa-chart-line"></i></div>
                                <div>
                                    <strong>Progress ZI</strong>
                                    <span>Pedoman, monitoring kegiatan, eviden lintas modul, review pimpinan, dan rekapan ZI.</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="surface-card">
                        <h3>Prinsip kerja sistem</h3>
                        <p>
                            Input hanya sekali, lalu dipakai lintas modul bila relevan. Eviden dari surat, rapat, dan cuti
                            dapat dimanfaatkan kembali untuk mendukung monitoring, pelaporan, dan review.
                        </p>
                    </section>
                </aside>
            </div>
        </section>

        <section class="section" id="modul">
            <div class="container">
                <div class="section-head">
                    <div>
                        <h2>Cakupan Modul</h2>
                        <p>Struktur aplikasi mengikuti kebutuhan kerja internal yang sudah berjalan di PTA Papua Barat, lalu dikonsolidasikan ke satu tampilan operasional yang rapi.</p>
                    </div>
                </div>
                <div class="feature-grid">
                    <article class="feature-card">
                        <div class="module-icon"><i class="fas fa-inbox"></i></div>
                        <h3>Surat Masuk</h3>
                        <p>Pencatatan surat, preview dokumen, disposisi, penelusuran penerima, dan arsip yang terhubung ke user terkait.</p>
                    </article>
                    <article class="feature-card">
                        <div class="module-icon"><i class="fas fa-paper-plane"></i></div>
                        <h3>Surat Keluar</h3>
                        <p>Nomor surat, template, file final, approval penandatangan, QR verifikasi, dan bundel dokumen saat diperlukan.</p>
                    </article>
                    <article class="feature-card">
                        <div class="module-icon"><i class="fas fa-handshake"></i></div>
                        <h3>Rapat dan Tindak Lanjut</h3>
                        <p>Undangan, absensi, notulensi, laporan tindak lanjut, approval, serta eviden yang dapat dipakai ulang.</p>
                    </article>
                    <article class="feature-card">
                        <div class="module-icon"><i class="fas fa-clipboard-list"></i></div>
                        <h3>Monitoring ZI</h3>
                        <p>Pedoman area, sub poin, status tindak lanjut, review pimpinan, dan rekapan capaian per periode evaluasi.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section" id="preview">
            <div class="container">
                <div class="section-head">
                    <div>
                        <h2>Visual Sistem</h2>
                        <p>Gambaran ini menunjukkan bahasa visual aplikasi yang dipakai di area internal: dashboard ringkas, approval, dan monitoring lintas modul.</p>
                    </div>
                </div>
                <div class="preview-grid">
                    <article class="preview-card">
                        <div class="preview-head">
                            <strong>Preview Dashboard Internal</strong>
                            <span class="preview-chip info"><i class="fas fa-eye"></i>Ringkasan Kerja</span>
                        </div>
                        <div class="preview-body">
                            <div class="preview-browser">
                                <div class="preview-browser-top">
                                    <div class="browser-dots"><span></span><span></span><span></span></div>
                                    <div class="browser-url">internal.pta-papuabarat.go.id/dashboard</div>
                                </div>
                                <div class="preview-dashboard">
                                    <div class="preview-hero">
                                        <strong>Ringkasan kerja lintas modul</strong>
                                        <span>Persuratan, rapat, cuti, dan rekapan ZI dalam satu tampilan operasional.</span>
                                    </div>
                                    <div class="preview-kpis">
                                        <div class="preview-kpi"><strong>12</strong><span>Butuh Tindak Lanjut</span></div>
                                        <div class="preview-kpi"><strong>7</strong><span>Surat Hari Ini</span></div>
                                        <div class="preview-kpi"><strong>4</strong><span>Agenda Terdekat</span></div>
                                        <div class="preview-kpi"><strong>3</strong><span>Approval Cuti</span></div>
                                    </div>
                                    <div class="preview-panel-grid">
                                        <div class="preview-panel">
                                            <strong>Persuratan</strong>
                                            <div class="preview-list">
                                                <div class="preview-list-item"><span>Surat Masuk Baru</span><span>3 item</span></div>
                                                <div class="preview-list-item"><span>Surat Keluar Draft</span><span>2 item</span></div>
                                                <div class="preview-list-item"><span>Template Surat</span><span>Aktif</span></div>
                                            </div>
                                        </div>
                                        <div class="preview-panel">
                                            <strong>Monitoring ZI</strong>
                                            <div class="preview-list">
                                                <div class="preview-list-item"><span>Sub Poin Ditindaklanjuti</span><span>14/18</span></div>
                                                <div class="preview-list-item"><span>Review Pimpinan</span><span>2 item</span></div>
                                                <div class="preview-list-item"><span>Eviden Perhatian</span><span>1 item</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>

                    <div class="preview-side-stack">
                        <article class="preview-card">
                            <div class="preview-head">
                                <strong>Preview Modul</strong>
                                <span class="preview-chip success"><i class="fas fa-layer-group"></i>Aktif</span>
                            </div>
                            <div class="preview-body">
                                <div class="preview-module-stack">
                                    <div class="preview-module">
                                        <div class="preview-module-top">
                                            <strong>Approval Center</strong>
                                            <span class="preview-chip danger"><i class="fas fa-bell"></i>4 Pending</span>
                                        </div>
                                        <div class="preview-line long"></div>
                                        <div class="preview-line medium"></div>
                                        <div class="preview-line short"></div>
                                    </div>
                                    <div class="preview-module">
                                        <div class="preview-module-top">
                                            <strong>Monitoring Kegiatan ZI</strong>
                                            <span class="preview-chip info"><i class="fas fa-tasks"></i>Review</span>
                                        </div>
                                        <div class="preview-line long"></div>
                                        <div class="preview-line long"></div>
                                        <div class="preview-line medium"></div>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <article class="preview-card">
                            <div class="preview-head">
                                <strong>Tombol Akses</strong>
                                <span class="preview-chip info"><i class="fas fa-sign-in-alt"></i>Login</span>
                            </div>
                            <div class="preview-body">
                                <p style="margin:0 0 16px;color:var(--muted);line-height:1.75;">Tombol akses utama di landing page disamakan dengan gaya tombol pada halaman login agar identitas visual aplikasi tetap konsisten dari area publik sampai area internal.</p>
                                <div class="header-actions" style="justify-content:flex-start;">
                                    <a href="{{ route('login') }}" class="btn-primary"><i class="fas fa-sign-in-alt"></i>Masuk ke Sistem</a>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="alur">
            <div class="container">
                <div class="section-head">
                    <div>
                        <h2>Alur Kerja Inti</h2>
                        <p>Landing page ini sengaja ringkas: cukup menjelaskan bagaimana dokumen bergerak, siapa yang memproses, dan di mana hasilnya termonitor.</p>
                    </div>
                </div>
                <div class="flow-grid">
                    <article class="flow-card">
                        <div class="flow-step">1</div>
                        <h3>Input dan Pencatatan</h3>
                        <p>User mencatat surat, membuat rapat, mengajukan cuti, atau menindaklanjuti sub poin ZI sesuai role dan kewenangannya.</p>
                    </article>
                    <article class="flow-card">
                        <div class="flow-step">2</div>
                        <h3>Approval dan Review</h3>
                        <p>Dokumen masuk ke approval center, dipreview langsung, lalu disetujui atau dikembalikan dengan catatan perbaikan.</p>
                    </article>
                    <article class="flow-card">
                        <div class="flow-step">3</div>
                        <h3>Arsip dan Monitoring</h3>
                        <p>Dokumen final, eviden, dan status capaian masuk ke arsip serta rekapan dashboard agar mudah diaudit dan dipantau.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section" id="keunggulan">
            <div class="container">
                <div class="section-head">
                    <div>
                        <h2>Keunggulan Sistem</h2>
                        <p>Bagian ini menekankan manfaat sistem dari sudut pandang pelaksanaan kerja instansi: lebih cepat, lebih tertelusur, dan lebih mudah dipakai lintas proses.</p>
                    </div>
                </div>
                <div class="advantage-grid">
                    <article class="advantage-card">
                        <div class="module-icon"><i class="fas fa-project-diagram"></i></div>
                        <h3>Terintegrasi Lintas Proses</h3>
                        <p>Dokumen, rapat, cuti, dan eviden Zona Integritas dapat saling mendukung sehingga proses kerja tidak terpecah dan input tidak berulang.</p>
                    </article>
                    <article class="advantage-card">
                        <div class="module-icon"><i class="fas fa-search"></i></div>
                        <h3>Mudah Ditelusuri</h3>
                        <p>Setiap item memiliki status, preview, approval, dan arsip yang jelas. Ini memudahkan pengendalian, pemeriksaan, dan monitoring tindak lanjut.</p>
                    </article>
                    <article class="advantage-card">
                        <div class="module-icon"><i class="fas fa-shield-alt"></i></div>
                        <h3>Siap Dikembangkan</h3>
                        <p>Struktur aplikasi disusun agar realistis untuk terus dikembangkan, termasuk template baru, integrasi eviden otomatis, dan dashboard yang lebih spesifik per unit.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section">
            <div class="container">
                <div class="cta-band">
                    <div>
                        <h3>Siap masuk ke sistem kerja internal?</h3>
                        <p>Gunakan akun yang sudah dibuat admin untuk mengakses persuratan, rapat, cuti, dan monitoring Zona Integritas.</p>
                    </div>
                    <div class="header-actions">
                        <a href="#modul" class="btn-secondary"><i class="fas fa-layer-group"></i>Lihat Modul</a>
                        <a href="{{ route('login') }}" class="btn-primary"><i class="fas fa-sign-in-alt"></i>Masuk Sekarang</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container site-footer-inner">
            <div>Pengadilan Tinggi Agama Papua Barat</div>
            <div>Sistem Informasi Terpadu &bull; {{ now()->format('Y') }}</div>
        </div>
    </footer>
</body>
</html>

