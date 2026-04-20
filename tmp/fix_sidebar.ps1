$file = 'c:\xampp\htdocs\super3\resources\views\layouts\app.blade.php'
$bytes = [System.IO.File]::ReadAllBytes($file)
$enc = New-Object System.Text.UTF8Encoding($false)
$c = $enc.GetString($bytes)

# ==========================================
# 1. SIDEBAR: Remove 'is-collapsed' from all section defaults
#    - Keep the route-active logic but flip the default
#    - Now: sections start COLLAPSED unless on that route
#    - New: sections NEVER start collapsed from HTML (JS handles persistence)
# ==========================================

# Approval section - remove is-collapsed default
$c = $c.Replace(
    "{{ request()->routeIs('approval.*') ? '' : 'is-collapsed' }}"" data-section=""approval""",
    """ data-section=""approval"""
)

# Persuratan section
$c = $c.Replace(
    "{{ request()->routeIs('surat-masuk.*') || request()->routeIs('surat-keluar.*') || request()->routeIs('surat-template.*') ? '' : 'is-collapsed' }}"" data-section=""persuratan""",
    """ data-section=""persuratan"""
)

# Rapat section
$c = $c.Replace(
    "{{ request()->routeIs('rapat.*') ? '' : 'is-collapsed' }}"" data-section=""rapat""",
    """ data-section=""rapat"""
)

# Cuti section
$c = $c.Replace(
    "{{ request()->routeIs('cuti.*') || request()->routeIs('surat-tugas.*') ? '' : 'is-collapsed' }}"" data-section=""cuti""",
    """ data-section=""cuti"""
)

# Perawatan section
$c = $c.Replace(
    "{{ request()->routeIs('perawatan.*') ? '' : 'is-collapsed' }}"" data-section=""perawatan""",
    """ data-section=""perawatan"""
)

# Persediaan section
$c = $c.Replace(
    "{{ request()->routeIs('persediaan.*') ? '' : 'is-collapsed' }}"" data-section=""persediaan""",
    """ data-section=""persediaan"""
)

# Progress ZI section
$c = $c.Replace(
    "{{ request()->routeIs('progress-zi.*') ? '' : 'is-collapsed' }}"" data-section=""progress-zi""",
    """ data-section=""progress-zi"""
)

# Master Data section
$c = $c.Replace(
    "{{ request()->routeIs('admin.*') || request()->routeIs('progress-zi.periods.*') || request()->routeIs('progress-zi.areas.*') ? '' : 'is-collapsed' }}"" data-section=""master-data""",
    """ data-section=""master-data"""
)

# ==========================================
# 2. SIDEBAR JS: Update to use localStorage properly
#    - If NO saved state exists, all sections stay open (default)
#    - If saved state exists, apply it (user's preference persists)
# ==========================================

# The existing JS logic is actually correct already:
# - It reads from localStorage
# - If savedSections has a key, it applies it
# - If not (first login), sections stay as they are in HTML (now = expanded)
# So no JS changes needed for section toggle behavior!

# Remove dead theme toggle JS (leftover from removed dark mode)
$c = $c.Replace(
    "`$('#themeToggle').on('click', function () {`r`n                const nextTheme = `$('body').hasClass('theme-dark') ? 'light' : 'dark';`r`n                localStorage.setItem(THEME_KEY, nextTheme);`r`n                applyTheme(nextTheme);`r`n            });",
    ""
)

# ==========================================
# 3. CLEAN UP: Remove sidebar subtitle text "Super Admin"
#    under user name - redundant since role is shown
# ==========================================

# Remove subtitle text from user panel
$c = $c.Replace(
    "<div class=""sidebar-user-name"">{{ Auth::user()->name }}</div>`r`n                                <div class=""sidebar-user-role"">{{ Auth::user()->role_label ?? 'Pegawai' }}</div>",
    "<div class=""sidebar-user-name"">{{ Auth::user()->name }}</div>"
)

# Write back
[System.IO.File]::WriteAllBytes($file, $enc.GetBytes($c))
Write-Host "Done - layout sidebar updated"
