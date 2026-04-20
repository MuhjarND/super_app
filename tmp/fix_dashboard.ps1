$file = 'c:\xampp\htdocs\super3\resources\views\dashboard.blade.php'
$bytes = [System.IO.File]::ReadAllBytes($file)
$enc = New-Object System.Text.UTF8Encoding($false)
$c = $enc.GetString($bytes)

# 1. Dashboard hero - from dark to light pastel
$c = $c.Replace(
    'background: linear-gradient(135deg, #0f3352 0%, #175d8f 52%, #3b82f6 100%);',
    'background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 40%, #f5f3ff 100%);'
)
$c = $c.Replace(
    "color: #fff;`n            border-radius: 18px;`n            padding: 26px 28px;`n            box-shadow: 0 18px 40px rgba(15, 51, 82, 0.18);",
    "color: #0f172a;`n            border-radius: 18px;`n            padding: 26px 28px;`n            border: 1px solid #e0e7ff;`n            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.06);"
)

# 2. Hero chip - from glass-dark to white cards
$c = $c.Replace(
    'background: rgba(255, 255, 255, 0.14);',
    'background: #ffffff;'
)
$c = $c.Replace(
    "border: 1px solid rgba(255, 255, 255, 0.16);",
    "border: 1px solid #e0e7ff;"
)

# 3. Hero chip icon - change gold to indigo
$c = $c.Replace(
    'color: #facc15;',
    'color: #6366f1;'
)

# 4. Hero meta - from light opacity to dark
$c = $c.Replace(
    "opacity: 0.86;`n            font-size: 0.92rem;",
    "color: #64748b;`n            font-size: 0.92rem;"
)

# 5. Module pill - update persuratan to indigo
$c = $c.Replace(
    '.module-pill.persuratan { background: linear-gradient(135deg, #2563eb, #1d4ed8); }',
    '.module-pill.persuratan { background: linear-gradient(135deg, #6366f1, #4f46e5); }'
)

# 6. Module link - update blue to indigo
$c = $c.Replace('background: #eff6ff;', 'background: #eef2ff;')
$c = $c.Replace('color: #1d4ed8;', 'color: #4f46e5;')

# 7. tone-blue - update to indigo
$c = $c.Replace(
    ".tone-blue { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }",
    ".tone-blue { background: linear-gradient(135deg, #6366f1, #4f46e5); }"
)

# 8. item-link background
$c = $c.Replace('background: #eff6ff;' + "`n" + '            color: #1d4ed8;', 'background: #eef2ff;' + "`n" + '            color: #4f46e5;')

# 9. Calendar today chip
$c = $c.Replace(
    "background: #eff6ff;`n            color: #1d4ed8;",
    "background: #eef2ff;`n            color: #4f46e5;"
)

# 10. Calendar day dot rapat
$c = $c.Replace('.calendar-day-dot.rapat { background: #2563eb; }', '.calendar-day-dot.rapat { background: #4f46e5; }')

# 11. Calendar today border
$c = $c.Replace('border-color: #93c5fd;', 'border-color: #a5b4fc;')
$c = $c.Replace('box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.18);', 'box-shadow: inset 0 0 0 1px rgba(99, 102, 241, 0.18);')

# 12. Module card shadow - lighten
$c = $c.Replace(
    'box-shadow: 0 10px 26px rgba(15, 23, 42, 0.05);',
    'box-shadow: var(--card-shadow);'
)

[System.IO.File]::WriteAllBytes($file, $enc.GetBytes($c))
Write-Host "Done - dashboard updated"
