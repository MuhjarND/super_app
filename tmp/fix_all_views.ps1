$viewsDir = 'c:\xampp\htdocs\super3\resources\views'
$enc = New-Object System.Text.UTF8Encoding($false)
$count = 0

# Get all blade files
$files = Get-ChildItem -Path $viewsDir -Filter '*.blade.php' -Recurse

foreach ($f in $files) {
    $path = $f.FullName
    
    # Skip already-processed main files (we've already done these carefully)
    if ($path -match 'layouts\\app\.blade\.php$') { continue }
    if ($path -match 'auth\\login\.blade\.php$') { continue }
    
    $bytes = [System.IO.File]::ReadAllBytes($path)
    $original = $enc.GetString($bytes)
    $c = $original

    # ---- Color palette: blue -> indigo-violet ----
    
    # Primary blue shades -> indigo
    $c = $c.Replace('#3b82f6', '#6366f1')   # blue-500 -> indigo-500
    $c = $c.Replace('#2563eb', '#4f46e5')   # blue-600 -> indigo-600
    $c = $c.Replace('#1d4ed8', '#4338ca')   # blue-700 -> indigo-700
    $c = $c.Replace('#1e40af', '#3730a3')   # blue-800 -> indigo-800
    $c = $c.Replace('#1e3a8a', '#312e81')   # blue-900 -> indigo-900
    
    # Light blue -> light indigo
    $c = $c.Replace('#eff6ff', '#eef2ff')   # blue-50 -> indigo-50
    $c = $c.Replace('#dbeafe', '#e0e7ff')   # blue-100 -> indigo-100
    $c = $c.Replace('#bfdbfe', '#c7d2fe')   # blue-200 -> indigo-200
    $c = $c.Replace('#93c5fd', '#a5b4fc')   # blue-300 -> indigo-300
    $c = $c.Replace('#60a5fa', '#818cf8')   # blue-400 -> indigo-400

    # ---- Shadow reduction ----
    $c = $c.Replace('box-shadow: 0 6px 18px rgba(37, 99, 235,', 'box-shadow: 0 2px 8px rgba(79, 70, 229,')
    $c = $c.Replace('box-shadow: 0 6px 16px rgba(15, 23, 42, 0.12)', 'box-shadow: none')
    $c = $c.Replace('box-shadow: 0 6px 18px rgba(15, 23, 42, 0.14)', 'box-shadow: none')
    $c = $c.Replace('box-shadow: 0 10px 22px rgba(15, 23, 42, 0.2)', 'box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08)')

    # ---- Border softening ----
    $c = $c.Replace('border: 1px solid #e5e7eb', 'border: 1px solid #e8eaed')
    $c = $c.Replace('border-bottom: 1px solid #e5e7eb', 'border-bottom: 1px solid #e8eaed')

    # ---- Border radius consistency ----
    $c = $c.Replace('border-radius: 16px;', 'border-radius: 14px;')

    # Only write if changed
    if ($c -ne $original) {
        [System.IO.File]::WriteAllBytes($path, $enc.GetBytes($c))
        $count++
        Write-Host "Updated: $($f.Name)"
    }
}

Write-Host "`nDone - $count files updated"
