$file = 'c:\xampp\htdocs\super3\resources\views\layouts\app.blade.php'
$bytes = [System.IO.File]::ReadAllBytes($file)
$enc = New-Object System.Text.UTF8Encoding($false)
$c = $enc.GetString($bytes)

# ==========================================
# 1. SIDEBAR - Make it feel premium glass
# ==========================================

# Sidebar background - subtle gradient + glass effect
$old = @"
        .main-sidebar {
            background: var(--sidebar-bg) !important;
            border-right: 1px solid var(--sidebar-border);
            box-shadow: none !important;
            width: var(--sidebar-width) !important;
        }
"@
$new = @"
        .main-sidebar {
            background: linear-gradient(180deg, #ffffff 0%, #fafbff 100%) !important;
            border-right: 1px solid var(--sidebar-border);
            box-shadow: 4px 0 24px rgba(99, 102, 241, 0.03) !important;
            width: var(--sidebar-width) !important;
        }
"@
$c = $c.Replace($old, $new)

# Logo mark - larger with glow effect
$old = @"
        .logo-mark {
            width: 38px;
            height: 38px;
            background: linear-gradient(135deg, #4f46e5, #8b5cf6);
            border-radius: 10px;
"@
$new = @"
        .logo-mark {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);
"@
$c = $c.Replace($old, $new)

# User avatar - gradient ring
$old = @"
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4f46e5;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
"@
$new = @"
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.9rem;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.2);
"@
$c = $c.Replace($old, $new)

# Section toggle - colored accent line
$old = @"
        .sidebar .nav-section-toggle {
            width: calc(100% - 24px);
            margin: 14px 12px 4px;
            padding: 8px 10px;
            border: 0;
            background: transparent;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 0.61rem;
            letter-spacing: 1.2px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 10px;
            transition: all 0.15s ease;
        }
"@
$new = @"
        .sidebar .nav-section-toggle {
            width: calc(100% - 24px);
            margin: 14px 12px 4px;
            padding: 9px 12px;
            border: 0;
            background: linear-gradient(135deg, #f8fafc, #eef2ff);
            color: var(--primary);
            text-transform: uppercase;
            font-size: 0.62rem;
            letter-spacing: 1.4px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-radius: 10px;
            border-left: 3px solid var(--primary);
            transition: all 0.18s ease;
        }
"@
$c = $c.Replace($old, $new)

# Section toggle hover
$c = $c.Replace(
    ".sidebar .nav-section-toggle:hover {`r`n            background: #f8fafc;`r`n            color: #64748b;",
    ".sidebar .nav-section-toggle:hover {`r`n            background: linear-gradient(135deg, #eef2ff, #e0e7ff);`r`n            color: var(--primary-dark);"
)

# Nav link - smoother hover and better active state
$old = @"
        .sidebar .nav-link {
            color: var(--text-secondary) !important;
            border-radius: 10px;
            margin: 1px 12px;
            padding: 8px 11px !important;
            transition: all 0.15s ease;
            font-size: 0.78rem;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
"@
$new = @"
        .sidebar .nav-link {
            color: var(--text-secondary) !important;
            border-radius: 10px;
            margin: 2px 12px;
            padding: 9px 12px !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            position: relative;
        }
"@
$c = $c.Replace($old, $new)

# Active nav - indigo gradient bg with left strip
$old = @"
        .sidebar .nav-link.active {
            background: var(--primary-50) !important;
            color: #4f46e5 !important;
            font-weight: 600;
            box-shadow: none;
        }
"@
$new = @"
        .sidebar .nav-link.active {
            background: linear-gradient(135deg, #eef2ff, #e8e5ff) !important;
            color: #4f46e5 !important;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.08);
            border-left: 3px solid #4f46e5;
        }
"@
$c = $c.Replace($old, $new)

# Active nav icon color
$c = $c.Replace(
    ".sidebar .nav-link.active .nav-icon {`n            color: #3b82f6;",
    ".sidebar .nav-link.active .nav-icon {`n            color: #6366f1;"
)

# Nav hover
$c = $c.Replace(
    ".sidebar .nav-link:hover {`r`n            background: #f3f4f6 !important;`r`n            color: var(--text-primary) !important;",
    ".sidebar .nav-link:hover {`r`n            background: #f5f3ff !important;`r`n            color: #4f46e5 !important;"
)

# ==========================================
# 2. TOPBAR - Subtle gradient, better avatar
# ==========================================
$old = @"
        .main-header {
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            box-shadow: none;
            min-height: 56px;
        }
"@
$new = @"
        .main-header {
            background: linear-gradient(90deg, #ffffff 0%, #fdfcff 100%);
            border-bottom: 1px solid #e8eaef;
            box-shadow: 0 1px 4px rgba(99, 102, 241, 0.03);
            min-height: 56px;
        }
"@
$c = $c.Replace($old, $new)

# Topbar avatar - bigger with ring
$old = @"
        .topbar-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, #4f46e5, #8b5cf6);
"@
$new = @"
        .topbar-avatar {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            box-shadow: 0 3px 10px rgba(79, 70, 229, 0.25);
"@
$c = $c.Replace($old, $new)

# Dropdown hover - indigo tint
$c = $c.Replace(
    ".main-header .navbar-nav .nav-item .dropdown-toggle:hover {`r`n            background: #f3f4f6;",
    ".main-header .navbar-nav .nav-item .dropdown-toggle:hover {`r`n            background: #f5f3ff;"
)

# ==========================================
# 3. CARDS - Add subtle gradient, hover lift
# ==========================================
$old = @"
        .card {
            border: 1px solid #e8eaed;
            border-radius: var(--radius);
            box-shadow: var(--card-shadow);
            transition: all 0.2s ease;
            background: white;
        }
"@
$new = @"
        .card {
            border: 1px solid #e8eaef;
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03), 0 1px 2px rgba(99, 102, 241, 0.02);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(180deg, #ffffff 0%, #fdfcff 100%);
        }

        .card:hover {
            box-shadow: 0 4px 16px rgba(99, 102, 241, 0.06), 0 1px 3px rgba(0, 0, 0, 0.04);
        }
"@
$c = $c.Replace($old, $new)

# ==========================================
# 4. CONTENT HEADER - Bigger, bolder
# ==========================================
$c = $c.Replace(
    "font-size: 1.4rem;`r`n            letter-spacing: -0.02em;",
    "font-size: 1.5rem;`r`n            letter-spacing: -0.03em;"
)

# ==========================================
# 5. FOOTER - Subtle gradient
# ==========================================
$c = $c.Replace(
    ".main-footer {`r`n            background: white;`r`n            border-top: 1px solid var(--sidebar-border);",
    ".main-footer {`r`n            background: linear-gradient(90deg, #fdfcff, #f8fafc);`r`n            border-top: 1px solid #e8eaef;"
)

# ==========================================
# 6. NOTIFICATION TOGGLE - Indigo tint
# ==========================================
$c = $c.Replace(
    "background: #ef4444;`r`n            color: #fff;`r`n            font-size: 0.68rem;`r`n            font-weight: 800;`r`n            line-height: 1;`r`n            box-shadow: 0 6px 18px rgba(239, 68, 68, 0.28);",
    "background: linear-gradient(135deg, #ef4444, #dc2626);`r`n            color: #fff;`r`n            font-size: 0.68rem;`r`n            font-weight: 800;`r`n            line-height: 1;`r`n            box-shadow: 0 3px 10px rgba(239, 68, 68, 0.35);`r`n            animation: notifPulse 2s ease-in-out infinite;"
)

# Add notification pulse animation after the @keyframes globalLoaderSpin
$c = $c.Replace(
    "@keyframes globalLoaderSpin {`r`n            to { transform: rotate(360deg); }`r`n        }",
    "@keyframes globalLoaderSpin {`r`n            to { transform: rotate(360deg); }`r`n        }`r`n`r`n        @keyframes notifPulse {`r`n            0%, 100% { transform: scale(1); }`r`n            50% { transform: scale(1.1); }`r`n        }"
)

# ==========================================
# 7. SCROLLBAR - Indigo themed
# ==========================================
$scrollbar = @"

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #c7d2fe; border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: #a5b4fc; }
"@
$c = $c.Replace("        /* ======================== SIDEBAR ======================== */", "$scrollbar`r`n`r`n        /* ======================== SIDEBAR ======================== */")

# ==========================================
# 8. PAGE EMOJI - Better styling
# ==========================================
$c = $c.Replace(
    "background: #f3f4f6;`r`n            display: inline-flex;`r`n            align-items: center;`r`n            justify-content: center;`r`n            border-radius: 10px;`r`n            margin-right: 8px;",
    "background: linear-gradient(135deg, #eef2ff, #e8e5ff);`r`n            display: inline-flex;`r`n            align-items: center;`r`n            justify-content: center;`r`n            border-radius: 12px;`r`n            margin-right: 10px;`r`n            box-shadow: 0 2px 6px rgba(79, 70, 229, 0.06);"
)

# ==========================================
# 9. TABLE - Better styling
# ==========================================
$c = $c.Replace(
    ".table thead th {`r`n            font-weight: 700;",
    ".table thead th {`r`n            font-weight: 800;"
)

# ==========================================
# 10. SELECT2 - Indigo focus
# ==========================================
$c = $c.Replace(
    "border-color: var(--primary-light);`r`n            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);",
    "border-color: #6366f1;`r`n            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);"
)

# Write back
[System.IO.File]::WriteAllBytes($file, $enc.GetBytes($c))
Write-Host "Done - layout modernized"
