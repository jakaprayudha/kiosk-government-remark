<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/public-session.php';

$menus = [
    'layanan' => ['title' => 'Layanan administrasi', 'text' => 'Kenali layanan dan kanal pengurusan yang tepat.', 'icon' => 'document', 'tone' => 'blue', 'status' => 'Katalog layanan',
        'heading' => 'Kenali layanan sebelum mengurus',
        'intro' => 'Halaman ini menyediakan panduan umum. Daftar layanan resmi kecamatan belum dipublikasikan.',
        'steps' => ['Tentukan kebutuhan administrasi Anda dan tanyakan unit yang berwenang kepada petugas.', 'Pastikan layanan diproses oleh kecamatan atau instansi lain sebelum menyerahkan dokumen.', 'Untuk Dukcapil, bantuan sosial, pajak, atau pembayaran, ikuti kanal resmi instansi terkait.']],
    'syarat' => ['title' => 'Persyaratan & biaya', 'text' => 'Tabel syarat dokumen, biaya, dan dasar hukum pengurusan.', 'icon' => 'checklist', 'tone' => 'orange', 'status' => 'Tabel referensi',
        'heading' => 'Persiapan yang tepat, proses lebih jelas',
        'intro' => 'Persyaratan, biaya, dan estimasi berbeda untuk setiap layanan. Rincian resmi belum tersedia di KIOSK ini.',
        'steps' => ['Konfirmasikan dokumen wajib, dokumen tambahan, dan kebutuhan salinan kepada petugas.', 'Pastikan informasi biaya dan estimasi waktu berasal dari kanal resmi; jangan menganggap seluruh layanan gratis.', 'Jangan mengunggah atau menyerahkan data identitas kecuali diperlukan dan tujuan penggunaannya jelas.']],
    'kantor' => ['title' => 'Informasi kecamatan', 'text' => 'Profil kantor, peta titik desa, dan informasi wilayah.', 'icon' => 'building', 'tone' => 'teal', 'status' => 'Profil & peta desa',
        'heading' => 'Kenali kantor kecamatan Anda',
        'intro' => 'Profil kantor belum diisi oleh pengelola. Alamat, jam layanan, nomor telepon, dan peta belum tersedia.',
        'steps' => ['Periksa papan informasi resmi di kantor untuk jam dan lokasi layanan.', 'Hubungi petugas di lokasi untuk memastikan unit atau loket yang menangani kebutuhan Anda.', 'Kontak dan kanal resmi akan ditampilkan setelah data kecamatan dipublikasikan.']],
    'pengajuan' => ['title' => 'Pengajuan layanan', 'text' => 'Permintaan layanan dan bukti pengajuan.', 'icon' => 'inbox', 'tone' => 'purple', 'status' => 'Belum tersedia',
        'heading' => 'Pengajuan digital belum tersedia',
        'intro' => 'KIOSK belum menerima atau menyimpan pengajuan layanan. Tidak ada permintaan yang dikirim melalui halaman ini.',
        'steps' => ['Tanyakan proses pengajuan yang berlaku kepada petugas kecamatan.', 'Siapkan dokumen sesuai persyaratan resmi layanan yang dituju.', 'Kode tiket dan bukti digital akan tersedia setelah modul pengajuan diaktifkan.']],
    'status' => ['title' => 'Cek status pengajuan', 'text' => 'Lacak perkembangan layanan dengan kode tiket.', 'icon' => 'search', 'tone' => 'blue', 'status' => 'Belum tersedia',
        'heading' => 'Pelacakan tiket belum tersedia',
        'intro' => 'KIOSK belum menyediakan pencarian tiket. Jangan memasukkan kode tiket atau data pribadi di halaman lain yang tidak resmi.',
        'steps' => ['Simpan bukti pengajuan yang Anda peroleh melalui kanal resmi.', 'Tanyakan perkembangan pengajuan kepada unit yang menerima permintaan Anda.', 'Pencarian status dengan kode tiket akan tersedia setelah modul transaksi diaktifkan.']],
    'pengaduan' => ['title' => 'Pengaduan & aspirasi', 'text' => 'Panduan menyampaikan masukan Anda.', 'icon' => 'chat', 'tone' => 'green', 'status' => 'Belum tersedia',
        'heading' => 'Suara Anda penting',
        'intro' => 'Formulir pengaduan digital belum tersedia. Halaman ini tidak mengirim pengaduan atau memanggil petugas.',
        'steps' => ['Tanyakan kanal pengaduan resmi kepada petugas di lokasi.', 'Siapkan uraian yang jelas, lokasi kejadian, dan informasi pendukung yang relevan.', 'Hindari menyertakan data pribadi yang tidak diperlukan. Simpan bukti penyampaian pengaduan.']],
    'antrean' => ['title' => 'Antrean & janji layanan', 'text' => 'Panduan antrean dan jadwal konsultasi.', 'icon' => 'calendar', 'tone' => 'orange', 'status' => 'Belum tersedia',
        'heading' => 'Antrean digital belum tersedia',
        'intro' => 'Nomor antrean dan reservasi belum dapat dibuat dari KIOSK. Tidak ada jadwal yang dipesan melalui halaman ini.',
        'steps' => ['Tanyakan alur pengambilan nomor antrean kepada petugas atau loket penerima.', 'Pastikan layanan dan loket yang dituju sesuai dengan kebutuhan Anda.', 'Ikuti petunjuk menunggu dan panggilan antrean yang berlaku di lokasi.']],
    'bantuan' => ['title' => 'Bantuan penggunaan', 'text' => 'Panduan singkat menggunakan layar KIOSK.', 'icon' => 'help', 'tone' => 'teal', 'status' => 'Panduan KIOSK',
        'heading' => 'Kami bantu Anda memulai',
        'intro' => 'Gunakan layar sentuh, mouse, atau keyboard. Anda tidak perlu login untuk membaca informasi publik.',
        'steps' => ['Sentuh salah satu menu di beranda. Pada keyboard, gunakan Tab untuk berpindah dan Enter untuk membuka.', 'Gunakan tombol Teks besar untuk memperbesar tulisan. Tombol Beranda mengembalikan Anda ke menu utama.', 'Jika perlu pendampingan, temui petugas di lokasi. KIOSK belum memiliki tombol pemanggilan petugas. Setelah tidak aktif, layar akan kembali ke beranda.']],
];
$menus['pengajuan']['status'] = 'Tersedia sesuai layanan';
$menus['pengajuan']['text'] = 'Pilih layanan, isi formulir, lalu pantau surat dan nomor pengambilan.';
$menus['status']['status'] = 'Scan tiket + PIN';
$menus['pengaduan']['status'] = 'Kirim & cek tanggapan';
$menus['pengaduan']['text'] = 'Sampaikan laporan atau usulan, boleh tanpa nama.';
$menus['antrean']['status'] = 'Simulasi DEMO';
$menus['antrean']['text'] = 'Pilih instansi, antrean hari ini, atau janji layanan DEMO.';
$menus['bukti'] = ['title' => 'Bukti pengajuan', 'text' => '', 'icon' => 'document', 'tone' => 'blue', 'status' => ''];
$selected = $_GET['menu'] ?? '';
if (!is_string($selected) || ($selected !== '' && !isset($menus[$selected]))) {
    http_response_code(404);
    exit('Menu KIOSK tidak ditemukan. Silakan kembali ke /kiosk.php.');
}
$page = $selected === '' ? null : $menus[$selected];
$now = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
$announcements = [];
$announcementsError = '';
if ($selected === '') {
    require_once dirname(__DIR__) . '/app/database.php';
    require_once dirname(__DIR__) . '/app/announcements.php';
    try {
        $announcements = public_announcements(open_database(), $now);
    } catch (RuntimeException $error) {
        error_log('Pengumuman publik gagal dimuat: ' . get_class($error));
        http_response_code(503);
        $announcementsError = 'Pengumuman belum dapat dimuat. Silakan hubungi petugas untuk informasi terbaru.';
    }
}
$isTransaction = in_array($selected, ['pengajuan', 'status', 'bukti'], true);
if ($isTransaction) require dirname(__DIR__) . '/app/public-submissions-controller.php';
if ($selected === 'pengaduan') require dirname(__DIR__) . '/app/public-complaints-controller.php';
if ($selected === 'antrean') require dirname(__DIR__) . '/app/public-queues-controller.php';
$isCatalog = $selected === 'layanan';
$documentGuides = [];
$guidesError = '';
if ($selected === 'syarat') {
    require_once dirname(__DIR__) . '/app/database.php';
    require_once dirname(__DIR__) . '/app/document-guides.php';
    try {
        $documentGuides = public_document_guides(open_database());
    } catch (RuntimeException $error) {
        error_log('Panduan dokumen gagal dimuat: ' . get_class($error));
        http_response_code(503);
        $guidesError = 'Persyaratan dan biaya belum dapat dimuat. Silakan hubungi petugas.';
    }
}
$office = null;
$officeError = '';
if ($selected === 'kantor') {
    require_once dirname(__DIR__) . '/app/database.php';
    require_once dirname(__DIR__) . '/app/office.php';
    require_once dirname(__DIR__) . '/app/office-management.php';
    require_once dirname(__DIR__) . '/app/service-catalog.php';
    try {
        $officeSlug = kiosk_office_slug(open_database(), $_COOKIE['kiosk_device'] ?? null);
        if ($officeSlug === null) $officeError = 'Perangkat ini belum ditetapkan ke kecamatan. Hubungi admin pusat untuk menetapkan browser KIOSK melalui menu Profil kecamatan → Perangkat KIOSK.';
        else $office = public_office(open_database(), $officeSlug);
    } catch (DomainException $error) {
        $officeError = $error->getMessage();
    } catch (RuntimeException $error) {
        error_log('Profil kecamatan gagal dimuat: ' . get_class($error));
        http_response_code(503);
        $officeError = 'Profil dan desa belum dapat dimuat dari database. Hubungi petugas.';
    }
}
$catalogError = '';
$catalog = [];
$categories = [];
$service = null;
$requirements = [];
$category = $_GET['kategori'] ?? '';
$serviceId = null;
if ($isCatalog) {
    if (!is_string($category) || strlen($category) > 200) {
        http_response_code(400);
        exit('Filter kategori tidak valid.');
    }
    if (isset($_GET['layanan'])) {
        $serviceId = filter_var($_GET['layanan'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($serviceId === false) {
            http_response_code(404);
            exit('Layanan tidak ditemukan.');
        }
    }
    require_once dirname(__DIR__) . '/app/database.php';
    require_once dirname(__DIR__) . '/app/service-catalog.php';
    try {
        $pdo = open_database();
        $catalog = public_service_catalog($pdo, $now->format('Y-m-d'));
        foreach ($catalog as $entry) {
            $categories[$entry['category_slug']] = $entry['category_name'];
        }
        if ($serviceId !== null) {
            $matches = public_service_catalog($pdo, $now->format('Y-m-d'), '', $serviceId);
            $service = $matches[0] ?? null;
            if ($service === null) {
                http_response_code(404);
                $catalogError = 'Layanan tidak ditemukan atau tidak lagi dipublikasikan.';
            } else {
                $requirements = public_service_requirements($pdo, $serviceId);
                $page['title'] = $service['name'];
            }
        } elseif ($category !== '') {
            $catalog = array_values(array_filter($catalog, static fn(array $entry): bool => $entry['category_slug'] === $category));
        }
    } catch (RuntimeException $error) {
        error_log('Katalog layanan gagal dimuat: ' . $error->getMessage());
        http_response_code(503);
        $catalogError = 'Informasi layanan sementara tidak dapat dimuat. Silakan hubungi petugas atau coba kembali nanti.';
    }
}
function kiosk_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function kiosk_icon(string $name): string
{
    $paths = [
        'building' => '<path d="m3 9 9-6 9 6M5 10v10h14V10M3 21h18M9 11v6M15 11v6"/>',
        'document' => '<path d="M6 3h8l4 4v14H6ZM14 3v5h4M9 12h6M9 16h6"/>',
        'checklist' => '<rect x="4" y="4" width="16" height="17" rx="2"/><path d="m7 10 1 1 2-2M13 10h4m-10 6 1 1 2-2M13 16h4M9 2h6v4H9Z"/>',
        'inbox' => '<path d="M4 4h16v16H4ZM4 14h5l1 3h4l1-3h5M9 8h6"/>',
        'search' => '<circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/>',
        'chat' => '<path d="M3 4h18v13H10l-5 4v-4H3ZM7 9h10M7 13h6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 11h18M7 15h3M14 15h3"/>',
        'help' => '<circle cx="12" cy="12" r="9"/><path d="M9 9a3 3 0 1 1 4 3v2M12 17h.01"/>',
        'arrow' => '<path d="M5 12h14m-5-5 5 5-5 5"/>',
        'home' => '<path d="m3 10 9-7 9 7M5 9v12h14V9M9 21v-8h6v8"/>',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f7fc">
    <meta name="public-csrf" content="<?= kiosk_escape($_SESSION['csrf']) ?>">
    <title><?= $page ? kiosk_escape($page['title']) . ' — ' : '' ?>KIOSK Publik Kecamatan</title>
    <?php if ($selected === 'kantor' && $office !== null): ?>
        <link rel="stylesheet" href="assets/css/leaflet.css">
    <?php endif; ?>
    <link rel="stylesheet" href="assets/css/kiosk.css?v=<?= filemtime(__DIR__ . '/assets/css/kiosk.css') ?>">
    <script src="assets/js/kiosk.js?v=<?= filemtime(__DIR__ . '/assets/js/kiosk.js') ?>" defer></script>
    <script src="assets/js/service-devices.js?v=<?= filemtime(__DIR__ . '/assets/js/service-devices.js') ?>" defer></script>
    <?php if ($selected === 'kantor' && $office !== null): ?>
        <script src="assets/js/office-map.js?v=<?= filemtime(__DIR__ . '/assets/js/office-map.js') ?>" defer></script>
    <?php endif; ?>
    <?php if ($isTransaction || in_array($selected, ['pengaduan','antrean'], true)): ?><script src="assets/js/auth.js" defer></script><?php endif; ?>
</head>
<body>
    <a class="skip-link" href="#konten-kiosk">Lewati ke konten utama</a>
    <header class="kiosk-header">
        <a class="brand" href="kiosk.php" data-menu-voice="Beranda KIOSK" aria-label="Beranda KIOSK"><span class="brand-icon"><?= kiosk_icon('building') ?></span><span><strong>KIOSK<span>KECAMATAN</span></strong></span></a>
        <div class="public-label"><span></span> LAYANAN INFORMASI PUBLIK</div>
        <div class="clock"><time id="kiosk-clock" datetime="<?= kiosk_escape($now->format(DATE_ATOM)) ?>"><?= $now->format('H:i:s') ?> WIB</time><span id="kiosk-date"><?= $now->format('d/m/Y') ?></span></div>
    </header>
    <main id="konten-kiosk" class="kiosk-container">
        <?php if ($page === null): ?>
            <section class="welcome">
                <div><span class="eyebrow">SELAMAT DATANG DI KIOSK KECAMATAN</span><h1>Ada yang bisa kami bantu?</h1><p>Sentuh menu di bawah untuk menemukan informasi yang Anda butuhkan.</p><span class="welcome-note">Mudah digunakan <i></i> Tanpa perlu akun <i></i> Bahasa Indonesia</span></div>
                <div class="welcome-art" aria-hidden="true"><?= kiosk_icon('building') ?><span>INFORMASI LEBIH DEKAT</span></div>
            </section>
            <section class="menu-section" aria-labelledby="menu-title">
                <div class="section-heading"><h2 id="menu-title">Pilih kebutuhan Anda</h2><span>Mulai dengan menyentuh salah satu menu</span></div>
                <div class="menu-grid">
                    <?php foreach ($menus as $key => $item): ?>
                        <?php if ($key === 'bukti') continue; ?>
                        <a class="menu-card" href="kiosk.php?menu=<?= kiosk_escape($key) ?>" data-menu-voice="<?= kiosk_escape($item['title']) ?>"><span class="card-top"><span class="menu-icon <?= kiosk_escape($item['tone']) ?>"><?= kiosk_icon($item['icon']) ?></span><span class="menu-status"><?= kiosk_escape($item['status']) ?></span></span><h3><?= kiosk_escape($item['title']) ?></h3><p><?= kiosk_escape($item['text']) ?></p><span class="card-action">Buka informasi <?= kiosk_icon('arrow') ?></span></a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php require dirname(__DIR__) . '/app/views/public-announcements.php'; ?>
            <aside class="public-notice"><?= kiosk_icon('help') ?><p><strong>Informasi untuk Anda</strong> Katalog menampilkan layanan yang dipublikasikan pengelola. Pengajuan tersedia sesuai pengaturan layanan; data DEMO hanya untuk simulasi. Surat final dan nomor pengambilan tersedia setelah layanan diselesaikan admin. Antrean dan janji lintas instansi hanya simulasi lokal, bukan reservasi resmi.</p></aside>
        <?php elseif ($selected === 'bantuan'): ?>
            <?php require dirname(__DIR__) . '/app/views/public-help.php'; ?>
        <?php elseif ($selected === 'antrean'): ?>
            <?php require dirname(__DIR__) . '/app/views/public-queues.php'; ?>
        <?php elseif ($selected === 'pengaduan'): ?>
            <?php require dirname(__DIR__) . '/app/views/public-complaints.php'; ?>
        <?php elseif ($selected === 'syarat'): ?>
            <?php require dirname(__DIR__) . '/app/views/public-document-guides.php'; ?>
        <?php elseif ($selected === 'kantor'): ?>
            <?php require dirname(__DIR__) . '/app/views/public-office.php'; ?>
        <?php elseif ($isCatalog): ?>
            <?php require dirname(__DIR__) . '/app/views/service-catalog.php'; ?>
        <?php elseif ($isTransaction): ?>
            <?php require dirname(__DIR__) . '/app/views/public-submissions.php'; ?>
        <?php else: ?>
            <a class="back-link" href="kiosk.php" data-menu-voice="Beranda KIOSK"><?= kiosk_icon('home') ?> Kembali ke beranda</a>
            <article class="detail-panel">
                <span class="menu-icon <?= kiosk_escape($page['tone']) ?>"><?= kiosk_icon($page['icon']) ?></span>
                <span class="detail-category"><?= kiosk_escape($page['title']) ?> <span class="menu-status"><?= kiosk_escape($page['status']) ?></span></span>
                <h1><?= kiosk_escape($page['heading']) ?></h1>
                <p class="detail-intro"><?= kiosk_escape($page['intro']) ?></p>
                <ol class="guide-list">
                    <?php foreach ($page['steps'] as $index => $step): ?>
                        <li><span aria-hidden="true"><?= $index + 1 ?></span><p><?= kiosk_escape($step) ?></p></li>
                    <?php endforeach; ?>
                </ol>
                <div class="detail-bottom"><?= kiosk_icon('help') ?><p>Butuh bantuan langsung? Silakan temui petugas di lokasi kecamatan.</p></div>
            </article>
        <?php endif; ?>
    </main>
    <footer class="kiosk-footer">
        <a class="footer-button" href="kiosk.php" data-menu-voice="Beranda KIOSK"><?= kiosk_icon('home') ?> Beranda</a>
        <button class="footer-button" id="text-size-toggle" type="button" aria-pressed="false"><span aria-hidden="true">A+</span> Teks besar</button>
        <a class="footer-button" href="kiosk.php?menu=bantuan" data-menu-voice="Bantuan penggunaan"><?= kiosk_icon('help') ?> Bantuan</a>
        <form method="post" action="end-session.php" class="end-session-form"><input type="hidden" name="csrf" value="<?= kiosk_escape($_SESSION['csrf']) ?>"><button class="footer-button" type="submit">Selesai & bersihkan sesi</button></form>
        <span class="footer-note">Jaga kerahasiaan data pribadi Anda.</span>
    </footer>
    <p class="speech-status" id="speech-status" role="status" aria-live="polite"></p>
    <dialog id="idle-dialog" aria-labelledby="idle-title">
        <span class="dialog-icon"><?= kiosk_icon('help') ?></span>
        <h2 id="idle-title">Masih menggunakan KIOSK?</h2>
        <p>Layar akan kembali ke beranda dalam <span id="idle-countdown">20</span> detik karena tidak ada aktivitas.</p>
        <div class="dialog-actions"><button id="continue-session" type="button">Ya, lanjutkan</button><button id="end-session" type="button">Akhiri sesi</button></div>
    </dialog>
</body>
</html>
