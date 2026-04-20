# Read file as raw bytes to preserve encoding
$file = 'c:\xampp\htdocs\super3\resources\views\layouts\app.blade.php'
$bytes = [System.IO.File]::ReadAllBytes($file)
$enc = New-Object System.Text.UTF8Encoding($false)
$c = $enc.GetString($bytes)

# 1. CSS Variable updates
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

# 2. Sidebar logo/avatar
$c = $c.Replace('background: linear-gradient(135deg, #1e40af, #3b82f6);', 'background: linear-gradient(135deg, #4f46e5, #8b5cf6);')
$c = $c.Replace('background: linear-gradient(135deg, #dbeafe, #bfdbfe);', 'background: linear-gradient(135deg, #eef2ff, #e0e7ff);')
$c = $c.Replace('color: #1e40af;', 'color: #4f46e5;')

# 3. Active nav
$c = $c.Replace('background: #eff6ff !important;', 'background: var(--primary-50) !important;')
$c = $c.Replace('color: #1e40af !important;', 'color: #4f46e5 !important;')
$c = $c.Replace('color: #3b82f6;' + "`n" + '            opacity: 1;', "color: #6366f1;`n            opacity: 1;")

# 4. Cards - border-radius
$c = $c.Replace('border-radius: 16px;', 'border-radius: var(--radius);')
$c = $c.Replace('border: 1px solid #e5e7eb;', 'border: 1px solid #e8eaed;')

# 5. Badges - remove shadow
$c = $c.Replace('box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12);', 'box-shadow: none;')

# 6. Primary badge
$c = $c.Replace('background: linear-gradient(135deg, #3b82f6, #2563eb);', 'background: linear-gradient(135deg, #6366f1, #4f46e5);')

# 7. Buttons primary
$c = $c.Replace('box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35);', 'box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25);')

# 8. Create/add buttons
$c = $c.Replace('box-shadow: 0 6px 18px rgba(37, 99, 235, 0.18);', 'box-shadow: 0 2px 8px rgba(79, 70, 229, 0.15);')
$c = $c.Replace('background: linear-gradient(135deg, #2563eb, #1d4ed8);', 'background: linear-gradient(135deg, #4f46e5, #4338ca);')
$c = $c.Replace('box-shadow: 0 8px 22px rgba(37, 99, 235, 0.28);', 'box-shadow: 0 4px 14px rgba(79, 70, 229, 0.2);')
$c = $c.Replace('background: linear-gradient(135deg, #1d4ed8, #2563eb);', 'background: linear-gradient(135deg, #4338ca, #4f46e5);')

# 9. Outline buttons - remove shadows
$c = $c.Replace('box-shadow: 0 6px 18px rgba(15, 23, 42, 0.14);', 'box-shadow: none;')
$c = $c.Replace('box-shadow: 0 6px 18px rgba(37, 99, 235, 0.16);', 'box-shadow: none;')
$c = $c.Replace('box-shadow: 0 6px 18px rgba(5, 150, 105, 0.16);', 'box-shadow: none;')
$c = $c.Replace('box-shadow: 0 6px 18px rgba(220, 38, 38, 0.16);', 'box-shadow: none;')
$c = $c.Replace('box-shadow: 0 6px 18px rgba(8, 145, 178, 0.16);', 'box-shadow: none;')
$c = $c.Replace('box-shadow: 0 6px 18px rgba(217, 119, 6, 0.16);', 'box-shadow: none;')
$c = $c.Replace('box-shadow: 0 10px 22px rgba(15, 23, 42, 0.2);', 'box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);')

# 10. Edit icon button
$c = $c.Replace('background: linear-gradient(135deg, #6366f1, #4338ca);', 'background: linear-gradient(135deg, #8b5cf6, #6d28d9);')

# 11. Modal header - white with indigo title
$c = $c.Replace('background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);' + "`r`n" + '            color: white;' + "`r`n" + '            padding: 20px 24px;' + "`r`n" + '            border: none;', "background: #ffffff;`r`n            color: var(--text-primary);`r`n            padding: 20px 24px;`r`n            border-bottom: 1px solid #e8eaed;")
$c = $c.Replace('.modal-header .close {' + "`r`n" + '            color: white;', ".modal-header .close {`r`n            color: var(--text-secondary);")

# 12. Form focus
$c = $c.Replace('border-color: #3b82f6;', 'border-color: #6366f1;')
$c = $c.Replace('box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);', 'box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.12);')

# 13. Loader spinner
$c = $c.Replace('border: 4px solid rgba(59, 130, 246, 0.18);', 'border: 4px solid rgba(99, 102, 241, 0.18);')
$c = $c.Replace('border-top-color: #2563eb;', 'border-top-color: #4f46e5;')

# 14. Remove dark theme CSS block
$darkPattern = '(?s)/\* ======================== DARK THEME ======================== \*/.*?body\.theme-dark \.global-loader-card \{[^}]+\}'
$c = [regex]::Replace($c, $darkPattern, '')

# 15. Remove theme toggle button HTML
$togglePattern = '(?s)<li class="nav-item mr-2 d-flex align-items-center">\s*<button type="button" class="theme-toggle-btn"[^<]*<i[^<]*</i>\s*<span[^<]*</span>\s*</button>\s*</li>'
$c = [regex]::Replace($c, $togglePattern, '')

# 16. Remove theme toggle CSS
$toggleCssPattern = '(?s)\.theme-toggle-btn \{[^}]+\}\r?\n\r?\n\s*\.theme-toggle-btn:hover \{[^}]+\}'
$c = [regex]::Replace($c, $toggleCssPattern, '')

# 17. Remove theme JS
$c = [regex]::Replace($c, "(?m)^\s*const savedTheme = localStorage\.getItem\(THEME_KEY\) \|\| 'light';\r?\n\s*applyTheme\(savedTheme\);\r?\n", '')
$c = [regex]::Replace($c, "(?s)\\\$\('#themeToggle'\)\.on\('click', function \(\) \{[^}]+\}\);", '')
$c = [regex]::Replace($c, "(?s)function applyTheme\(theme\) \{.*?if \(label\) \{[^}]+\}\r?\n\s*\}", '')
$c = [regex]::Replace($c, "const THEME_KEY = 'smart-theme';", "")

# 18. Notification toggle - update hover
$c = $c.Replace('border-color: #cbd5e1;', 'border-color: #c7d2fe;')
$c = $c.Replace('color: #0f172a !important;', 'color: #4f46e5 !important;')

# 19. Mobile table card - subtle border
$c = $c.Replace('border: 1px solid #dbe3f0;', 'border: 1px solid #e0e7ff;')
$c = $c.Replace('box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);', 'box-shadow: var(--card-shadow);')

# 20. Dark theme in modal header for dynamic (already handled)
$c = $c.Replace('background: linear-gradient(135deg, #0f3a7c 0%, #1d4ed8 100%);', '')

# Write back
[System.IO.File]::WriteAllBytes($file, $enc.GetBytes($c))
Write-Host "Done - all layout changes applied"
