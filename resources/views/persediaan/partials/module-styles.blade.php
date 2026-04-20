<style>
    .inventory-module-hero {
        padding: 8px 2px 12px;
    }

    .inventory-module-title {
        font-size: 1.55rem;
        font-weight: 700;
        line-height: 1.15;
        color: #0f172a;
    }

    .inventory-module-subtitle {
        font-size: 0.98rem;
        color: #64748b;
    }

    .inventory-module-shell {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.10), rgba(245, 158, 11, 0.10));
        border-radius: 24px;
        padding: 1px;
        margin-top: 2px;
    }

    .inventory-module-board {
        background: #fff;
        border-radius: 23px;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, 0.16);
        box-shadow: 0 20px 48px rgba(15, 23, 42, 0.07);
    }

    .inventory-module-board-header {
        padding: 18px 22px;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
    }

    .inventory-module-board-title {
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
    }

    .inventory-module-board-subtitle {
        color: #64748b;
        font-size: 0.95rem;
    }

    .inventory-module-board-count {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
    }

    .inventory-module-board-body {
        padding: 20px;
    }

    .inventory-module-panel {
        border: 1px solid rgba(191, 219, 254, 0.85);
        border-radius: 20px;
        background: #fff;
        overflow: hidden;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.04);
    }

    .inventory-module-panel-header {
        padding: 18px 20px;
        border-bottom: 1px solid rgba(226, 232, 240, 0.95);
    }

    .inventory-module-panel-body {
        padding: 20px;
    }

    .inventory-module-panel-title {
        font-size: 0.98rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 4px;
    }

    .inventory-module-panel-subtitle {
        color: #64748b;
        font-size: 0.92rem;
        margin: 0;
    }

    .inventory-module-stat {
        border: 1px solid rgba(191, 219, 254, 0.85);
        border-radius: 20px;
        padding: 18px;
        height: 100%;
        background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,250,252,0.92));
    }

    .inventory-module-stat-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        font-weight: 700;
    }

    .inventory-module-stat-value {
        font-size: 1.8rem;
        font-weight: 800;
        color: #163567;
        line-height: 1.1;
        margin-top: 8px;
    }

    .inventory-module-stat-note {
        margin-top: 8px;
        color: #64748b;
        font-size: 0.92rem;
    }

    .inventory-module-table thead th {
        background: #edf4ff;
        color: #21427e;
        font-size: 0.88rem;
        font-weight: 700;
        border-bottom: 1px solid rgba(191, 219, 254, 0.82);
    }

    .inventory-module-table td,
    .inventory-module-table th {
        padding: 13px 16px;
        vertical-align: middle;
        border-color: rgba(226, 232, 240, 0.9);
        font-size: 0.95rem;
    }

    .inventory-module-muted {
        color: #64748b;
        font-size: 0.92rem;
        line-height: 1.45;
    }

    .inventory-module-chip {
        display: inline-flex;
        align-items: center;
        padding: 8px 14px;
        border-radius: 999px;
        background: #eef4ff;
        color: #47658f;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .inventory-module-empty {
        border: 1px dashed rgba(148, 163, 184, 0.45);
        border-radius: 14px;
        padding: 18px;
        color: #64748b;
        background: rgba(248, 250, 252, 0.85);
        text-align: center;
    }
</style>
