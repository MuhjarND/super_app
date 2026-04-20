$file = 'c:\xampp\htdocs\super3\resources\views\layouts\app.blade.php'
$bytes = [System.IO.File]::ReadAllBytes($file)
$enc = New-Object System.Text.UTF8Encoding($false)
$c = $enc.GetString($bytes)

# ==============================================
# PART 1: COLOR PALETTE (blue -> indigo-violet)
# ==============================================
$c = $c.Replace('--primary: #1e40af;', '--primary: #4f46e5;')
$c = $c.Replace('--primary-light: #3b82f6;', '--primary-light: #6366f1;')
$c = $c.Replace('--primary-dark: #1e3a5f;', "--primary-dark: #3730a3;`r`n            --primary-50: #eef2ff;`r`n            --primary-100: #e0e7ff;")
$c = $c.Replace('--accent: #f59e0b;' + "`r`n" + '            --accent-light: #fbbf24;', "--accent: #8b5cf6;`r`n            --accent-light: #a78bfa;")
$c = $c.Replace('--sidebar-border: #e5e7eb;', '--sidebar-border: #e8eaed;')
$c = $c.Replace('--body-bg: #f3f4f6;', '--body-bg: #f8fafc;')
$c = $c.Replace('--text-primary: #111827;', '--text-primary: #0f172a;')
$c = $c.Replace('--text-secondary: #6b7280;', '--text-secondary: #64748b;')
$c = $c.Replace('--card-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);', '--card-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);')
$c = $c.Replace('--card-hover-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);', "--card-hover-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);`r`n            --radius: 14px;")

# ==============================================
# PART 2: GLOBAL COLOR REPLACEMENTS
# ==============================================
$c = $c.Replace('background: linear-gradient(135deg, #1e40af, #3b82f6);', 'background: linear-gradient(135deg, #4f46e5, #8b5cf6);')
$c = $c.Replace('background: linear-gradient(135deg, #dbeafe, #bfdbfe);', 'background: linear-gradient(135deg, #eef2ff, #e0e7ff);')
$c = $c.Replace('color: #1e40af;', 'color: #4f46e5;')
$c = $c.Replace('background: #eff6ff !important;', 'background: var(--primary-50) !important;')
$c = $c.Replace('color: #1e40af !important;', 'color: #4f46e5 !important;')
$c = $c.Replace('background: linear-gradient(135deg, #3b82f6, #2563eb);', 'background: linear-gradient(135deg, #6366f1, #4f46e5);')
$c = $c.Replace('background: linear-gradient(135deg, #2563eb, #1d4ed8);', 'background: linear-gradient(135deg, #4f46e5, #4338ca);')
$c = $c.Replace('background: linear-gradient(135deg, #1d4ed8, #2563eb);', 'background: linear-gradient(135deg, #4338ca, #4f46e5);')
$c = $c.Replace('background: linear-gradient(135deg, #6366f1, #4338ca);', 'background: linear-gradient(135deg, #8b5cf6, #6d28d9);')
$c = $c.Replace('box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35);', 'box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25);')
$c = $c.Replace('box-shadow: 0 6px 18px rgba(37, 99, 235, 0.18);', 'box-shadow: 0 2px 8px rgba(79, 70, 229, 0.15);')
$c = $c.Replace('box-shadow: 0 8px 22px rgba(37, 99, 235, 0.28);', 'box-shadow: 0 4px 14px rgba(79, 70, 229, 0.2);')
$c = $c.Replace('box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12);', 'box-shadow: none;')
$c = $c.Replace('box-shadow: 0 6px 18px rgba(15, 23, 42, 0.14);', 'box-shadow: none;')
$c = $c.Replace('box-shadow: 0 6px 18px rgba(37, 99, 235, 0.16);', 'box-shadow: none;')
$c = $c.Replace('box-shadow: 0 6px 18px rgba(5, 150, 105, 0.16);', 'box-shadow: none;')
$c = $c.Replace('box-shadow: 0 6px 18px rgba(220, 38, 38, 0.16);', 'box-shadow: none;')
$c = $c.Replace('box-shadow: 0 6px 18px rgba(8, 145, 178, 0.16);', 'box-shadow: none;')
$c = $c.Replace('box-shadow: 0 6px 18px rgba(217, 119, 6, 0.16);', 'box-shadow: none;')
$c = $c.Replace('box-shadow: 0 10px 22px rgba(15, 23, 42, 0.2);', 'box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);')
$c = $c.Replace('border-color: #3b82f6;', 'border-color: #6366f1;')
$c = $c.Replace('box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);', 'box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);')
$c = $c.Replace('border: 4px solid rgba(59, 130, 246, 0.18);', 'border: 4px solid rgba(99, 102, 241, 0.18);')
$c = $c.Replace('border-top-color: #2563eb;', 'border-top-color: #4f46e5;')
$c = $c.Replace('border: 1px solid #e5e7eb;', 'border: 1px solid #e8eaed;')
$c = $c.Replace('border-radius: 16px;', 'border-radius: var(--radius);')

# ==============================================
# PART 3: MODAL HEADER - white bg
# ==============================================
$c = $c.Replace(
    'background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);' + "`r`n" + '            color: white;' + "`r`n" + '            padding: 20px 24px;' + "`r`n" + '            border: none;',
    "background: #ffffff;`r`n            color: var(--text-primary);`r`n            padding: 20px 24px;`r`n            border-bottom: 1px solid #e8eaed;"
)
$c = $c.Replace('.modal-header .close {' + "`r`n" + '            color: white;', ".modal-header .close {`r`n            color: var(--text-secondary);")

# ==============================================
# PART 4: REMOVE DARK THEME CSS BLOCK
# ==============================================
$darkPattern = '(?s)/\* ======================== DARK THEME ======================== \*/.*?body\.theme-dark \.global-loader-card \{[^}]+\}'
$c = [regex]::Replace($c, $darkPattern, '')

# ==============================================
# PART 5: REMOVE THEME TOGGLE
# ==============================================
$togglePattern = '(?s)<li class="nav-item mr-2 d-flex align-items-center">\s*<button type="button" class="theme-toggle-btn"[^<]*<i[^<]*</i>\s*<span[^<]*</span>\s*</button>\s*</li>'
$c = [regex]::Replace($c, $togglePattern, '')
$toggleCssPattern = '(?s)\.theme-toggle-btn \{[^}]+\}\r?\n\r?\n\s*\.theme-toggle-btn:hover \{[^}]+\}'
$c = [regex]::Replace($c, $toggleCssPattern, '')
$c = [regex]::Replace($c, "(?m)^\s*const savedTheme = localStorage\.getItem\(THEME_KEY\) \|\| 'light';\r?\n\s*applyTheme\(savedTheme\);\r?\n", '')
$c = [regex]::Replace($c, "(?s)\\\$\('#themeToggle'\)\.on\('click', function \(\) \{[^}]+\}\);", '')
$c = [regex]::Replace($c, "(?s)function applyTheme\(theme\) \{.*?if \(label\) \{[^}]+\}\r?\n\s*\}", '')
$c = [regex]::Replace($c, "const THEME_KEY = 'smart-theme';", "")

# ==============================================
# PART 6: SIDEBAR - smaller text, black color, auto-expand
# ==============================================

# Nav header - smaller
$c = $c.Replace(
    ".sidebar .nav-header {`r`n            color: var(--text-muted);`r`n            text-transform: uppercase;`r`n            font-size: 0.61rem;",
    ".sidebar .nav-header {`r`n            color: #94a3b8;`r`n            text-transform: uppercase;`r`n            font-size: 0.56rem;"
)

# Section toggle - smaller, dark color
$c = $c.Replace(
    ".sidebar .nav-section-toggle {`r`n            width: calc(100% - 24px);`r`n            margin: 14px 12px 4px;`r`n            padding: 8px 10px;`r`n            border: 0;`r`n            background: transparent;`r`n            color: var(--text-muted);`r`n            text-transform: uppercase;`r`n            font-size: 0.61rem;",
    ".sidebar .nav-section-toggle {`r`n            width: calc(100% - 24px);`r`n            margin: 10px 12px 3px;`r`n            padding: 6px 10px;`r`n            border: 0;`r`n            background: transparent;`r`n            color: #1e293b;`r`n            text-transform: uppercase;`r`n            font-size: 0.56rem;"
)

# Nav link - smaller, black text
$c = $c.Replace(
    ".sidebar .nav-link {`r`n            color: var(--text-secondary) !important;`r`n            border-radius: 10px;`r`n            margin: 1px 12px;`r`n            padding: 8px 11px !important;`r`n            transition: all 0.15s ease;`r`n            font-size: 0.78rem;",
    ".sidebar .nav-link {`r`n            color: #1e293b !important;`r`n            border-radius: 10px;`r`n            margin: 1px 12px;`r`n            padding: 6px 11px !important;`r`n            transition: all 0.15s ease;`r`n            font-size: 0.72rem;"
)

# Nav icon - smaller
$c = $c.Replace(
    ".sidebar .nav-link .nav-icon {`r`n            width: 20px;`r`n            text-align: center;`r`n            margin-right: 10px;`r`n            font-size: 0.82rem;`r`n            opacity: 0.65;",
    ".sidebar .nav-link .nav-icon {`r`n            width: 18px;`r`n            text-align: center;`r`n            margin-right: 8px;`r`n            font-size: 0.72rem;`r`n            opacity: 0.5;"
)

# Active nav icon
$c = $c.Replace("color: #3b82f6;`n            opacity: 1;", "color: #4f46e5;`n            opacity: 1;")

# Sub-nav - smaller
$c = $c.Replace(
    ".sidebar .nav-item-sub .nav-link {`r`n            margin-left: 24px;`r`n            padding: 7px 10px !important;`r`n            font-size: 0.75rem;",
    ".sidebar .nav-item-sub .nav-link {`r`n            margin-left: 24px;`r`n            padding: 5px 10px !important;`r`n            font-size: 0.68rem;"
)
$c = $c.Replace(
    ".sidebar .nav-item-sub .nav-link .nav-icon {`r`n            width: 18px;`r`n            margin-right: 8px;`r`n            font-size: 0.76rem;",
    ".sidebar .nav-item-sub .nav-link .nav-icon {`r`n            width: 16px;`r`n            margin-right: 7px;`r`n            font-size: 0.68rem;"
)

# Section chevron smaller
$c = $c.Replace(
    ".sidebar .nav-section-toggle .section-chevron {`r`n            font-size: 0.7rem;",
    ".sidebar .nav-section-toggle .section-chevron {`r`n            font-size: 0.6rem;"
)

# Sidebar user name - smaller
$c = $c.Replace('font-size: 0.88rem;', 'font-size: 0.82rem;')

# ==============================================
# PART 7: REMOVE is-collapsed from HTML sections
# ==============================================
$c = [regex]::Replace($c, "\{\{ request\(\)->routeIs\([^}]+\? '' : 'is-collapsed' \}\}", '')

# ==============================================
# PART 8: MODERNIZE SIDEBAR VISUALS
# ==============================================
# Logo mark glow
$c = $c.Replace(
    "background: linear-gradient(135deg, #4f46e5, #8b5cf6);`r`n            border-radius: 10px;",
    "background: linear-gradient(135deg, #4f46e5, #7c3aed);`r`n            border-radius: 12px;`r`n            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);"
)

# User avatar - solid gradient
$c = $c.Replace(
    "background: linear-gradient(135deg, #eef2ff, #e0e7ff);`r`n            display: flex;`r`n            align-items: center;`r`n            justify-content: center;`r`n            color: #4f46e5;",
    "background: linear-gradient(135deg, #4f46e5, #7c3aed);`r`n            display: flex;`r`n            align-items: center;`r`n            justify-content: center;`r`n            color: #ffffff;"
)

# Nav hover - subtle indigo
$c = $c.Replace(
    ".sidebar .nav-link:hover {`r`n            background: #f3f4f6 !important;`r`n            color: var(--text-primary) !important;",
    ".sidebar .nav-link:hover {`r`n            background: #f5f3ff !important;`r`n            color: #4f46e5 !important;"
)

# Active nav - with left border
$c = $c.Replace(
    ".sidebar .nav-link.active {`r`n            background: var(--primary-50) !important;`r`n            color: #4f46e5 !important;`r`n            font-weight: 600;`r`n            box-shadow: none;",
    ".sidebar .nav-link.active {`r`n            background: linear-gradient(135deg, #eef2ff, #e8e5ff) !important;`r`n            color: #4f46e5 !important;`r`n            font-weight: 700;`r`n            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.08);`r`n            border-left: 3px solid #4f46e5;"
)

# ==============================================
# PART 9: MODERNIZE CARDS
# ==============================================
$c = $c.Replace(
    ".card {`r`n            border: 1px solid #e8eaed;`r`n            border-radius: var(--radius);`r`n            box-shadow: none;`r`n            transition: all 0.2s ease;`r`n            background: white;`r`n        }",
    ".card {`r`n            border: 1px solid #e8eaef;`r`n            border-radius: var(--radius);`r`n            box-shadow: 0 1px 3px rgba(0,0,0,0.03);`r`n            transition: all 0.25s cubic-bezier(0.4,0,0.2,1);`r`n            background: linear-gradient(180deg, #ffffff 0%, #fdfcff 100%);`r`n        }`r`n`r`n        .card:hover {`r`n            box-shadow: 0 4px 16px rgba(99,102,241,0.06);`r`n        }"
)

# Modal title - indigo color
$c = $c.Replace(
    ".modal-header .modal-title {`r`n            font-weight: 700;`r`n            font-size: 1rem;`r`n        }",
    ".modal-header .modal-title {`r`n            font-weight: 700;`r`n            font-size: 1.05rem;`r`n            color: var(--primary);`r`n        }"
)

# ==============================================
# PART 10: CLEAN UP TEXT
# ==============================================
# Remove sidebar role subtitle
$c = $c.Replace(
    "<div class=""sidebar-user-name"">{{ Auth::user()->name }}</div>`r`n                                <div class=""sidebar-user-role"">{{ Auth::user()->role_label ?? 'Pegawai' }}</div>",
    "<div class=""sidebar-user-name"">{{ Auth::user()->name }}</div>"
)

# Simplify footer
$c = $c.Replace(
    "Hak Cipta &copy; <strong>PTA Papua Barat</strong> - {{ date('Y') }}",
    "&copy; PTA Papua Barat {{ date('Y') }}"
)

# ==============================================
# PART 11: CUSTOM SCROLLBAR
# ==============================================
$scrollbar = @"

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #c7d2fe; border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: #a5b4fc; }

"@
$c = $c.Replace("        /* ======================== SIDEBAR ======================== */", "$scrollbar`r`n        /* ======================== SIDEBAR ======================== */")

# ==============================================
# PART 12: NOTIF PULSE ANIMATION
# ==============================================
$c = $c.Replace(
    "box-shadow: 0 6px 18px rgba(239, 68, 68, 0.28);",
    "box-shadow: 0 3px 10px rgba(239, 68, 68, 0.35);"
)

# Write
[System.IO.File]::WriteAllBytes($file, $enc.GetBytes($c))
Write-Host "Done - ALL layout changes applied ($([Math]::Round(($bytes.Length - $enc.GetBytes($c).Length)/1024, 1))KB reduced)"
