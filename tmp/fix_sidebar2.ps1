$file = 'c:\xampp\htdocs\super3\resources\views\layouts\app.blade.php'
$bytes = [System.IO.File]::ReadAllBytes($file)
$enc = New-Object System.Text.UTF8Encoding($false)
$c = $enc.GetString($bytes)

# Fix remaining is-collapsed patterns in HTML
# Pattern: {{ request()->routeIs('xxx') ? '' : 'is-collapsed' }}

# Cuti (line 1986) - different pattern than expected
$c = $c.Replace(
    "{{ request()->routeIs('cuti.*') ? '' : 'is-collapsed' }}"" data-section=""cuti""",
    """ data-section=""cuti"""
)

# Perawatan (line 2057) - uses perawatan-alat-mesin
$c = $c.Replace(
    "{{ request()->routeIs('perawatan-alat-mesin.*') ? '' : 'is-collapsed' }}"" data-section=""perawatan-alat-mesin""",
    """ data-section=""perawatan-alat-mesin"""
)

# Persediaan (line 2138) - uses persediaan-dev as data-section
$c = $c.Replace(
    "{{ request()->routeIs('persediaan.*') ? '' : 'is-collapsed' }}"" data-section=""persediaan-dev""",
    """ data-section=""persediaan-dev"""
)

# Arsip (line 2269) - uses arsip  
$c = $c.Replace(
    "{{ request()->routeIs('arsip.*') ? '' : 'is-collapsed' }}"" data-section=""arsip""",
    """ data-section=""arsip"""
)

# Also check for any remaining surat-tugas pattern
$c = $c.Replace(
    "{{ request()->routeIs('cuti.*') || request()->routeIs('surat-tugas.*') ? '' : 'is-collapsed' }}"" data-section=""cuti""",
    """ data-section=""cuti"""
)

# Write back
[System.IO.File]::WriteAllBytes($file, $enc.GetBytes($c))
Write-Host "Done - remaining sections fixed"
