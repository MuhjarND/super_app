$file = 'c:\xampp\htdocs\super3\resources\views\dashboard.blade.php'
$bytes = [System.IO.File]::ReadAllBytes($file)
$enc = New-Object System.Text.UTF8Encoding($false)
$c = $enc.GetString($bytes)

# 1. Hero meta - shorten verbose text
$c = $c.Replace(
    "{{ now()->translatedFormat('l, d F Y') }} &bull; Ringkasan kerja lintas modul",
    "{{ now()->translatedFormat('l, d F Y') }}"
)

# 2. Calendar panel subtitle - shorten
$c = $c.Replace(
    "{{ `$calendarOverview['month_label'] }} &bull; ringkasan agenda lintas modul yang terlihat untuk Anda.",
    "{{ `$calendarOverview['month_label'] }}"
)

# 3. Shorten calendar stat labels
$c = $c.Replace(">Total event bulan berjalan<", ">Event bulan ini<")
$c = $c.Replace(">Hari yang memiliki agenda<", ">Hari dengan agenda<")
$c = $c.Replace(">Rapat dan agenda pimpinan<", ">Rapat & agenda<")
$c = $c.Replace(">Agenda pimpinan bulan ini<", ">Agenda pimpinan<")
$c = $c.Replace(">Rentang cuti pegawai<", ">Cuti pegawai<")
$c = $c.Replace(">Agenda dan target Progress ZI<", ">Progress ZI<")
$c = $c.Replace(">Surat tugas aktif bulan ini<", ">Surat tugas<")
$c = $c.Replace(">Tanggal dengan benturan jadwal<", ">Benturan jadwal<")

# 4. Shorten hero chip labels
$c = $c.Replace(">Tindak lanjut aktif<", ">Tindak lanjut<")
$c = $c.Replace(">Rapat / agenda mendatang<", ">Agenda mendatang<")
$c = $c.Replace(">Approval cuti pending<", ">Cuti pending<")
$c = $c.Replace(">Surat masuk hari ini<", ">Surat masuk<")

# 5. Module card descriptions - shorter
$c = $c.Replace(
    "Surat masuk, surat keluar, dan disposisi yang dikelola dengan tracking status realtime.",
    "Kelola surat masuk, keluar, dan disposisi."
)
$c = $c.Replace(
    "Rapat, agenda pimpinan, approval undangan, dan tindak lanjut terpadu.",
    "Rapat, agenda, dan tindak lanjut."
)
$c = $c.Replace(
    "Pengajuan cuti Anda, status proses, dan approval yang berlaku.",
    "Pengajuan dan status cuti."
)
$c = $c.Replace(
    "Pencatatan dan monitoring perawatan alat dan mesin kantor.",
    "Monitoring perawatan alat & mesin."
)
$c = $c.Replace(
    "Manajemen dan tracking persediaan barang kantor.",
    "Tracking persediaan barang."
)
$c = $c.Replace(
    "Monitoring progress dan evaluasi indikator Zona Integritas.",
    "Progress & evaluasi ZI."
)

# Write back
[System.IO.File]::WriteAllBytes($file, $enc.GetBytes($c))
Write-Host "Done - dashboard text cleaned"
