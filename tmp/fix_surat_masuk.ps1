$file = 'c:\xampp\htdocs\super3\resources\views\surat-masuk\index.blade.php'
$bytes = [System.IO.File]::ReadAllBytes($file)
$enc = New-Object System.Text.UTF8Encoding($false)
$c = $enc.GetString($bytes)

# 1. Card - update border
$c = $c.Replace('border: 1px solid #e5e7eb;', 'border: 1px solid #e8eaed;')

# 2. Header bg
$c = $c.Replace("background: white;`n            border-bottom: 1px solid #f3f4f6;", "background: #ffffff;`n            border-bottom: 1px solid #f0f0f3;")

# 3. Add button - indigo
$c = $c.Replace(
    "background: linear-gradient(135deg, #3b82f6, #2563eb);`n            border: none;`n            color: white;`n            padding: 10px 22px;`n            border-radius: 10px;`n            font-weight: 600;`n            font-size: 0.85rem;",
    "background: linear-gradient(135deg, #6366f1, #4f46e5);`n            border: none;`n            color: white;`n            padding: 10px 22px;`n            border-radius: 10px;`n            font-weight: 600;`n            font-size: 0.85rem;"
)
$c = $c.Replace(
    "background: linear-gradient(135deg, #2563eb, #1d4ed8);`n            color: white;`n            transform: translateY(-1px);`n            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);",
    "background: linear-gradient(135deg, #4f46e5, #4338ca);`n            color: white;`n            transform: translateY(-1px);`n            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.3);"
)

# 4. Expand button hover
$c = $c.Replace('border-color: #3b82f6;' + "`r`n" + '            color: #3b82f6;', 'border-color: #6366f1;' + "`r`n" + '            color: #6366f1;')
$c = $c.Replace('background: #3b82f6;' + "`r`n" + '            border-color: #3b82f6;', 'background: #6366f1;' + "`r`n" + '            border-color: #6366f1;')

# 5. Disposisi action btn colors - update blue
$c = $c.Replace('background: #dbeafe;' + "`r`n" + '            color: #1e40af;', 'background: #eef2ff;' + "`r`n" + '            color: #4f46e5;')
$c = $c.Replace('background: #bfdbfe;' + "`r`n" + '            color: #1e40af;', 'background: #e0e7ff;' + "`r`n" + '            color: #4f46e5;')
$c = $c.Replace('box-shadow: 0 2px 8px rgba(30, 64, 175, 0.2);', 'box-shadow: 0 2px 8px rgba(79, 70, 229, 0.15);')

# 6. Sifat biasa badge
$c = $c.Replace('background: #eff6ff;' + "`r`n" + '            color: #1d4ed8;', 'background: #eef2ff;' + "`r`n" + '            color: #4f46e5;')

[System.IO.File]::WriteAllBytes($file, $enc.GetBytes($c))
Write-Host "Done - surat masuk updated"
