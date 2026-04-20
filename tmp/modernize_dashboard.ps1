$file = 'c:\xampp\htdocs\super3\resources\views\dashboard.blade.php'
$bytes = [System.IO.File]::ReadAllBytes($file)
$enc = New-Object System.Text.UTF8Encoding($false)
$c = $enc.GetString($bytes)

# ==========================================
# 1. DASHBOARD HERO - Bold visual impact
# ==========================================
$old = @"
        .dashboard-hero {
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 40%, #f5f3ff 100%);
            color: #0f172a;
            border-radius: 18px;
            padding: 26px 28px;
            border: 1px solid #e0e7ff;
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.06);
        }
"@
$new = @"
        .dashboard-hero {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 40%, #7c3aed 100%);
            color: #ffffff;
            border-radius: 20px;
            padding: 30px 32px;
            border: none;
            box-shadow: 0 8px 32px rgba(79, 70, 229, 0.2);
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
"@
$c = $c.Replace($old, $new)

# Hero meta - lighter for dark bg
$c = $c.Replace(
    ".dashboard-hero-meta {`n            color: #64748b;`n            font-size: 0.92rem;",
    ".dashboard-hero-meta {`n            color: rgba(255,255,255,0.75);`n            font-size: 0.92rem;"
)

# Hero chips - glassmorphism style on dark bg
$old = @"
        .hero-chip {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            border: 1px solid #e0e7ff;
            border-radius: 999px;
            padding: 9px 14px;
            min-height: 44px;
        }

        .hero-chip i {
            color: #6366f1;
        }
"@
$new = @"
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
"@
$c = $c.Replace($old, $new)

# Hero chip text colors
$c = $c.Replace(
    ".hero-chip span {`n            font-size: 0.74rem;`n            opacity: 0.82;",
    ".hero-chip span {`n            font-size: 0.74rem;`n            color: rgba(255,255,255,0.8);"
)

# ==========================================
# 2. MODULE CARDS - Hover lift + glow
# ==========================================
$old = @"
        .module-card {
            background: #fff;
            border: 1px solid #e8eaed;
            border-radius: 18px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            display: grid;
            gap: 16px;
        }
"@
$new = @"
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
"@
$c = $c.Replace($old, $new)

# Module pill - larger with shadow
$old = @"
        .module-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 14px;
            font-size: 1.1rem;
            color: #fff;
            flex-shrink: 0;
        }
"@
$new = @"
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
"@
$c = $c.Replace($old, $new)

# Metric box - subtle left accent color
$old = @"
        .metric-box {
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 12px 13px;
            min-height: 76px;
        }
"@
$new = @"
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
"@
$c = $c.Replace($old, $new)

# Module link - hover effect
$old = @"
        .module-link-row a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border-radius: 10px;
            background: #eef2ff;
            color: #4f46e5;
            padding: 9px 12px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
        }
"@
$new = @"
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
"@
$c = $c.Replace($old, $new)

# ==========================================
# 3. DASH PANEL - Better heads and body
# ==========================================
$old = @"
        .dash-panel {
            background: #fff;
            border: 1px solid #e8eaed;
            border-radius: 18px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
"@
$new = @"
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
"@
$c = $c.Replace($old, $new)

# Panel head - subtle gradient
$c = $c.Replace(
    ".dash-panel-head {`n            display: flex;`n            align-items: center;`n            justify-content: space-between;`n            gap: 12px;`n            padding: 18px 20px 14px;`n            border-bottom: 1px solid #eef2f7;",
    ".dash-panel-head {`n            display: flex;`n            align-items: center;`n            justify-content: space-between;`n            gap: 12px;`n            padding: 20px 22px 14px;`n            border-bottom: 1px solid #eef2f7;`n            background: linear-gradient(135deg, #fdfcff, #f8fafc);"
)

# ==========================================
# 4. ACTION ITEMS - Better icon design
# ==========================================
$old = @"
        .action-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            color: #fff;
            margin-top: 2px;
        }
"@
$new = @"
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
"@
$c = $c.Replace($old, $new)

# Item link - hover effect
$old = @"
        .item-link {
            align-self: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #eef2ff;
            color: #4f46e5;
            text-decoration: none;
            flex-shrink: 0;
        }
"@
$new = @"
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
"@
$c = $c.Replace($old, $new)

# ==========================================
# 5. CALENDAR STAT CARDS - Better visual
# ==========================================
$old = @"
        .calendar-stat-card {
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            padding: 14px 15px;
            min-height: 84px;
        }
"@
$new = @"
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
"@
$c = $c.Replace($old, $new)

# Calendar stat value - indigo color
$c = $c.Replace(
    ".calendar-stat-value {`n            font-size: 1.4rem;`n            font-weight: 800;`n            color: #0f172a;",
    ".calendar-stat-value {`n            font-size: 1.5rem;`n            font-weight: 800;`n            color: #4f46e5;"
)

# ==========================================
# 6. CALENDAR CELLS - Better today highlight
# ==========================================
$c = $c.Replace(
    ".calendar-day-cell.today {`n            border-color: #a5b4fc;`n            box-shadow: inset 0 0 0 1px rgba(99, 102, 241, 0.18);",
    ".calendar-day-cell.today {`n            border-color: #818cf8;`n            box-shadow: inset 0 0 0 2px rgba(99, 102, 241, 0.2), 0 2px 8px rgba(99, 102, 241, 0.08);`n            background: linear-gradient(135deg, #fdfcff, #eef2ff);"
)

# Mobile hero - keep dark gradient
$c = $c.Replace(
    ".dashboard-hero {`n                padding: 18px 16px;`n                border-radius: 14px;",
    ".dashboard-hero {`n                padding: 20px 18px;`n                border-radius: 16px;"
)

# Write back
[System.IO.File]::WriteAllBytes($file, $enc.GetBytes($c))
Write-Host "Done - dashboard modernized"
