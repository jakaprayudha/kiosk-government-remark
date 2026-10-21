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

Dashboard membaca jumlah admin aktif, pengajuan baru, total pengajuan dalam retensi (termasuk DEMO), aktivitas hari ini (WIB), dan tujuh aktivitas audit terakhir langsung dari SQLite. Menu **Pengajuan layanan** aktif untuk pemeriksaan dan tindak lanjut; **Layanan administrasi** menyediakan pengaturan transaksi per layanan; **Persyaratan & biaya** menyediakan tambah/edit/publikasi master referensi dokumen. **Pengaduan & aspirasi** menyediakan tindak lanjut laporan, dan **Antrean & jadwal** mengelola simulasi instansi/layanan/jadwal berkuota dan panggilan. CRUD konten kategori/layanan transaksi, akun, laporan, audit lengkap, dan pengaturan umum masih menyusul.

### UI KIOSK publik

Buka `public/kiosk.php` melalui `/kiosk.php` pada server dengan document root `public`. Aksi **Lihat KIOSK publik** di sidebar dan banner dashboard membuka halaman ini di tab baru tanpa mengganti halaman admin.

UI publik menyediakan delapan menu dengan target sentuh besar, panduan informasi, tombol beranda/bantuan, pilihan teks besar, dan jam WIB. Menu layanan administrasi membaca katalog transaksi SQLite; persyaratan dan biaya membaca master referensi dokumen SQLite yang terpisah. Pengajuan tersedia untuk layanan yang diaktifkan pengelola, dengan cek tiket + PIN. Informasi kecamatan membaca profil dan titik desa SQLite. Pengaduan digital tersedia; antrean lintas instansi dan janji tersedia sebagai simulasi lokal DEMO, bukan reservasi resmi. Publik tidak perlu login. Formulir/foto berada sementara di memori halaman sampai dikirim; tidak memakai localStorage/IndexedDB untuk data pemohon.

Header tanggal dan waktu tetap terlihat saat halaman digulir. Jam menampilkan jam, menit, dan detik WIB sesuai waktu perangkat, diperbarui setiap detik. Menyentuh menu membacakan nama menu dalam bahasa Indonesia melalui Web Speech API sebelum membuka halaman tujuan. Ketersediaan suara bergantung pada dukungan browser, suara bahasa Indonesia di sistem operasi, serta volume perangkat; tidak menggunakan API suara pihak ketiga yang dikonfigurasi aplikasi. Jika suara gagal atau tidak didukung, pemberitahuan ditampilkan dan menu tetap dibuka. Membuka tautan dengan Ctrl/Cmd atau di tab baru tidak menunggu suara.

Setelah 120 detik tanpa aktivitas, dialog konfirmasi ditampilkan selama 20 detik sebelum membersihkan sesi publik dan kembali ke beranda KIOSK. Tombol **Ya, lanjutkan** atau Escape membatalkan hitung mundur. Tombol **Selesai & bersihkan sesi** mengakhiri sesi secara manual. Kamera dihentikan, input/foto/tiket dibersihkan, dan server membatalkan akses bukti melalui sesi tersebut. Heartbeat mempertahankan sesi selama pengguna aktif mengisi formulir; tanpa permintaan selama 140 detik, server membatalkan sesi sebagai pengaman. Sesi publik memakai cookie terpisah dari admin; beberapa tab publik pada browser yang sama berbagi sesi. Jangan menyiapkan pengguna berbeda dalam tab publik paralel pada browser yang sama.

### Informasi kecamatan dan peta desa (SQLite)

Buka `/kiosk.php?menu=kantor` untuk profil Kecamatan Sei Rampah, Kabupaten Serdang Bedagai, Sumatera Utara, serta kartu desa dan peta interaktif. Jalankan `php bin/seed-demo-office.php` untuk menerapkan migrasi `005_office_villages` dan memuat profil serta **17 desa contoh**. Seed SQL berada di `database/seeds/demo-sei-rampah.sql`, aman dijalankan ulang tanpa menggandakan atau menimpa perubahan.

- `office_profiles`: slug, nama kecamatan/kabupaten/provinsi, deskripsi, alamat, jam layanan, catatan kontak, pusat peta (latitude/longitude), URL rujukan, DEMO, publikasi, dan waktu perubahan.
- `office_villages`: FK profil, slug unik per profil, nama desa, nama kepala desa, telepon desa (`phone`, migrasi `006_village_phone`), jumlah dan tahun penduduk, latitude/longitude, catatan koordinat, DEMO, publikasi, dan urutan. Jalankan `php bin/migrate.php` untuk menambah kolom telepon pada instalasi lama. Nomor contoh tidak direkayasa; telepon kosong ditampilkan sebagai belum tersedia pada kartu dan popup.
- Hanya profil dan desa terpublikasi ditampilkan. Total penduduk dijumlahkan dari desa yang tampil, bukan seluruh database. Profil terpilih memakai slug `sei-rampah`.

Nama 17 desa merujuk [daftar wilayah Sei Rampah](https://id.wikipedia.org/wiki/Sei_Rampah,_Serdang_Bedagai), yang mencantumkan rujukan BPS. **Nama kades fiktif, populasi tahun 2026 dummy, koordinat desa titik perkiraan untuk visualisasi, alamat/jam kantor contoh**; semuanya bukan informasi pemerintah terverifikasi. Titik bukan lokasi kantor, pusat desa, batas administrasi, atau petunjuk navigasi. Pusat awal peta juga bukan penanda kantor terverifikasi. Ganti dengan data dan koordinat yang disahkan sebelum penggunaan operasional.

Leaflet 1.9.4 dibundel lokal. Titik bernomor dan kartu/koordinat tersedia tanpa internet; sentuh titik untuk popup berisi nama desa, kepala desa, penduduk/tahun, telepon dan koordinat yang sama dengan kartu, tombol kartu untuk memusatkan peta, dan **Lihat semua desa** untuk mengatur ulang jangkauan. Peta dasar OpenStreetMap **dimuat otomatis** saat halaman dibuka: browser meminta tile ke `tile.openstreetmap.org`, mengirim IP dan area peta, bukan data kades/penduduk/telepon. Tidak meminta geolokasi perangkat. Atribusi OpenStreetMap tetap terlihat selama tile aktif; jangan mengunduh massal/prefetch tile. Jika internet gagal, pemberitahuan dan tombol **Coba lagi peta dasar** muncul, sementara titik/kartu lokal tetap dapat digunakan. Tanpa JavaScript, informasi kartu masih terbaca. Tombol **Matikan peta dasar** menghentikan layer, tetapi tile yang sudah dimuat dapat tersimpan di cache browser.

Permintaan tile secara khusus memakai `referrerPolicy: strict-origin-when-cross-origin` agar mengirim origin situs yang sebenarnya sesuai [kebijakan tile OSM](https://operations.osmfoundation.org/policies/tiles/), tanpa path/query halaman. Kebijakan `same-origin` pada halaman publik lainnya tetap dipertahankan. Jangan menghapus Referer tile: penyedia dapat menolak akses, termasuk mengembalikan gambar pesan blokir dengan HTTP 200; keberhasilan tidak cukup dinilai hanya dari event gambar selesai dimuat.

Pemilih **Tampilan peta** menyediakan **Peta jalan — OpenStreetMap** (default) dan **Satelit — Esri World Imagery**. Satelit dimuat dari `services.arcgisonline.com` hanya saat dipilih; satu layer dasar aktif pada satu waktu, sementara marker dan popup desa tetap tersedia. Atribusi berganti sesuai penyedia. Kedua penyedia menerima IP, origin situs, dan area tile yang diminta. Citra satelit bukan realtime, bukan layer kemacetan, dan tidak menambahkan garis batas desa/bidang tanah; resolusi/usia citra bergantung wilayah. Penggunaan layanan Esri tunduk pada ketentuan [World Imagery](https://goto.arcgisonline.com/maps/World_Imagery); tinjau lisensi dan kapasitas layanan sebelum deployment produksi. Mengganti layer juga mengaktifkan kembali peta dasar jika sebelumnya dimatikan.

CRUD profil/desa di panel admin **belum tersedia**. Pengelola teknis memperbarui kedua tabel lewat SQLite dengan `PRAGMA foreign_keys = ON`, transaksi, parameter terikat, dan pembaruan `updated_at`. Jangan menghapus label DEMO sebelum memverifikasi seluruh isi terkait, termasuk catatan koordinat. Perubahan terlihat setelah halaman dimuat ulang; seed tidak menimpa baris yang sudah ada. Bundel ulang aset dengan `npm run build`; lisensi Leaflet disertakan di `public/assets/js/office-map.LICENSE.txt`.

Uji tanpa mengubah database aplikasi: `php tests/office.php`.

### Sarana kecamatan (DEMO SQLite)

Menu informasi kecamatan juga membaca `office_facilities` (migrasi `007_office_facilities`): FK profil, slug, kategori, nama, deskripsi, alamat, telepon, jam operasional, koordinat, catatan koordinat, DEMO, publikasi, urutan dan waktu perubahan. Hanya sarana terpublikasi pada profil yang dipilih ditampilkan. Jalankan ulang `php bin/seed-demo-office.php` untuk menerapkan migrasi dan memuat `database/seeds/demo-office-facilities.sql` secara idempoten tanpa menimpa perubahan.

Seed menyediakan **8 fasilitas rekaan**: satu puskesmas, dua SD, satu SMP, satu SMA, satu kantor pelayanan, satu pasar, dan satu lapangan olahraga. Nama dan koordinat bukan fasilitas nyata di Sei Rampah. Telepon tidak direkayasa; kosong ditampilkan belum tersedia. Jangan gunakan sebagai rujukan medis/darurat, pendaftaran sekolah atau navigasi.

Marker desa tetap **lingkaran biru bernomor**; sarana memakai **kotak berwarna dengan kode**: kesehatan merah (KES), SD hijau, SMP ungu, SMA kuning tua, kantor teal (KTR), pasar oranye (PSR), olahraga merah muda (OR). Legenda ikut masuk modal fullscreen. Popup/kartu memuat nama, kategori, informasi, alamat, jam operasional, telepon dan koordinat. Tombol **Lihat semua titik** mencakup desa dan fasilitas; ID keduanya dipisahkan agar tidak tertukar. Mode jalan/satelit dan modal tetap dapat digunakan. CRUD admin sarana belum tersedia; pengelola teknis memperbarui SQLite dan memverifikasi data sebelum menghapus label DEMO.

Kontrol **Mode peta** berada di dalam peta di kiri atas, di bawah zoom **+ / -**. Tombol **Layar penuh** membuka dialog modal seluas viewport (bukan Fullscreen API browser), dengan tombol **Tutup peta** dan dukungan Escape. Instance peta yang sama dipindahkan sementara ke modal sehingga layer, titik, dan posisi tidak direset. Status pemuatan ikut tampil di modal; setelah ditutup, peta kembali ke halaman. Dialog konfirmasi idle tetap dapat tampil di atas modal peta dan pengakhiran sesi menutup modal.

### Katalog layanan dinamis (SQLite)

Jalankan `php bin/migrate.php` untuk menerapkan migrasi `002_service_catalog`. Menu `/kiosk.php?menu=layanan` membaca tabel berikut:

- `service_categories`: nama, slug unik, deskripsi, ikon, urutan, dan status aktif.
- `services`: kategori, nama, slug, ringkasan, deskripsi, sasaran pemohon, biaya, estimasi waktu, unit, lokasi/kanal, URL resmi, urutan, status publikasi, dan tanggal berlaku.
- `service_requirements`: dokumen per layanan, keterangan, status wajib/opsional, dan urutan.

Layanan baru secara default berstatus draf (`is_published = 0`). Katalog dan detail hanya menampilkan layanan berstatus publikasi dalam kategori aktif dan rentang tanggal berlaku berdasarkan WIB. `valid_from` dan `valid_until` berformat `YYYY-MM-DD`, batas tanggal termasuk, atau `NULL` bila tanpa batas. Slug kategori dipakai pada filter `kategori`; ID layanan dipakai pada parameter `layanan` untuk detail.

Database awal tidak diisi layanan contoh agar persyaratan/biaya rekaan tidak dianggap resmi. Sebelum CRUD admin tersedia, konten resmi hanya dapat diisi oleh pengelola teknis melalui SQLite dengan foreign key aktif. Halaman langsung membaca perubahan saat dimuat ulang. Pengaturan transaksi tersedia di admin; CRUD konten tetap menyusul. Biaya atau persyaratan kosong ditandai belum diinformasikan, bukan dianggap gratis atau tanpa dokumen. Tautan kanal resmi hanya menerima URL HTTP/HTTPS tanpa kredensial dan dibuka di tab baru.

Validasi katalog tanpa mengubah database aplikasi: `php tests/service-catalog.php`.

Menu **Persyaratan & biaya** (`/kiosk.php?menu=syarat`) menampilkan tabel referensi dengan kolom pengurusan dokumen, daftar syarat, nominal rupiah, serta dasar hukum/Perda dan tautan sumber. Di layar kecil setiap baris menjadi kartu. Semua isi langsung terlihat tanpa membuka detail, checklist, atau transaksi. Data berasal dari tabel SQLite `document_guides` (migrasi `004_document_guides`), terpisah dari katalog pengajuan; parameter lama `layanan`/`kategori` pada menu syarat tidak lagi memfilter data. Menu **Layanan administrasi** tetap memakai alur detail → pengajuan. Pengelola harus menyelaraskan ketentuan yang terkait pada kedua master data.

Jalankan `php bin/seed-demo-document-guides.php` untuk memuat empat contoh pengurusan wilayah **Kabupaten Serdang Bedagai** dari `database/seeds/demo-document-guides.sql`. Seed aman dijalankan ulang tanpa menimpa perubahan admin. Semua syarat, Rp0, dan Rp10.000 adalah **data simulasi**, bukan ketentuan resmi atau dasar pungutan; tidak ada nomor Perda rekaan. Produksi wajib memakai persyaratan dan tarif dari instansi berwenang.

Admin → **Persyaratan & biaya** (`/admin/dashboard.php?module=document-guides`) dapat menambah/mengedit nama pengurusan, wilayah, syarat (satu dokumen per baris), nominal biaya, catatan biaya, dasar hukum (nomor/tahun/pasal/lampiran), URL sumber, urutan, status DEMO dan publikasi. Kosong berarti biaya belum diketahui, sedangkan `0` adalah nominal nol yang tetap perlu dasar hukum untuk data resmi. Data non-DEMO hanya dapat tayang setelah admin menandai verifikasi dengan nominal, dasar hukum dan URL sumber yang lengkap. Ini merupakan konfirmasi pengelola, bukan verifikasi hukum otomatis. Nonaktifkan publikasi untuk menarik data; perubahan memakai CSRF, deteksi konflik versi, dan audit SQLite. Panduan ini tidak membatasi dasar hukum hanya pada Perda karena kewenangan/tarif dapat mengikuti ketentuan lain. Uji regresi: `php tests/document-guides.php`.

Untuk mengisi contoh pengujian secara opsional, jalankan `php bin/seed-demo-services.php`. Perintah ini menambahkan tiga kategori, enam layanan terpublikasi, dan dua persyaratan per layanan (wajib/opsional), semuanya berlabel **DEMO**, bukan informasi resmi. Menjalankan ulang tidak menggandakan atau menimpa data dengan slug yang sudah ada. Data demo tidak boleh digunakan sebagai dasar pengurusan layanan; hapus atau ganti dengan data terverifikasi sebelum penggunaan publik sebenarnya.

### Pengajuan, kamera, QR, printer, dan tindak lanjut

Terapkan `php bin/migrate.php` untuk migrasi `003_service_submissions`. PHP membutuhkan `PDO_SQLite`, `fileinfo`, dan `gd` (foto direkode JPEG untuk membuang metadata). Pastikan server dapat menulis `database/` dan `storage/uploads/`, tetapi document root hanya `public/`. Jangan sajikan direktori proyek melalui web.

Alur masyarakat:

1. Detail layanan → **Cetak panduan & persyaratan** atau **QR panduan di HP**. QR berisi alamat detail situs; HP harus dapat mengakses alamat tersebut. Domain lokal `.test` tidak otomatis tersedia dari internet.
2. **Mulai pengajuan** (hanya bila diaktifkan) → baca privasi dan setujui penggunaan data → isi uraian dan data minimum → ambil foto bila diperlukan.
3. Kamera dibuka atas tindakan pengguna melalui HTTPS/localhost, dengan izin perangkat. Pilih jenis foto, ambil foto, periksa pratinjau, lalu **Gunakan foto**, **Ulangi**, atau **Hapus foto**. Maksimal 3 foto dokumen dan 1 foto wajah jika diwajibkan, 2 MB per foto; unggahan JPEG/PNG maksimal 2500 piksel per sisi dan 4 megapiksel. Foto dokumen tetap opsional; dokumen wajib yang tidak difoto harus dibawa ke petugas. Foto wajah diambil eksplisit, bukan otomatis, dan tidak melakukan verifikasi biometrik.
4. **Periksa ringkasan** → periksa isian/foto → centang konfirmasi → kirim. Transaksi SQLite menyimpan tiket, riwayat awal, dan metadata foto. Kunci formulir unik mencegah tiket ganda saat POST diulang. Kegagalan tidak menampilkan bukti berhasil.
5. Bukti menampilkan kode tiket acak, PIN 6 digit, QR, layanan, tanggal, dan status. QR memakai format `KIOSK:V1:<20 karakter hex>` dan tidak menyertakan PIN/data pribadi. PIN disimpan sebagai hash di database; simpan cetakan secara pribadi karena PIN tercetak di bukti.
6. **Cetak / cetak ulang bukti** membuka dialog cetak browser tanpa membuat pengajuan baru. Aplikasi tidak dapat menjamin kertas tercetak; jika printer gagal, kode tiket tetap tersimpan. Catat tiket/PIN sebelum mengakhiri sesi.
7. **Scan / cek tiket** menerima scanner USB yang bertindak sebagai keyboard (fokus kolom kode, scanner mengirim Enter), kamera QR, atau input manual. Scanner hanya menerima format tiket aplikasi, bukan menavigasi URL sembarang. PIN tetap wajib; hasil memuat status, riwayat/catatan publik, serta surat final dan nomor pengambilan jika sudah selesai. Hasil tidak memuat NIK, tanggal lahir, nama/kontak, foto atau catatan internal; isi PDF mengikuti surat yang disahkan admin. Batas percobaan per IP: 10 lookup / 15 menit; pengajuan 5 / 15 menit (termasuk simulasi). Perangkat yang berbagi IP turut berbagi batas.

Admin:

- **Pengajuan layanan**: daftar berpaginasi, filter status, detail, foto privat melalui endpoint berautentikasi, dan riwayat. Catatan publik serta internal terpisah.
- Transisi: Baru → Ditinjau → Diproses → Selesai; dari Ditinjau/Diproses dapat meminta informasi; Ditolak dan Perlu informasi wajib alasan publik. Status akhir tidak dibuka ulang. Konflik pembaruan ditolak dan perubahan dicatat dalam audit.
- **Layanan administrasi**: pengaturan `accepts_submissions`, `requires_name`, `requires_contact`, `camera_enabled`, `requires_nik`, `requires_birth_date`, `requires_face`, `retention_days` (1–365 hari). Default layanan baru: informasi saja, kamera mati, nama/kontak opsional, identitas tambahan/foto wajah tidak diminta, retensi 30 hari. Aktifkan transaksi hanya untuk layanan berwenang dan minta identitas tambahan hanya jika dasar pengelolaannya jelas. Retensi baru berlaku pada pengajuan berikutnya; snapshot nama layanan dan `is_demo` tersimpan pada tiket.
- Enam layanan dummy yang telah di-seed diaktifkan untuk simulasi oleh migrasi. Gunakan data/dokumen contoh saja. Tidak ada pengajuan dikirim ke instansi eksternal.

Lampiran disimpan dengan nama acak di `storage/uploads/` (privat, di luar web root). Setelah batas retensi, pengajuan/lampiran tidak dapat diakses lagi. Jadwalkan **setiap hari** `php bin/purge-submissions.php` untuk menghapus berkas, pengajuan, dan riwayat kedaluwarsa. Kegagalan cleanup menghasilkan exit nonzero untuk monitoring. Backup harus memiliki aturan retensi/penghapusan sendiri. Sebelum produksi, instansi wajib menyetujui dasar pemrosesan, retensi, dan prosedur operasional.

### Pengajuan terpisah, identitas, surat final & antrean pengambilan

Terapkan `php bin/migrate.php` untuk migrasi `008_application_identity_letters`.

- **Layanan administrasi** (`menu=layanan`) tetap katalog informasi/panduan. **Pengajuan layanan** (`menu=pengajuan`) langsung menampilkan pilihan layanan terpublikasi yang menerima pengajuan, bukan membuka katalog yang sama. Pilihan mengarah langsung ke formulir `menu=pengajuan&layanan=ID`.
- Nama/kontak mengikuti kewajiban per layanan. NIK hanya ditampilkan/disimpan jika `requires_nik = 1`, wajib tepat 16 digit (bukan verifikasi Dukcapil). Tanggal lahir hanya diminta jika `requires_birth_date = 1`, format `YYYY-MM-DD`, tanggal kalender valid 1900 sampai hari ini WIB. Pengiriman NIK/tanggal lahir yang tidak diminta ditolak server. Keyboard touchscreen mendukung input identitas; kolom tanggal memakai teks berformat agar bisa diisi keyboard tersebut.
- `requires_face = 1` mewajibkan satu **Foto wajah pemohon**, dipilih pada jenis foto lalu diambil melalui kamera dan **Gunakan foto**. Kamera depan diprioritaskan untuk wajah, belakang untuk dokumen; pilihan bergantung perangkat. Validasi server mewajibkan lampiran JPEG yang direkode dan disimpan privat. Aplikasi tidak bisa membuktikan gambar benar-benar wajah/orang hidup; tidak ada pengenalan wajah/liveness/verifikasi NIK otomatis. Jika kamera wajib gagal, hubungi petugas; tidak ada bypass diam-diam.
- Enam layanan DEMO meminta nama, tanggal lahir dan foto wajah; DEMO legalisasi juga meminta NIK. Semua wajib menggunakan data dan gambar contoh, bukan identitas pribadi asli. Layanan yang sudah diedit melalui admin tidak ditimpa oleh seed yang dijalankan ulang.
- **Selesai** kini wajib disertai unggahan PDF final maksimal 5 MB dan konfirmasi admin bahwa surat telah diperiksa/disahkan. Upload hanya dari admin berwenang, MIME/magic PDF diperiksa; ini bukan antivirus, validasi tanda tangan, atau mesin penerbit surat. Jangan unggah PDF aktif/tidak tepercaya. Surat DEMO wajib diberi tanda simulasi pada isi PDF oleh admin. PDF tidak bisa diganti setelah status akhir.
- Dalam transaksi yang sama, `submission_letters` menyimpan berkas privat, petugas, tanggal dan nomor **`L<service_id>-<urutan>`**, misalnya `L1-000001`. `service_pickup_sequences` mengalokasikan urutan unik per layanan. Nomor baru dibuat ketika surat final selesai, bukan saat pengajuan. Tidak reset harian dan tidak diklaim sebagai janji waktu, antrean realtime, atau sistem panggilan loket. Konfirmasikan jadwal/loket kepada petugas; nomor pengambilan surat ini berbeda dari nomor antrean DEMO pada menu Antrean & janji layanan. Konflik, kegagalan surat/audit membatalkan status dan alokasi nomor.
- Pemohon **cek tiket + PIN** setelah selesai untuk melihat nomor pengambilan, mencetak nomor dan membuka PDF final. PDF dibuka di penampil browser pada tab baru; gunakan tombol cetak penampil. Endpoint `/surat.php` memerlukan sesi publik aktif dan izin akses maksimal 5 menit dari cek PIN terakhir, memeriksa status Selesai dan retensi pada setiap permintaan, tanpa PIN di URL. Akhiri sesi dan tutup tab PDF setelah mencetak. Data yang sudah ditampilkan/diunduh browser tidak dapat ditarik kembali oleh server.
- PDF dan foto wajah ikut penghapusan retensi. NIK/tanggal lahir hanya tersedia pada detail admin, tidak dimasukkan ke QR, log audit, atau receipt publik. Database dan backup memerlukan pembatasan akses, enkripsi perangkat/backup serta kebijakan data instansi; implementasi tidak mengenkripsi per kolom.

Uji regresi identitas, kewajiban foto, surat, alokasi nomor, konflik dan rollback: `php tests/submissions.php`.

Halaman **Status pengajuan** (`menu=status`) menyediakan aksi **Scan QR dengan kamera** dan **Ketik nomor pengajuan**, serta scanner USB/HID yang mengirim Enter. Nomor pengajuan adalah kode tiket 20 karakter pada bukti (QR memakai `KIOSK:V1:`), bukan nomor antrean pengambilan. Semua metode tetap memerlukan PIN enam digit dan pembatasan percobaan. Timeline menampilkan riwayat aktual secara kronologis, waktu WIB sampai detik, catatan publik, serta penanda terbaru; tidak mengarang persentase penyelesaian atau tahapan yang belum terjadi. Cek ulang untuk mengambil pembaruan, bukan polling otomatis.

Saat kamera QR aktif, pratinjau menampilkan label kamera aktif, kotak panduan penempatan QR, instruksi, dan animasi garis pemindai. Area di luar kotak diredupkan tanpa memotong gambar kamera; decoder tetap membaca seluruh gambar. Animasi dinonaktifkan jika perangkat memakai `prefers-reduced-motion`. Pratinjau disembunyikan dan kamera dihentikan saat tiket terbaca, scanner dimatikan, beralih ke input manual, halaman tidak aktif, atau sesi berakhir.

Jika surat final sudah diterbitkan, hasil menyediakan **Unduh PDF surat** (respons attachment), **Buka / cetak PDF surat** (penampil PDF tab baru), dan cetak nomor pengambilan. Kedua akses PDF memakai otorisasi sesi/retensi yang sama; parameter `download=1` hanya mengubah Content-Disposition, bukan melewati pemeriksaan. Tidak ada akses unduh foto wajah/dokumen mentah atau catatan internal. Percobaan cek status berikutnya menghapus izin surat sebelumnya sebelum memvalidasi kredensial. Setelah mengunduh/mencetak, tutup tab PDF dan hapus file unduhan dari perangkat bersama sesuai prosedur pengelola.

QR dibuat dan dipindai secara lokal memakai `qrcode` dan `jsQR`; tidak mengirim dokumen ke layanan QR eksternal. Bundle tersedia di `public/assets/js/service-devices.js`. Untuk memperbarui dari sumber `resources/js/service-devices.js`: `npm ci && npm run build`. Build menyertakan lisensi dependency pada `public/assets/js/service-devices.LICENSE.txt`; distribusikan bersama bundle. Printer browser biasa menggunakan dialog, bukan silent-print; thermal/silent-print dan scanner non-keyboard memerlukan konfigurasi perangkat/driver di luar aplikasi.

Uji regresi: `php tests/service-catalog.php`, `php tests/submissions.php`, dan `npm test` (129 variasi QR tiket). Uji penerimaan perangkat nyata tetap diperlukan untuk izin kamera, fokus scanner USB, ukuran kertas/driver printer, suara, dan domain yang dapat dijangkau HP.

### Pengaduan & aspirasi

`kiosk.php?menu=pengaduan` menerima laporan langsung ke SQLite, terpisah dari pengajuan layanan. Jalankan `php bin/migrate.php` untuk migrasi `009_complaints.sql`. Form meminta jenis (pengaduan/aspirasi), kategori, judul dan uraian; lokasi, nama dan kontak opsional. Tanpa nama/kontak diperbolehkan, tetapi bukan jaminan anonimitas jaringan: pembatasan percobaan memakai hash IP dan server dapat memiliki log akses. Tidak meminta NIK, foto, atau lampiran. Isian dibatasi server (judul 160, lokasi 240, nama/kontak 120, uraian 3000 byte); checkbox privasi dan pemeriksaan isi wajib.

Pengiriman memakai CSRF, request key sesi dan transaksi idempoten. Bukti hanya tersedia pada sesi pengirim, memuat kode `AD-` + 20 karakter hex dan PIN enam digit yang dapat dicetak melalui dialog browser. PIN disimpan sebagai hash. Menu memakai dua tab navigasi terpisah: **Tulis laporan** (`menu=pengaduan`) dan **Cek tanggapan** (`menu=pengaduan&tab=tanggapan`). Hanya formulir tab aktif yang ditampilkan; pengecekan tidak ditumpuk di bawah formulir laporan. Pindah tab memuat halaman baru tanpa menyimpan draf. Hasil/error pencarian tetap di tab tanggapan, bukan Status pengajuan layanan. Pencarian wajib kode+PIN, hanya mengembalikan jenis/kategori, status dan riwayat tanggapan publik; tidak menampilkan kembali identitas, isi awal laporan, lokasi, atau catatan internal. Tidak ada daftar pengaduan publik. Batas per IP berbagi dengan pengajuan layanan: 5 pengiriman / 15 menit dan 10 pencarian / 15 menit. Semua halaman bersifat no-store; sesi berakhir membersihkan formulir, bukti dan hasil.

Admin membuka `admin/dashboard.php?module=complaints` untuk daftar/filter berhalaman, detail laporan, tanggapan publik dan catatan internal terpisah. Alur: Diterima → Ditinjau → Ditindaklanjuti → Selesai; Diterima/Ditinjau/Ditindaklanjuti dapat ditutup sebagai Tidak dapat ditindaklanjuti dengan alasan publik. Saat Ditinjau/Ditindaklanjuti admin dapat menambah tanggapan tanpa mengubah status. Setiap pembaruan wajib tanggapan, memakai pemeriksaan versi untuk mencegah penimpaan bersamaan, dan mencatat audit tanpa isi laporan/identitas. Status akhir tidak dapat diubah.

Retensi **180 hari sejak dikirim**, termasuk laporan selesai. Akses publik dan admin diblokir setelah kedaluwarsa. Jadwalkan `php bin/purge-submissions.php` setiap hari: kini juga menghapus pengaduan/aspirasi kedaluwarsa beserta riwayatnya. Perintah ini tidak memasang cron secara otomatis; pengelola harus menjadwalkannya dan memantau kegagalan. Backup/log perlu kebijakan retensi tersendiri. Database belum terenkripsi per kolom, sehingga kontrol akses perangkat dan backup tetap wajib.

Ini bukan kanal darurat dan tidak terintegrasi otomatis dengan SP4N-LAPOR!, SMS/email, atau instansi lain. Tersimpan bukan berarti sudah ditinjau, diteruskan, atau dijamin selesai pada waktu tertentu. Pengelola wajib menetapkan petugas, dasar pemrosesan data dan prosedur tindak lanjut sebelum penggunaan operasional. Tidak ada dummy pengaduan yang disisipkan ke database produksi. Uji regresi: `php tests/complaints.php`.

### Antrean & janji lintas instansi (DEMO lokal)

`kiosk.php?menu=antrean` memiliki tiga tab terpisah: **Antrean hari ini**, **Janji layanan**, dan **Cek antrean**. Alur: pilih instansi → pilih layanan → pilih slot tanggal/jam WIB → konfirmasi DEMO → terima dan cetak bukti melalui dialog browser. Tidak meminta nama, NIK, nomor BPJS, kontak, atau data medis. Pilihan instansi/layanan hanya yang aktif. Slot menampilkan sisa kuota, jumlah menunggu dan nomor yang sedang dipanggil berdasarkan data lokal saat halaman dimuat. Tidak ada estimasi waktu tunggu yang dibuat-buat, notifikasi, polling otomatis, atau integrasi speaker.

Jalankan `php bin/migrate.php` untuk migrasi `010_queues.sql`, lalu `php bin/seed-demo-queues.php` untuk contoh **4 instansi** (Disdukcapil, puskesmas, rumah sakit, dinas perizinan), **7 layanan**, kuota 10 per jadwal, hari ini dan 7 hari berikutnya. Seed mencocokkan nama instansi/nama layanan dan tanggal/jam mulai, tidak menimpa baris yang sudah cocok. Jika nama master diubah lalu seed diulang, nama contoh awal dapat dibuat lagi. Jam hari ini 08:00–23:59 dan tanggal berikutnya 08:00–11:00 hanya untuk kemudahan demonstrasi, **bukan jam operasional resmi**. Akhir pekan/libur juga hanya contoh. Seed tidak berjalan otomatis setiap hari; gunakan admin untuk membuat jadwal berikutnya. Hanya tanggal hari ini sampai 30 hari ke depan yang dapat dipesan; hari ini harus belum melewati jam selesai. Janji dimulai dari besok.

Admin `admin/dashboard.php?module=queues` menyediakan:
- **Instansi:** tambah/edit nama, jenis, deskripsi, alamat contoh, aktif/nonaktif.
- **Layanan:** tambah/edit nama, petunjuk/persyaratan contoh, aktif/nonaktif; instansi layanan tidak dapat dipindahkan.
- **Jadwal & kuota:** tanggal, jam mulai/selesai, kuota 1–500, buka/tutup, filter tanggal. Jadwal satu layanan tidak boleh tumpang tindih. Tanggal/jam/layanan pada jadwal tersimpan tidak dapat dipindahkan; tutup jadwal dan batalkan pemesanan secara eksplisit jika perlu penjadwalan ulang. Kuota tidak dapat diturunkan di bawah jumlah terisi. Perubahan slot memakai versi untuk mencegah penimpaan data bersamaan.
- **Panggilan & pemesanan:** daftar berhalaman menurut tanggal/ID slot, panggil nomor menunggu paling awal, selesaikan, tandai tidak hadir, atau batalkan. Satu nomor dipanggil per slot. Alur Menunggu → Dipanggil → Selesai/Tidak hadir; Menunggu/Dipanggil dapat Dibatalkan. Panggilan/penyelesaian hanya pada tanggal layanan setelah jam mulai. Status akhir tidak dapat diubah. Pembatalan oleh admin mengembalikan kuota; selesai/tidak hadir tetap memakai kuota. Belum ada pembatalan mandiri atau janji ulang otomatis.

Pemesanan memakai CSRF, request key sesi, PIN hash, transaksi SQLite, pemeriksaan status aktif/jam/kuota di server, dan alokasi atomik. Dua proses berebut kuota terakhir hanya menghasilkan satu pemesanan. Pengiriman ulang request key yang sama tidak mengurangi kuota lagi. Nomor tampilan **`Q<slot_id>-<urutan>`**, misalnya `Q1-001`, unik per jadwal, bukan urutan global/harian per instansi. Nomor yang dibatalkan tidak dipakai ulang. Kode rahasia **`AN-` + 20 karakter hex** dan PIN enam digit dipakai pada tab **Cek antrean**; kode berbeda dari tiket pengajuan dan laporan. PIN hanya terlihat pada bukti sesi pengirim, bukan pada hasil lookup/admin. Tidak ada pencarian terbuka berdasarkan nomor urut saja. Batas percobaan per IP berbagi dengan transaksi lain: 5 pengiriman/15 menit dan 10 lookup/15 menit. Tanpa data identitas, sistem tidak dapat mencegah seseorang memesan beberapa kali dengan formulir baru selain pembatasan tersebut.

Bukti/riwayat dapat dicek sampai **30 hari setelah akhir tanggal layanan WIB**. Jadwalkan `php bin/purge-submissions.php` setiap hari untuk menghapus booking kedaluwarsa beserta event; jadwal dan penghitung nomor tetap dipertahankan sebagai data operasional. Perintah tidak mengatur cron. Halaman no-store, tidak menyimpan booking pada localStorage/IndexedDB; akhiri sesi dan amankan salinan cetak/unduhan di perangkat bersama. Penonaktifan instansi/layanan atau penutupan slot hanya menghentikan booking baru, tidak membatalkan booking lama. Nama/alamat/petunjuk pada bukti mengikuti master terkini.

**Semua nomor dan janji tetap DEMO meskipun nama master diedit**, tidak berlaku pada Disdukcapil, fasilitas kesehatan, atau dinas sebenarnya. Tidak ada API antrean resmi, konfirmasi instansi, jaminan waktu panggilan, triase medis, SMS/email, QR check-in, atau pembayaran. Untuk kondisi darurat jangan gunakan modul ini. Integrasi resmi dan kesepakatan operasional diperlukan sebelum reservasi nyata dapat diterbitkan. Uji `php tests/queues.php` mencakup batas input, idempotensi, kuota penuh, konflik versi, rollback, pembatalan/nomor, instansi nonaktif, tanggal, retensi, privasi, dan kompetisi dua proses.

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
