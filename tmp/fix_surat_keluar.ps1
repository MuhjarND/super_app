$file = 'c:\xampp\htdocs\super3\resources\views\surat-keluar\index.blade.php'
$bytes = [System.IO.File]::ReadAllBytes($file)
$enc = New-Object System.Text.UTF8Encoding($false)
$c = $enc.GetString($bytes)

# 1. Card border
$c = $c.Replace('border: 1px solid #e5e7eb;', 'border: 1px solid #e8eaed;')

# 2. Add button - indigo-violet
$c = $c.Replace(
    'background: linear-gradient(135deg, #6366f1, #8b5cf6);',
    'background: linear-gradient(135deg, #6366f1, #4f46e5);'
)
$c = $c.Replace(
    'background: linear-gradient(135deg, #4f46e5, #7c3aed);',
    'background: linear-gradient(135deg, #4f46e5, #4338ca);'
)
$c = $c.Replace(
    'box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);',
    'box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25);'
)

# 3. Recipient name pill - indigo
$c = $c.Replace(
    'background: linear-gradient(90deg, #5738d6, #6b3fe8);',
    'background: linear-gradient(135deg, #6366f1, #4f46e5);'
)

# 4. Status badge complete - indigo
$c = $c.Replace('background: #e5efff;' + "`n" + '            color: #2563eb;', 'background: #eef2ff;' + "`n" + '            color: #4f46e5;')

# 5. Expand button hover
$c = $c.Replace('border-color: #3b82f6;' + "`n" + '            background: #3b82f6;', 'border-color: #6366f1;' + "`n" + '            background: #6366f1;')

# 6. Detail meta strong
$c = $c.Replace('color: #1e3a8a;', 'color: #3730a3;')

# 7. Page header icon gradient
$c = $c.Replace(
    'background: linear-gradient(135deg, #f0fdf4, #dcfce7);',
    'background: linear-gradient(135deg, #f0fdf4, #dcfce7);'
)

[System.IO.File]::WriteAllBytes($file, $enc.GetBytes($c))
Write-Host "Done - surat keluar updated"
