$viewsDir = 'c:\xampp\htdocs\super3\resources\views'
$enc = New-Object System.Text.UTF8Encoding($false)
$count = 0

$files = Get-ChildItem -Path $viewsDir -Filter '*.blade.php' -Recurse

foreach ($f in $files) {
    $path = $f.FullName
    $bytes = [System.IO.File]::ReadAllBytes($path)
    $original = $enc.GetString($bytes)
    $c = $original

    # 1. Page header descriptions - simplify verbose ones
    $c = $c.Replace("Kelola semua surat masuk yang diterima.", "")
    $c = $c.Replace("Kelola data surat keluar yang dikeluarkan.", "")
    $c = $c.Replace("Lihat detail informasi surat masuk.", "")
    $c = $c.Replace("Lihat detail informasi surat keluar.", "")
    $c = $c.Replace("Kelola semua template surat yang tersedia.", "")
    $c = $c.Replace("Kelola data rapat dan jadwal agenda.", "")
    $c = $c.Replace("Kelola notulensi rapat yang telah dilaksanakan.", "")
    $c = $c.Replace("Kelola data absensi kehadiran rapat.", "")
    $c = $c.Replace("Kelola dan pantau agenda rapat.", "")
    $c = $c.Replace("Kelola data voting rapat.", "")
    $c = $c.Replace("Kelola laporan dan hasil rapat.", "")
    $c = $c.Replace("Kelola data pengajuan cuti pegawai.", "")
    $c = $c.Replace("Kelola data tindak lanjut rapat.", "")
    $c = $c.Replace("Kelola data pengguna sistem.", "")
    $c = $c.Replace("Kelola data kategori rapat.", "")
    $c = $c.Replace("Kelola data klasifikasi surat.", "")
    $c = $c.Replace("Kelola data unit kerja / bagian.", "")
    $c = $c.Replace("Kelola seluruh aktivitas pengguna di sistem.", "")
    
    # 2. Clean empty page-header-desc paragraphs
    $c = $c -replace '<p class="page-header-desc">\s*</p>', ''

    # Only write if changed
    if ($c -ne $original) {
        [System.IO.File]::WriteAllBytes($path, $enc.GetBytes($c))
        $count++
        Write-Host "Updated: $($f.Name)"
    }
}

Write-Host "`nDone - $count files cleaned"
