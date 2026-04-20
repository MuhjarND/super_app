$file = 'c:\xampp\htdocs\super3\resources\views\layouts\app.blade.php'
$bytes = [System.IO.File]::ReadAllBytes($file)
$enc = New-Object System.Text.UTF8Encoding($false)
$c = $enc.GetString($bytes)

# 1. Remove dead theme toggle JS (if any leftover)
$c = $c.Replace(
    "`$('#themeToggle').on('click', function () {",
    "// Theme toggle removed"
)

# 2. Clean up footer text - simplify
$c = $c.Replace(
    "Hak Cipta &copy; <strong>PTA Papua Barat</strong> - {{ date('Y') }}",
    "&copy; PTA Papua Barat {{ date('Y') }}"
)

# 3. Remove "Versi Dikembangkan Oleh" footer
$c = $c.Replace(
    "Versi <strong>{{ config('app.version', '1.0.0') }}</strong> &mdash; Dikembangkan Oleh <strong>PTA Papua Barat</strong>",
    "v{{ config('app.version', '1.0.0') }}"
)

# Write back
[System.IO.File]::WriteAllBytes($file, $enc.GetBytes($c))
Write-Host "Done - layout text cleaned"
