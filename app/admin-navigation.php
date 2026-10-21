<?php
declare(strict_types=1);

function admin_navigation(): array
{
    return [
        'dashboard' => ['label' => 'Dashboard', 'group' => 'UTAMA', 'icon' => 'grid', 'description' => 'Ringkasan panel pengelola KIOSK.'],
        'categories' => ['label' => 'Kategori layanan', 'group' => 'KONTEN KIOSK', 'icon' => 'folder', 'description' => 'Kelompokkan layanan, atur ikon, urutan, dan status kategori.'],
        'services' => ['label' => 'Layanan administrasi', 'group' => 'KONTEN KIOSK', 'icon' => 'document', 'description' => 'Kelola master layanan, persyaratan, status publik dan pengaturan transaksi yang fleksibel.'],
        'document-guides' => ['label' => 'Persyaratan & biaya', 'group' => 'KONTEN KIOSK', 'icon' => 'document', 'description' => 'Kelola tabel referensi pengurusan dokumen, syarat, biaya, dan dasar hukum wilayah. Terpisah dari layanan transaksi.'],
        'announcements' => ['label' => 'Pengumuman', 'group' => 'KONTEN KIOSK', 'icon' => 'bell', 'description' => 'Kelola pengumuman, prioritas, dan periode tayang pada KIOSK.'],
        'office' => ['label' => 'Profil kecamatan', 'group' => 'KONTEN KIOSK', 'icon' => 'building', 'description' => 'Kelola profil, desa dan sarana per kecamatan; admin pusat menetapkan akun wilayah dan perangkat KIOSK.'],
        'submissions' => ['label' => 'Pengajuan layanan', 'group' => 'OPERASIONAL', 'icon' => 'inbox', 'description' => 'Tinjau pengajuan, penugasan petugas, status, dan riwayat tindak lanjut.'],
        'complaints' => ['label' => 'Pengaduan & aspirasi', 'group' => 'OPERASIONAL', 'icon' => 'chat', 'description' => 'Tinjau masukan masyarakat dengan catatan publik dan internal yang terpisah.'],
        'queues' => ['label' => 'Antrean & jadwal', 'group' => 'OPERASIONAL', 'icon' => 'calendar', 'description' => 'Kelola instansi, layanan, jadwal berkuota, dan panggilan simulasi DEMO lokal. Belum terintegrasi ke antrean resmi.'],
        'users' => ['label' => 'Akun & petugas', 'group' => 'ADMINISTRASI', 'icon' => 'users', 'description' => 'Admin pusat mengelola akun admin setiap kecamatan, password dan status akses wilayah.'],
        'reports' => ['label' => 'Laporan', 'group' => 'ADMINISTRASI', 'icon' => 'chart', 'description' => 'Ringkasan pengajuan, pengaduan dan pemesanan antrean menurut periode WIB, status terkini dan layanan.'],
        'audit' => ['label' => 'Audit aktivitas', 'group' => 'ADMINISTRASI', 'icon' => 'clock', 'description' => 'Telusuri riwayat aktivitas pengelola dengan filter periode, aktivitas, dan pencarian.'],
        'settings' => ['label' => 'Pengaturan KIOSK', 'group' => 'ADMINISTRASI', 'icon' => 'settings', 'description' => 'Kelola logo, nama daerah, judul, tagline, dan pesan footer tampilan publik.'],
    ];
}

function admin_icon(string $name): string
{
    $paths = [
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'folder' => '<path d="M3 7V5h7l2 3h9v12H3Z"/>',
        'document' => '<path d="M6 3h8l4 4v14H6Z"/><path d="M14 3v5h4M9 12h6M9 16h6"/>',
        'bell' => '<path d="M5 17h14l-2-3V9a5 5 0 0 0-10 0v5ZM10 21h4M12 2v2"/>',
        'building' => '<path d="m3 9 9-6 9 6M5 10v10h14V10M3 21h18M9 11v6M15 11v6"/>',
        'inbox' => '<path d="M4 4h16v16H4ZM4 14h5l1 3h4l1-3h5M9 8h6"/>',
        'chat' => '<path d="M3 4h18v13H10l-5 4v-4H3ZM7 9h10M7 13h6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 11h18M7 15h3M14 15h3"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M17 5a3 3 0 0 1 0 6M18 15a5 5 0 0 1 3 5"/>',
        'chart' => '<path d="M4 3v18h17M8 16v-4M13 16V8M18 16V5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'settings' => '<path d="m9 3-1 3-3 1-2 4 2 2v4l4 3 3-1 3 1 4-3v-4l2-2-2-4-3-1-1-3Z"/><circle cx="12" cy="12" r="3"/>',
        'arrow' => '<path d="M5 12h14m-5-5 5 5-5 5"/>',
        'external' => '<path d="M14 3h7v7M21 3 10 14M10 5H3v16h16v-7"/>',
        'logout' => '<path d="M9 3H3v18h6M8 12h13m-4-4 4 4-4 4"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    ];

    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';
}
