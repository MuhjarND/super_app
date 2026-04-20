$file = 'c:\xampp\htdocs\super3\resources\views\auth\login.blade.php'
$bytes = [System.IO.File]::ReadAllBytes($file)
$enc = New-Object System.Text.UTF8Encoding($false)
$c = $enc.GetString($bytes)

# 1. CSS variables - from navy to light
$c = $c.Replace('--navy-950: #091829;', '--navy-950: #f8fafc;')
$c = $c.Replace('--navy-900: #0f2640;', '--navy-900: #4f46e5;')
$c = $c.Replace('--navy-800: #163654;', '--navy-800: #6366f1;')
$c = $c.Replace('--blue-500: #2c6bed;', '--blue-500: #4f46e5;')
$c = $c.Replace('--gold-500: #d9a441;', '--gold-500: #8b5cf6;')

# 2. Body - light background
$c = $c.Replace(
    'color: #ffffff;',
    'color: #0f172a;'
)
$c = $c.Replace(
    "radial-gradient(circle at top left, rgba(217, 164, 65, 0.14), transparent 26%),`n                radial-gradient(circle at top right, rgba(44, 107, 237, 0.14), transparent 28%),`n                linear-gradient(180deg, var(--navy-950) 0%, var(--navy-900) 48%, var(--navy-800) 100%);",
    "radial-gradient(circle at top left, rgba(139, 92, 246, 0.08), transparent 30%),`n                radial-gradient(circle at bottom right, rgba(99, 102, 241, 0.08), transparent 30%),`n                linear-gradient(180deg, #f8fafc 0%, #eef2ff 50%, #f5f3ff 100%);"
)

# 3. Login loader overlay
$c = $c.Replace('background: rgba(6, 17, 29, 0.48);', 'background: rgba(255, 255, 255, 0.6);')

# 4. Login loader spinner
$c = $c.Replace("border: 4px solid rgba(44, 107, 237, 0.16);`n            border-top-color: var(--navy-900);", "border: 4px solid rgba(79, 70, 229, 0.16);`n            border-top-color: #4f46e5;")

# 5. Brand title - dark text on light bg
$c = $c.Replace("color: rgba(255,255,255,0.72);`n            font-size: 1rem;", "color: #64748b;`n            font-size: 1rem;")

# 6. Login card
$c = $c.Replace(
    'border: 1px solid rgba(255,255,255,0.08);',
    'border: 1px solid #e0e7ff;'
)
$c = $c.Replace(
    'box-shadow: 0 28px 70px rgba(5, 16, 30, 0.24);',
    'box-shadow: 0 20px 50px rgba(99, 102, 241, 0.08);'
)

# 7. Login button - indigo
$c = $c.Replace(
    'background: linear-gradient(135deg, var(--navy-900), var(--navy-800));',
    'background: linear-gradient(135deg, #4f46e5, #6366f1);'
)
$c = $c.Replace(
    'box-shadow: 0 18px 34px rgba(15, 38, 64, 0.2);',
    'box-shadow: 0 8px 24px rgba(79, 70, 229, 0.25);'
)
$c = $c.Replace(
    'box-shadow: 0 22px 40px rgba(15, 38, 64, 0.26);',
    'box-shadow: 0 12px 28px rgba(79, 70, 229, 0.3);'
)

# 8. Login footer - dark text
$c = $c.Replace(
    "color: rgba(255,255,255,0.68);`n            font-size: .84rem;",
    "color: #94a3b8;`n            font-size: .84rem;"
)

# 9. Login card h1 color (already dark, but update)
$c = $c.Replace('color: var(--navy-900);', 'color: #0f172a;')

# 10. Loader card text
$c = $c.Replace('color:#0c2136;', 'color:#0f172a;')

[System.IO.File]::WriteAllBytes($file, $enc.GetBytes($c))
Write-Host "Done - login updated"
