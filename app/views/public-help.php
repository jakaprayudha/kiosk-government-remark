<a class="back-link" href="kiosk.php">← Kembali ke beranda</a>
<article class="detail-panel help-page">
    <span class="menu-icon teal"><?= kiosk_icon('help') ?></span>
    <span class="detail-category">PANDUAN UNTUK SEMUA</span>
    <h1>Bantuan & cara menggunakan KIOSK</h1>
    <p class="detail-intro">Tidak perlu membuat akun. Sentuh menu yang Anda butuhkan dan ikuti petunjuk di layar. Jika bingung, mintalah bantuan petugas di lokasi.</p>
    <nav class="help-shortcuts" aria-label="Topik bantuan">
        <a class="footer-button" href="#help-start">Cara mulai</a>
        <a class="footer-button" href="#help-menus">Pilih menu</a>
        <a class="footer-button" href="#help-problems">Jika ada kendala</a>
        <a class="footer-button" href="#help-finish">Selesai menggunakan</a>
    </nav>

    <section id="help-start" class="help-section" aria-labelledby="help-start-title">
        <h2 id="help-start-title">Mulai dengan 3 langkah</h2>
        <ol class="help-steps">
            <li><strong>Sentuh menu yang diperlukan.</strong><p>Cukup sentuh satu kali. Tunggu halaman terbuka. Geser layar ke atas untuk melihat bagian di bawah.</p></li>
            <li><strong>Baca petunjuk, lalu isi bila diminta.</strong><p>Sentuh kotak isian untuk mengetik. Pada halaman formulir, tombol <strong>Keyboard layar sentuh</strong> membuka papan ketik. Periksa isian sebelum menekan Kirim.</p></li>
            <li><strong>Simpan bukti, lalu akhiri sesi.</strong><p>Jika mendapat kode dan PIN, simpan secara pribadi atau cetak buktinya. Setelah selesai, tekan <strong>Selesai & bersihkan sesi</strong> di bagian bawah layar.</p></li>
        </ol>
        <div class="help-tip"><strong>Tulisan terlalu kecil?</strong> Tekan <strong>A+ Teks besar</strong> di bagian bawah layar. Tekan lagi untuk kembali ke ukuran biasa. Tombol <strong>Beranda</strong> membawa Anda ke pilihan menu utama.</div>
        <p class="help-note">Keyboard perangkat juga dapat dipakai: tekan Tab untuk berpindah pilihan, Enter untuk membuka tombol/tautan, dan tombol panah untuk memilih pada daftar. Untuk membuka pertanyaan bantuan di bawah, fokuskan judulnya lalu tekan Enter atau Spasi.</p>
    </section>

    <section id="help-menus" class="help-section" aria-labelledby="help-menus-title">
        <h2 id="help-menus-title">Saya perlu memilih menu yang mana?</h2>
        <p>Sentuh judul pertanyaan untuk membuka panduannya.</p>
        <div class="help-questions">
            <?php foreach ([
                ['layanan', 'Ingin tahu layanan yang tersedia?', 'Layanan administrasi', 'Baca penjelasan layanan, persyaratan, dan cara mengurusnya. Ini adalah halaman informasi, bukan formulir pengajuan.'],
                ['syarat', 'Ingin tahu syarat dan biaya dokumen?', 'Persyaratan & biaya', 'Cari jenis dokumen, lalu baca daftar syarat, biaya, dan dasar hukumnya. Perhatikan label DEMO: biaya contoh bukan tarif resmi. Jika ragu, konfirmasikan kepada petugas.'],
                ['kantor', 'Ingin melihat informasi kantor dan peta?', 'Informasi kecamatan', 'Baca profil kecamatan dan informasi desa/fasilitas. Sentuh titik pada peta untuk membuka keterangannya. Anda dapat memilih peta jalan atau satelit dan memperbesar peta. Data/lokasi DEMO hanya contoh, bukan petunjuk lokasi yang sudah diverifikasi.'],
                ['pengajuan', 'Ingin mengajukan layanan?', 'Pengajuan layanan', 'Pilih layanan, isi data yang diminta, dan ambil foto jika diwajibkan. Periksa ringkasan sebelum mengirim. Simpan kode tiket 20 karakter dan PIN enam digit pada bukti. Surat final belum langsung tersedia: admin harus memeriksa dan menyelesaikan layanan terlebih dahulu.'],
                ['status', 'Sudah mengajukan layanan dan ingin melihat hasil?', 'Cek status pengajuan', 'Scan QR pada bukti atau ketik kode tiket, lalu masukkan PIN enam digit. Lihat riwayat proses dan catatan petugas. Jika surat final sudah tersedia, Anda dapat mengunduh atau membuka PDF untuk dicetak. Nomor pengambilan surat baru tersedia setelah layanan selesai.'],
                ['pengaduan', 'Ingin melapor atau menyampaikan saran?', 'Pengaduan & aspirasi', 'Buka tab Tulis laporan. Pilih Pengaduan untuk masalah atau Aspirasi untuk ide dan saran. Nama dan kontak boleh dikosongkan. Setelah mengirim, simpan kode berawalan AD- dan PIN. Untuk membaca balasan petugas, buka tab Cek tanggapan pada menu yang sama.'],
                ['antrean', 'Ingin mencoba mengambil antrean atau membuat janji?', 'Antrean & janji layanan', 'Pilih tab Antrean hari ini atau Janji layanan, lalu pilih instansi, layanan, dan jadwal yang masih memiliki kuota. Simpan bukti, kode berawalan AN-, dan PIN. Gunakan tab Cek antrean untuk melihat pembaruan. Seluruh antrean/janji masih DEMO lokal, tidak berlaku di instansi sebenarnya.'],
            ] as [$menu, $question, $label, $answer]): ?>
                <details>
                    <summary><?= kiosk_escape($question) ?></summary>
                    <div class="help-answer"><p><?= kiosk_escape($answer) ?></p><a class="footer-button" href="kiosk.php?menu=<?= $menu ?>">Buka <?= kiosk_escape($label) ?> →</a></div>
                </details>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="help-section" aria-labelledby="help-code-title">
        <h2 id="help-code-title">Jangan tertukar: setiap bukti punya tempat pengecekan</h2>
        <div class="help-code-grid">
            <div><h3>Pengajuan layanan</h3><p>Kode tiket <strong>20 karakter</strong> + PIN.</p><a href="kiosk.php?menu=status">Buka Cek status pengajuan →</a></div>
            <div><h3>Pengaduan / aspirasi</h3><p>Kode berawalan <strong>AD-</strong> + PIN.</p><a href="kiosk.php?menu=pengaduan&amp;tab=tanggapan">Buka Cek tanggapan →</a></div>
            <div><h3>Antrean / janji DEMO</h3><p>Kode berawalan <strong>AN-</strong> + PIN.</p><a href="kiosk.php?menu=antrean&amp;tab=cek">Buka Cek antrean →</a></div>
        </div>
        <p class="help-note">PIN adalah enam angka rahasia pada bukti. QR pengajuan hanya berisi kode tiket, bukan PIN. Nomor urut antrean dan nomor pengambilan surat bukan pengganti kode pribadi dan PIN.</p>
    </section>

    <section id="help-problems" class="help-section" aria-labelledby="help-problems-title">
        <h2 id="help-problems-title">Jika ada kendala</h2>
        <div class="help-questions">
            <details><summary>Bagaimana cara scan QR dengan kamera?</summary><div class="help-answer"><ol><li>Buka Cek status pengajuan, lalu sentuh <strong>Scan QR dengan kamera</strong>.</li><li>Izinkan kamera jika perangkat meminta izin.</li><li>Tempatkan seluruh QR tiket di dalam kotak hijau. Tahan sebentar sampai terbaca.</li><li>Masukkan PIN enam digit, lalu sentuh <strong>Cek status</strong>.</li></ol><p>Jika kamera tidak bisa digunakan, pilih <strong>Ketik nomor pengajuan</strong>. Jika tersedia scanner USB, sentuh kolom kode terlebih dahulu, lalu scan bukti. Gunakan QR bukti pengajuan, bukan QR dari halaman informasi layanan.</p></div></details>
            <details><summary>Bagaimana mengambil foto untuk pengajuan?</summary><div class="help-answer"><p>Pilih jenis foto yang diminta, lalu aktifkan kamera. Arahkan dokumen agar jelas, atau posisikan wajah jika layanan meminta foto wajah. Sentuh <strong>Ambil foto</strong>, periksa hasilnya, lalu pilih <strong>Gunakan foto</strong>. Jika kurang jelas, ulangi. Jangan memakai data atau foto identitas asli pada layanan berlabel DEMO.</p><p>Jika foto diwajibkan tetapi kamera gagal, pengajuan tidak dapat dikirim tanpa foto. Mintalah bantuan petugas; jangan mengirim foto yang tidak sesuai.</p></div></details>
            <details><summary>Bagaimana mencetak bukti atau surat?</summary><div class="help-answer"><p>Sentuh tombol <strong>Cetak</strong> pada bukti. Pilih printer pada jendela cetak, lalu periksa hasil kertasnya. Untuk surat final, pilih <strong>Buka / cetak PDF surat</strong>, lalu gunakan tombol cetak pada penampil PDF.</p><p>Jika kertas tidak keluar, jangan menekan cetak berulang kali. Mintalah petugas memeriksa kertas, sambungan, dan printer. Aplikasi tidak bisa memastikan kertas benar-benar tercetak. Tutup tab PDF setelah selesai.</p></div></details>
            <details><summary>Kode atau PIN tidak cocok, atau bukti hilang?</summary><div class="help-answer"><p>Pastikan Anda membuka menu pengecekan yang benar. Ketik kode lengkap dan PIN enam digit dari bukti; jangan memakai nomor urut antrean. Jika masih gagal atau bukti hilang, temui petugas. KIOSK tidak menyediakan pemulihan PIN mandiri.</p><p>Data yang melewati masa penyimpanan tidak dapat dicek lagi. Jika muncul pesan terlalu banyak percobaan, tunggu 15 menit atau minta bantuan petugas.</p></div></details>
            <details><summary>Sudah menekan Kirim, tetapi belum ada bukti berhasil?</summary><div class="help-answer"><p>Tunggu halaman selesai. Jangan menganggap laporan, pengajuan, atau janji berhasil jika belum ada konfirmasi dan kode bukti. Jika layar menampilkan kesalahan atau koneksi terputus, catat pesannya dan minta bantuan petugas sebelum mengisi ulang agar tidak mengirim dua kali.</p></div></details>
            <details><summary>Mengapa surat belum tersedia atau tanggapan belum ada?</summary><div class="help-answer"><p>Data yang tersimpan masih perlu diperiksa petugas. Buka menu pengecekan yang sesuai untuk membaca status dan catatan terakhir. Surat final hanya muncul setelah admin menyelesaikan layanan dan menerbitkan PDF. Pengaduan tidak langsung mendapat balasan otomatis. Hubungi petugas untuk memastikan tindak lanjut; KIOSK tidak menjamin waktu penyelesaian.</p></div></details>
            <details><summary>Apa arti label DEMO atau SIMULASI?</summary><div class="help-answer"><p>Artinya data atau layanan tersebut hanya contoh untuk mencoba aplikasi. Jangan memakai identitas asli. Nomor antrean dan janji DEMO <strong>tidak berlaku</strong> di Disdukcapil, puskesmas, rumah sakit, atau dinas sebenarnya. Biaya, lokasi, jadwal, dan dokumen contoh bukan informasi resmi yang sudah diverifikasi.</p></div></details>
            <details><summary>Layar bertanya “Masih menggunakan KIOSK?”</summary><div class="help-answer"><p>Sentuh <strong>Ya, lanjutkan</strong> jika Anda masih menggunakan layar. Jika tidak ada kegiatan selama sekitar dua menit, peringatan akan muncul dengan hitungan 20 detik. Bila tidak dilanjutkan, sesi diakhiri dan layar kembali ke beranda. Isian yang belum dikirim perlu diisi ulang.</p></div></details>
        </div>
    </section>

    <section id="help-finish" class="help-section" aria-labelledby="help-finish-title">
        <h2 id="help-finish-title">Sebelum meninggalkan KIOSK</h2>
        <ol class="help-steps">
            <li><strong>Ambil bukti dan dokumen Anda.</strong><p>Jangan meninggalkan kertas berisi kode, PIN, atau data pribadi di printer.</p></li>
            <li><strong>Tutup surat atau PDF yang dibuka.</strong><p>Jika mengunduh file di perangkat bersama, minta bantuan petugas untuk menghapus salinannya setelah digunakan. Mengakhiri sesi tidak menghapus file unduhan atau kertas yang sudah dicetak.</p></li>
            <li><strong>Tekan Selesai & bersihkan sesi.</strong><p>Tombol ini ada di bagian bawah layar. Pastikan layar kembali ke beranda. Tindakan ini membersihkan sesi penggunaan, bukan menghapus pengajuan atau laporan yang sudah tersimpan. Jika gagal, jangan tinggalkan layar; hubungi petugas.</p></li>
        </ol>
        <div class="help-tip"><strong>Masih membutuhkan pendampingan?</strong><p>Silakan temui petugas di lokasi kecamatan. Halaman bantuan ini tidak memanggil petugas secara otomatis dan bukan kanal darurat.</p><a class="footer-button" href="kiosk.php?menu=kantor">Lihat informasi kecamatan →</a></div>
    </section>
</article>
