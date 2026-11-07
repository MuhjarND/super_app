# E-voting Papeda

## Mode pengisian

Setiap item dapat memakai kandidat pegawai atau pilihan jawaban. Item lama dan
payload lama tetap menggunakan kandidat. Mode kandidat membutuhkan minimal dua
pegawai aktif. Mode jawaban membutuhkan 2–20 jawaban berbeda, setelah spasi dan
huruf besar/kecil dinormalisasi untuk pemeriksaan duplikasi.

Pemilih memberikan tepat satu pilihan pada setiap item. Formulir kandidat dan
foto disembunyikan serta dinonaktifkan ketika mode jawaban dipilih, sehingga
input yang tersembunyi tidak ikut dikirim. Nilai form tetap tersedia saat mode
diganti kembali sebelum disimpan. Select tunggal memakai kontrol native; pemilih
pegawai jamak memakai Select2 yang sudah digunakan aplikasi. Bahasa UI Indonesia.

Mode tanpa kandidat juga menerima gambar opsional per pilihan jawaban (JPG, PNG,
WebP maksimal 3 MB). Form menyediakan pratinjau dan hapus gambar. Kunci pilihan
tetap stabil saat baris lain dihapus sehingga gambar tidak berpindah jawaban.
Gambar tersimpan dapat dipertahankan, diganti atau dihapus sebelum suara masuk;
ID gambar lama harus berasal dari voting yang sedang diedit. Gambar ditampilkan
pada halaman pemilih, monitoring dan laporan PDF menggunakan kolom gambar yang
sudah ada. Perubahan ini tidak membutuhkan migrasi tambahan.

## Sumber perilaku dan kewenangan

- Permintaan fitur pengguna: opsi memakai kandidat, form kandidat tersembunyi
  pada mode tanpa kandidat, dan monitoring langsung.
- `App\User::canManageVoting()` tetap menjadi sumber izin pengelolaan.
- `App\User::canAccessVoting()` tetap menjadi sumber izin monitoring internal.
- Rute publik memakai kode voting dan daftar peserta sesuai alur sebelumnya.
  Model ini tidak menambahkan autentikasi identitas pemilih atau anonimitas suara.
- Draft belum ditampilkan di hasil publik. Voting aktif menerima suara; voting
  selesai hanya menampilkan hasil. Status dapat diubah oleh pengelola.

## Integritas hasil

`VotingBallotService` mengunci voting dan peserta dalam satu transaksi, memeriksa
status terbaru dan kepemilikan setiap pilihan, lalu merekam semua item sekaligus.
Pengiriman ulang tidak menambah suara. Indeks unik item/pemilih tetap berlaku.
Setelah suara masuk, pengubahan item, kandidat, jawaban dan peserta ditolak.
Status voting dapat ditutup tanpa menghapus hasil dan tidak dapat kembali draft.

Pilihan jawaban memakai tabel pilihan yang sudah ada (`voting_candidates`) dengan
`user_id` kosong, sehingga rujukan suara, laporan dan riwayat kandidat lama tidak
berubah. Migrasi ditujukan untuk MySQL/MariaDB Papeda dan tidak memakai DBAL.
Rollback ditolak jika ada jawaban tanpa kandidat agar data tersebut tidak hilang.

## Pemantauan langsung

Admin dan layar publik memakai `VotingStatsService` serta komponen Blade dan
skrip `voting-live.js` yang sama. Penghitungan suara dilakukan melalui agregasi
database. Data meliputi jumlah peserta, peserta sudah/belum voting, total suara
setiap item, jumlah dan persentase setiap pilihan, serta status voting.

Permintaan berikutnya dilakukan dua detik setelah permintaan sebelumnya selesai.
Permintaan tidak bertumpuk, memiliki timeout, tidak memakai cache, berhenti saat
tab tersembunyi, dan dilanjutkan saat tab aktif. Kegagalan ditampilkan sebagai
status koneksi, mempertahankan hasil terakhir dan menyediakan tombol coba lagi.
Data server ditampilkan sebagai teks DOM, bukan HTML. Fokus keyboard, label,
pilihan radio native, tampilan sempit dan reduced motion dipertahankan.

## Verifikasi

`tests/Feature/VotingModesTest.php` mencakup mode lama, kedua mode baru, item
campuran, validasi, hak akses, pengiriman ulang, kegagalan atomik, penguncian
hasil, status selesai, statistik kedua endpoint dan batas jumlah query.
