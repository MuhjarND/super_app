# Reviewer eviden Zona Integritas

Review eviden ZI diproses **cukup salah satu** dari **Sekretaris atau Panitera** melalui antrean bersama. Kedua reviewer melihat permintaan yang sama di Approval dan Tindak Lanjut. Pengajuan baru mengirim notifikasi kepada keduanya sesuai kanal notifikasi aplikasi yang aktif.

Hak review mengikuti role `sekretaris`/`panitera`, jabatan `SEK`/`PAN`, atau delegasi aktif untuk jabatan tersebut. Akun nonaktif tidak dipilih sebagai reviewer. Ketua, Wakil Ketua, role `approval` umum, admin, super admin, Kabag dan Kasubag tidak mempunyai hak keputusan ZI kecuali juga memegang jabatan/role/delegasi Sekretaris atau Panitera.

Admin tetap bisa melihat detail untuk administrasi; tombol keputusan dan endpoint persetujuan/perbaikan tetap khusus reviewer. Reviewer lama tetap dapat membaca keputusan historis yang pernah diprosesnya. Riwayat keputusan tidak ditulis ulang.

Saat Sekretaris atau Panitera mengambil keputusan, `approver_id` dicatat sebagai pengguna yang benar-benar bertindak, bersama catatan dan waktu keputusan. Transaksi mengunci baris permintaan dan memeriksa status terbaru: keputusan pertama menyelesaikan permintaan, keputusan kedua pada permintaan yang sama ditolak. Ini bukan alur persetujuan dua tahap.

## Pembaruan server

Sesudah memperbarui kode, jalankan:

```shell
php artisan migrate
```

Migrasi `2026_11_08_000001_reassign_pending_zi_reviews_to_sekretaris_panitera.php` memindahkan penunjukan pada permintaan **pending** yang masih menunjuk reviewer di luar kelompok baru. Permintaan yang sudah disetujui/dikembalikan dipertahankan. Migrasi tidak mengirim ulang notifikasi eksternal.

Jika tidak ada akun Sekretaris/Panitera aktif, migrasi tidak memaksakan penerima lain. Pengajuan baru menampilkan pesan bahwa reviewer belum tersedia. Antrean bersama tetap dapat diproses ketika akun/jabatan reviewer sudah aktif. `reassignPendingReviews()` dapat dijalankan ulang untuk menyelaraskan penunjukan pending setelah data jabatan diperbaiki.

Tidak ada perubahan hak persetujuan surat keluar, cuti, rapat atau modul lain. Tidak ada perubahan daftar anggota area ZI.
