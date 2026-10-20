# Rancangan KIOSK Kecamatan

Dokumen ini menjadi rancangan awal alur layanan, fitur, hak akses admin, kebutuhan teknis, dan basis data untuk KIOSK Kecamatan berbasis PHP, JavaScript, HTML, CSS, dan SQLite.

## 1. Tujuan

Menyediakan titik layanan mandiri di kantor kecamatan agar masyarakat dapat menemukan informasi layanan, memeriksa persyaratan, mengajukan permintaan atau pengaduan, mengambil nomor antrean, dan memperoleh bukti layanan. KIOSK juga menyediakan panel admin untuk mengelola master data dan menindaklanjuti pengajuan.

## 2. Peran pengguna

| Peran | Hak akses |
|---|---|
| Masyarakat | Melihat informasi layanan, mencari persyaratan, membuat pengajuan/pengaduan, mengambil antrean, mencetak bukti, dan melacak status dengan kode tiket. Tidak perlu akun untuk penggunaan dasar. |
| Admin | Masuk ke panel pengelolaan; mengelola master data; mengatur akun petugas; memproses pengajuan dan pengaduan; memperbarui status; melihat laporan dan audit aktivitas. |
| Petugas (opsional) | Mengakses antrean dan pengajuan sesuai penugasan; memperbarui status operasional. Tidak dapat mengelola akun admin atau konfigurasi sistem. |

## 3. Menu KIOSK untuk masyarakat

1. **Beranda** — pintasan layanan populer, jam layanan, pengumuman, pencarian, dan tombol bantuan.
2. **Layanan administrasi** — daftar layanan, syarat, biaya (jika berlaku), estimasi waktu, lokasi, formulir, dan tautan kanal resmi.
3. **Informasi kecamatan** — alamat, jam kerja, kontak, peta, daftar kelurahan/desa, dan fasilitas umum.
4. **Pengajuan layanan** — formulir permintaan informasi/layanan yang memang dapat diproses kecamatan; menghasilkan kode tiket.
5. **Cek status** — masukkan kode tiket dan verifikasi ringan untuk melihat status serta catatan tindak lanjut.
6. **Pengaduan dan aspirasi** — kategori, uraian, lokasi, kontak opsional sesuai kebijakan, dan lampiran opsional.
7. **Antrean dan janji layanan** — pilih layanan, ambil nomor antrean atau jadwal konsultasi jika operasional kecamatan mendukung.
8. **Bantuan KIOSK** — panduan singkat, bahasa, aksesibilitas, dan tombol memanggil petugas.

> KIOSK menampilkan panduan dan mengarahkan pengguna ke kanal resmi untuk urusan yang diproses instansi lain (misalnya Dukcapil, bantuan sosial, pajak, atau pembayaran). Jangan menyatakan pengajuan berhasil ke instansi eksternal kecuali ada integrasi resmi.

## 4. Alur kerja masyarakat

### A. Mencari informasi layanan

`Beranda → pilih/cari layanan → detail layanan → lihat syarat, biaya, waktu, lokasi dan kanal pengurusan → cetak/scan QR atau kembali ke beranda`

### B. Mengirim permintaan layanan

`Pilih layanan → baca pemberitahuan privasi dan persyaratan → isi data minimum → konfirmasi → simpan ke SQLite → sistem membuat kode tiket → tampilkan/cetak bukti → admin menerima item di daftar kerja`

Status yang disarankan: `Baru → Ditinjau → Perlu informasi` (opsional) `→ Diproses → Selesai` atau `Ditolak`. Status `Perlu informasi` dan `Ditolak` harus menyertakan alasan yang dapat dipahami.

### C. Mengirim pengaduan/aspirasi

`Pilih kategori → isi uraian dan lokasi → lampirkan foto bila perlu → pilih apakah kontak akan disertakan → konfirmasi → kode tiket → admin memverifikasi dan menugaskan → status tindak lanjut diperbarui → masyarakat mengecek dengan kode tiket`

### D. Mengambil antrean

`Pilih layanan → lihat perkiraan antrean → ambil nomor → cetak/lihat nomor dan QR → layar menampilkan cara menunggu/panggilan`. Admin/petugas memanggil dan menandai selesai atau tidak hadir.

### E. Mengakhiri sesi KIOSK

Setelah beberapa saat tidak ada aktivitas, sistem menampilkan konfirmasi lalu kembali ke beranda. Data yang diketik dibersihkan dari layar dan tidak disimpan di browser; berkas sementara dibersihkan sesuai masa retensi.

## 5. Alur kerja admin

1. Admin masuk melalui halaman terpisah dengan akun aktif.
2. Dashboard menampilkan jumlah pengajuan per status, antrean hari ini, dan pengaduan baru.
3. Admin mengelola **master data**: kategori, layanan, persyaratan, informasi kantor, pengumuman, jadwal/kuota antrean, serta template pesan.
4. Admin membuka item masuk, memeriksa data, menetapkan petugas bila fitur penugasan dipakai, lalu memperbarui status dan catatan.
5. Perubahan status disimpan pada riwayat status; masyarakat dapat melihat status publik tanpa melihat catatan internal.
6. Admin melihat laporan berdasarkan periode, jenis layanan, status, dan waktu penyelesaian; ekspor hanya untuk kebutuhan yang berwenang.
7. Aktivitas penting dicatat pada audit log. Admin dapat menonaktifkan akun petugas dan mengubah kata sandi.

## 6. Fitur panel admin dan master data

- **Kategori layanan:** nama, slug, ikon, urutan, status aktif.
- **Layanan:** nama, kategori, deskripsi, target pemohon, dokumen syarat, biaya, estimasi, unit penanggung jawab, kanal/lokasi, URL resmi, status publikasi, tanggal berlaku.
- **Persyaratan:** nama dokumen, wajib/opsional, keterangan, urutan; dapat terkait ke layanan.
- **Pengumuman:** judul, isi, tanggal mulai/akhir, prioritas, status publikasi.
- **Profil kantor dan kontak:** nama kantor, alamat, jam layanan, telepon, email, tautan peta dan kanal resmi.
- **Kategori pengaduan:** nama dan petunjuk penggunaan.
- **Antrean:** layanan yang menerima antrean, jam, kuota, tanggal tutup, loket.
- **Pengguna internal:** nama, username, hash kata sandi, peran, status aktif, waktu login terakhir.
- **Pengajuan dan pengaduan:** pencarian/filter, detail, penugasan, status, catatan publik dan internal terpisah.
- **Laporan dan audit:** ringkasan operasional, histori perubahan, unduh laporan dengan pembatasan akses.

## 7. Rancangan database SQLite

Gunakan foreign key, indeks untuk kolom pencarian/status, waktu tersimpan dalam ISO-8601, dan migrasi skema bernomor. ID dapat berupa integer autoincrement; kode tiket publik dibuat acak dan tidak berurutan.

### Tabel inti

| Tabel | Kolom utama |
|---|---|
| `users` | `id`, `name`, `username` (unik), `password_hash`, `role` (`admin`/`officer`), `is_active`, `last_login_at`, `created_at`, `updated_at` |
| `service_categories` | `id`, `name`, `slug` (unik), `description`, `icon`, `sort_order`, `is_active`, `created_at`, `updated_at` |
| `services` | `id`, `category_id` FK, `name`, `slug` (unik), `summary`, `description`, `eligibility`, `fee_text`, `estimated_time`, `office_unit`, `service_location`, `official_url`, `is_published`, `valid_from`, `valid_until`, `created_by` FK, `created_at`, `updated_at` |
| `service_requirements` | `id`, `service_id` FK, `name`, `description`, `is_required`, `sort_order` |
| `announcements` | `id`, `title`, `body`, `starts_at`, `ends_at`, `priority`, `is_published`, `created_by` FK, `created_at`, `updated_at` |
| `office_profile` | `id`, `office_name`, `address`, `phone`, `email`, `opening_hours_json`, `map_url`, `official_channels_json`, `updated_at`, `updated_by` FK |
| `request_categories` | `id`, `name`, `kind` (`service_request`/`complaint`), `description`, `is_active`, `sort_order` |
| `submissions` | `id`, `public_code` (unik), `kind`, `service_id` FK nullable, `category_id` FK nullable, `name` nullable, `contact` nullable, `description`, `location_text`, `status`, `assigned_to` FK nullable, `public_note`, `internal_note`, `created_at`, `updated_at`, `closed_at` |
| `submission_events` | `id`, `submission_id` FK, `old_status`, `new_status`, `public_message`, `internal_note`, `changed_by` FK nullable, `created_at` |
| `submission_attachments` | `id`, `submission_id` FK, `stored_name`, `original_name`, `mime_type`, `size_bytes`, `created_at` |
| `queue_config` | `id`, `service_id` FK, `weekday`, `opens_at`, `closes_at`, `daily_quota`, `is_active` |
| `queue_tickets` | `id`, `public_code` (unik), `service_id` FK, `queue_date`, `queue_number`, `status`, `called_at`, `served_at`, `created_at` |
| `audit_logs` | `id`, `user_id` FK nullable, `action`, `entity_type`, `entity_id`, `summary`, `created_at` |
| `settings` | `key` (PK), `value`, `updated_at`, `updated_by` FK nullable |

Status awal `submissions`: `new`, `reviewing`, `needs_info`, `in_progress`, `completed`, `rejected`. Status tiket: `waiting`, `called`, `served`, `no_show`, `cancelled`.

### Aturan penyimpanan dan privasi

- Simpan kata sandi hanya dengan `password_hash()` dan verifikasi `password_verify()`.
- Jangan simpan NIK, nomor KK, foto identitas, atau data sensitif kecuali benar-benar diperlukan dan dasar pengelolaannya jelas. KIOSK publik sebaiknya mengumpulkan data minimum.
- Lampiran disimpan di direktori privat di luar web root; SQLite hanya menyimpan metadata dan nama file acak. Validasi ukuran, MIME, dan ekstensi; jangan menerima file executable.
- Kode tiket harus cukup acak, tidak memuat data pribadi, dan cek status dapat meminta faktor verifikasi kedua (misalnya nomor kontak parsial/OTP) bila data yang ditampilkan sensitif.
- Terapkan masa retensi dan prosedur penghapusan/anonimisasi sesuai kebijakan instansi.
- Catatan publik dan catatan internal dipisahkan. Jangan pernah menampilkan catatan internal ke masyarakat.

## 8. Kebutuhan teknis

### Perangkat lunak

- PHP 8.2+ dengan ekstensi `PDO_SQLite` dan `fileinfo`.
- SQLite dengan foreign key aktif (`PRAGMA foreign_keys = ON`) dan backup berkala.
- HTML5 semantik, CSS responsif, JavaScript modern tanpa framework sebagai pilihan awal.
- Web server Apache atau Nginx dengan HTTPS untuk panel admin dan integrasi jaringan.
- Zona waktu aplikasi dikonfigurasi untuk `Asia/Jakarta`; simpan waktu dengan format konsisten.

### Perangkat KIOSK

- Komputer layar sentuh, browser mode kiosk, printer thermal/A4 sesuai bukti yang dicetak, koneksi jaringan lokal/internet sesuai kebutuhan.
- Tombol bantuan fisik atau cara mudah memanggil petugas.
- Pengaturan layar otomatis kembali ke beranda dan pembersihan sesi.

### Keamanan aplikasi

- PDO prepared statements untuk semua query; validasi server-side untuk seluruh input.
- Proteksi CSRF pada form admin, escaping output HTML, pembatasan percobaan login, cookie sesi `HttpOnly`, `Secure`, dan `SameSite`.
- Hak akses diperiksa di server pada setiap endpoint admin; jangan mengandalkan penyembunyian tombol di JavaScript.
- Batasi jenis/ukuran unggahan, gunakan nama berkas acak, dan jangan sajikan lampiran langsung dari direktori publik.
- Backup database terenkripsi sesuai kebijakan dan uji pemulihannya secara berkala.
- Siapkan audit log, log error tanpa data pribadi, dan mekanisme pembaruan dependensi.

## 9. Struktur aplikasi yang disarankan

```text
public/
  index.php
  assets/css/app.css
  assets/js/app.js
app/
  Controllers/
  Services/
  Repositories/
  Middleware/
  Views/
config/
  app.php
  database.php
database/
  migrations/
  kiosk.sqlite          # dibuat saat instalasi, tidak disimpan ke Git
storage/
  uploads/              # di luar public web root
  logs/
```

Gunakan front controller sederhana (`public/index.php`), pemisahan controller dan akses data, serta file migrasi SQL. Untuk instalasi satu kecamatan, SQLite cukup selama beban tulis rendah, ada satu server aplikasi, dan backup dilakukan. Jika nanti banyak kiosk menulis serentak atau dibutuhkan layanan daring multi-instance, rencanakan migrasi ke PostgreSQL/MySQL.

### Setup login admin awal

Pastikan PHP 8.2+ memiliki ekstensi `PDO_SQLite`, lalu dari direktori proyek jalankan:

```sh
php bin/setup-admin.php
```

Perintah ini menjalankan migrasi SQLite, meminta nama, username, dan password admin pertama, lalu membuat satu kode pemulihan acak. Simpan kode tersebut di tempat aman; kode hanya ditampilkan sekali, disimpan dalam bentuk hash, dan hanya dapat dipakai satu kali. Jangan masukkan database `database/kiosk.sqlite` ke Git. Untuk menjalankan server pengembangan dengan document root yang benar:

```sh
php -S localhost:8000 -t public
```

Buka `http://localhost:8000`, lalu pilih **Login Admin**. Gunakan `php bin/migrate.php` untuk menjalankan migrasi baru setelah pembaruan aplikasi.

Halaman reset password meminta username dan kode pemulihan. Formulir admin mendukung keyboard perangkat saat input disentuh serta menyediakan tombol **Keyboard layar sentuh** sebagai alternatif. Pada perangkat kiosk, aktifkan juga keyboard virtual di sistem operasi/browser kiosk untuk pengalaman input native. Situs produksi wajib disajikan melalui HTTPS agar cookie sesi menggunakan atribut `Secure`.

### Dashboard dan navigasi admin

Setelah login, admin membuka `public/admin/dashboard.php`. Panel ini dirancang untuk laptop/desktop dengan sidebar, tabel aktivitas, serta navigasi keyboard dan mouse; tidak memerlukan touchscreen. Di layar kecil, tombol menu membuka sidebar.

Dashboard membaca jumlah admin aktif, petugas aktif, aktivitas hari ini (WIB), total audit, dan tujuh aktivitas terakhir langsung dari SQLite. Statistik layanan belum ditampilkan karena modul operasional belum diimplementasikan. Menu konten KIOSK, operasional, akun, laporan, audit lengkap, dan pengaturan membuka halaman penjelasan dengan status **belum tersedia**. Tahap ini belum menyediakan tambah, edit, atau hapus data (CRUD).

## 10. Tahapan implementasi

1. **Fondasi:** skema/migrasi SQLite, login admin, kontrol peran, audit log, dan pengelolaan profil kantor.
2. **Informasi publik:** beranda, pencarian layanan, kategori, detail layanan, persyaratan, dan pengumuman.
3. **Transaksi dasar:** pengajuan/pengaduan, kode tiket, halaman cek status, serta panel tindak lanjut admin.
4. **Operasional:** antrean, cetak bukti, laporan, aksesibilitas, dan timeout sesi KIOSK.
5. **Penguatan:** backup/pemulihan, uji keamanan dan beban yang sesuai perangkat, kebijakan retensi, pelatihan petugas, serta evaluasi penggunaan.

## 11. Kriteria keberhasilan

- Masyarakat dapat menemukan syarat dan kanal resmi sebuah layanan dalam beberapa langkah.
- Setiap pengajuan yang berhasil mendapat kode tiket dan memiliki riwayat status.
- Admin dapat memperbarui master data tanpa mengubah kode aplikasi.
- Masyarakat hanya melihat status dan catatan yang ditujukan untuk publik.
- Setelah sesi berakhir, informasi yang dimasukkan tidak tertinggal di layar KIOSK.
- Database dapat dicadangkan dan dipulihkan; kegagalan aplikasi tidak menghilangkan bukti transaksi yang tersimpan.
