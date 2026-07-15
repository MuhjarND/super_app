<style>
    .library-module {
        --library-primary: #5b4cf0;
        --library-primary-soft: #eeecff;
        --library-border: #e5e7eb;
        --library-text: #172033;
        --library-muted: #64748b;
    }

    .library-module .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 18px;
        flex-wrap: wrap;
    }

    .library-module .page-header h1 { margin: 0; color: var(--library-text); font-size: 1.25rem; font-weight: 700; }
    .library-module .page-header p { margin: 3px 0 0; color: var(--library-muted); font-size: .84rem; }
    .library-module .card { border: 1px solid var(--library-border); border-radius: 16px; box-shadow: 0 8px 24px rgba(15,23,42,.045); }
    .library-module .card-header { padding: 14px 17px; background: #fff; border-bottom: 1px solid #eef2f7; font-size: .88rem; font-weight: 700; }
    .library-module .stat-card { height: 100%; padding: 17px; border-radius: 16px; color: #fff; box-shadow: 0 10px 25px rgba(15,23,42,.09); }
    .library-module .stat-card-primary { background: linear-gradient(135deg,#5b4cf0,#7568f5); }
    .library-module .stat-card-info { background: linear-gradient(135deg,#0284c7,#0ea5e9); }
    .library-module .stat-card-success { background: linear-gradient(135deg,#059669,#10b981); }
    .library-module .stat-card-warning { background: linear-gradient(135deg,#d97706,#f59e0b); }
    .library-module .stat-card-danger { background: linear-gradient(135deg,#dc2626,#ef4444); }
    .library-module .stat-card-dark { background: linear-gradient(135deg,#1e293b,#475569); }
    .library-module .stat-icon { display:flex; align-items:center; justify-content:center; width:44px; height:44px; border-radius:12px; background:rgba(255,255,255,.15); font-size:1.15rem; }
    .library-module .stat-value { margin-top:10px; font-size:1.55rem; line-height:1; font-weight:800; }
    .library-module .stat-label { margin-top:5px; font-size:.78rem; opacity:.84; }
    .library-module .table-modern thead th { padding:11px 14px; background:#f8fafc; color:#64748b; font-size:.7rem; letter-spacing:.04em; text-transform:uppercase; white-space:nowrap; }
    .library-module .table-modern tbody td { padding:11px 14px; vertical-align:middle; border-color:#eef2f7; font-size:.82rem; }
    .library-module .badge-status { border-radius:20px; padding:5px 9px; font-size:.7rem; }
    .library-module .btn { border-radius:9px; font-size:.8rem; font-weight:600; }
    .library-module .btn-primary { background:var(--library-primary); border-color:var(--library-primary); }
    .library-module .btn-icon { display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px; padding:0; }
    .library-module .form-control,
    .library-module .form-select { min-height:40px; border:1px solid #dbe1ea; border-radius:10px; font-size:.84rem; }
    .library-module .form-label { color:#475569; font-size:.8rem; font-weight:700; }
    .library-module .empty-state { padding:38px 18px; text-align:center; color:#94a3b8; }
    .library-module .avatar-text-sm { display:flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:50%; font-size:.72rem; font-weight:700; }
    .library-module .gap-1 { gap:.25rem!important; } .library-module .gap-2 { gap:.5rem!important; } .library-module .gap-3 { gap:1rem!important; }
    .library-module .me-1 { margin-right:.25rem!important; } .library-module .me-2 { margin-right:.5rem!important; } .library-module .me-3 { margin-right:1rem!important; }
    .library-module .ms-1 { margin-left:.25rem!important; } .library-module .ms-2 { margin-left:.5rem!important; } .library-module .ms-3 { margin-left:1rem!important; }
    .library-module .fw-600 { font-weight:600!important; } .library-module .fw-700 { font-weight:700!important; }
    .library-module .g-2 { margin:-.25rem; } .library-module .g-2 > * { padding:.25rem; }
    .library-module .g-3 { margin:-.5rem; } .library-module .g-3 > * { padding:.5rem; }

    @media (max-width: 767.98px) {
        .library-module .page-header { align-items:flex-start; }
        .library-module .page-header .btn { min-height:38px; }
        .library-module .card { border-radius:14px; }
        .library-module .table-responsive { overflow-x:auto!important; -webkit-overflow-scrolling:touch; }
        .library-module .table-modern { min-width:720px; }
        .library-module .stat-card { padding:14px; }
        .library-module .stat-value { font-size:1.3rem; }
    }
</style>
