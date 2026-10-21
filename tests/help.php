<?php
declare(strict_types=1);
function kiosk_escape(string $text): string { return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function kiosk_icon(string $name): string { return ''; }
function check_help(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
ob_start();
require dirname(__DIR__) . '/app/views/public-help.php';
$html = ob_get_clean();
foreach (['Bantuan & cara menggunakan KIOSK', 'Tidak perlu membuat akun', 'Keyboard layar sentuh',
    'A+ Teks besar', 'AD-', 'AN-', 'PIN enam digit', 'kotak hijau', 'Buka / cetak PDF surat',
    '15 menit', '20 detik', 'Selesai & bersihkan sesi', 'tidak berlaku', 'tidak memanggil petugas',
    'bukan menghapus pengajuan', 'Mengakhiri sesi tidak menghapus file unduhan'] as $text) {
    check_help(str_contains($html, $text), 'Panduan hilang: ' . $text);
}
foreach (['layanan','syarat','kantor','pengajuan','status','pengaduan','antrean'] as $menu) {
    check_help(str_contains($html, 'href="kiosk.php?menu=' . $menu . '"'), 'Tautan menu hilang: ' . $menu);
}
foreach (['pengaduan&amp;tab=tanggapan','antrean&amp;tab=cek'] as $route) {
    check_help(str_contains($html, 'href="kiosk.php?menu=' . $route . '"'), 'Tautan cek salah.');
}
preg_match_all('/href="#([^"]+)"/', $html, $anchors);
foreach ($anchors[1] as $id) check_help(substr_count($html, 'id="' . $id . '"') === 1, 'Tujuan topik tidak unik/tersedia: ' . $id);
check_help(substr_count($html, '<details>') === 15 && substr_count($html, '<summary>') === 15, 'Jumlah panduan menu/kendala tidak lengkap.');
check_help(!str_contains($html, '<form') && !str_contains($html, '<input'), 'Bantuan tidak perlu meminta data pengguna.');
echo "PASS: panduan menu, kode/PIN, perangkat, kendala, privasi, DEMO, tautan topik dan FAQ tanpa formulir.\n";
