<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/database.php';
require_once dirname(__DIR__) . '/app/migrations.php';

$categories = [
    'demo-administrasi-umum' => 'DEMO - Administrasi umum',
    'demo-konsultasi' => 'DEMO - Konsultasi layanan',
    'demo-informasi' => 'DEMO - Informasi publik',
];
$services = [
    ['slug' => 'demo-legalisasi-dokumen', 'category' => 'demo-administrasi-umum', 'name' => 'Legalisasi dokumen', 'summary' => 'Simulasi informasi pemeriksaan dan legalisasi dokumen.', 'requirements' => ['Dokumen contoh yang akan diperiksa', 'Salinan dokumen contoh']],
    ['slug' => 'demo-rekomendasi-kegiatan', 'category' => 'demo-administrasi-umum', 'name' => 'Rekomendasi kegiatan', 'summary' => 'Simulasi informasi permohonan rekomendasi kegiatan masyarakat.', 'requirements' => ['Surat permohonan contoh', 'Rencana kegiatan contoh']],
    ['slug' => 'demo-konsultasi-administrasi', 'category' => 'demo-konsultasi', 'name' => 'Konsultasi administrasi', 'summary' => 'Simulasi panduan konsultasi kebutuhan administrasi.', 'requirements' => ['Ringkasan kebutuhan konsultasi contoh', 'Dokumen pendukung contoh']],
    ['slug' => 'demo-konsultasi-kanal-instansi', 'category' => 'demo-konsultasi', 'name' => 'Konsultasi kanal instansi', 'summary' => 'Simulasi panduan mencari instansi yang menangani kebutuhan Anda.', 'requirements' => ['Catatan kebutuhan layanan contoh', 'Referensi layanan contoh']],
    ['slug' => 'demo-permintaan-informasi', 'category' => 'demo-informasi', 'name' => 'Permintaan informasi publik', 'summary' => 'Simulasi informasi alur permintaan informasi publik.', 'requirements' => ['Uraian informasi yang diminta (contoh)', 'Referensi informasi pendukung (contoh)']],
    ['slug' => 'demo-informasi-fasilitas', 'category' => 'demo-informasi', 'name' => 'Informasi fasilitas umum', 'summary' => 'Simulasi informasi fasilitas dan unit layanan di wilayah kecamatan.', 'requirements' => ['Catatan lokasi atau fasilitas contoh', 'Gambar pendukung contoh']],
];

try {
    $pdo = open_database(true);
    run_migrations($pdo);
    $pdo->beginTransaction();
    $insertCategory = $pdo->prepare(
        'INSERT INTO service_categories (name, slug, description, sort_order)
         VALUES (:name, :slug, :description, :sort_order) ON CONFLICT(slug) DO NOTHING'
    );
    $findCategory = $pdo->prepare('SELECT id FROM service_categories WHERE slug = ?');
    $categoryIds = [];
    foreach ($categories as $slug => $name) {
        $insertCategory->execute([
            'name' => $name,
            'slug' => $slug,
            'description' => 'Kategori contoh untuk pengujian, bukan klasifikasi resmi kecamatan.',
            'sort_order' => count($categoryIds) + 1,
        ]);
        $findCategory->execute([$slug]);
        $categoryIds[$slug] = (int) $findCategory->fetchColumn();
    }

    $insertService = $pdo->prepare(
        'INSERT INTO services
         (category_id, name, slug, summary, description, eligibility, fee_text,
          estimated_time, office_unit, service_location, is_published, sort_order,
          accepts_submissions, camera_enabled, is_demo, requires_name, requires_birth_date, requires_face, requires_nik)
         VALUES (:category_id, :name, :slug, :summary, :description, :eligibility,
                 :fee_text, :estimated_time, :office_unit, :service_location, 1, :sort_order, 1, 1, 1, 1, 1, 1, :requires_nik)
         ON CONFLICT(slug) DO NOTHING'
    );
    $insertRequirement = $pdo->prepare(
        'INSERT INTO service_requirements (service_id, name, description, is_required, sort_order)
         VALUES (:service_id, :name, :description, :is_required, :sort_order)'
    );
    $created = 0;
    foreach ($services as $index => $service) {
        $insertService->execute([
            'category_id' => $categoryIds[$service['category']],
            'name' => 'DEMO - ' . $service['name'],
            'slug' => $service['slug'],
            'summary' => 'DEMO - Bukan informasi resmi. ' . $service['summary'],
            'description' => "DATA DEMO UNTUK PENGUJIAN UI, BUKAN INFORMASI RESMI.\n"
                . $service['summary'] . "\n"
                . 'Nama layanan, dokumen, biaya, waktu, dan lokasi pada contoh ini tidak menentukan kebijakan atau kewenangan kecamatan. Konfirmasikan seluruh ketentuan kepada petugas. Jangan menyerahkan dokumen pribadi berdasarkan contoh ini.',
            'eligibility' => 'Pengguna yang sedang mencoba tampilan KIOSK. Bukan ketentuan pemohon resmi.',
            'fee_text' => 'DEMO - Biaya resmi belum ditetapkan di contoh ini. Konfirmasi kepada petugas.',
            'estimated_time' => 'DEMO - Contoh tampilan: 1 hari kerja. Bukan janji waktu penyelesaian.',
            'office_unit' => 'DEMO - Unit pelayanan contoh, bukan unit resmi.',
            'service_location' => 'DEMO - Loket contoh. Lokasi dan kanal resmi harus dikonfirmasi kepada petugas.',
            'sort_order' => $index + 1,
            'requires_nik' => $service['slug'] === 'demo-legalisasi-dokumen' ? 1 : 0,
        ]);
        if ($insertService->rowCount() === 0) {
            continue;
        }
        $serviceId = (int) $pdo->lastInsertId();
        foreach ($service['requirements'] as $order => $requirement) {
            $insertRequirement->execute([
                'service_id' => $serviceId,
                'name' => 'DEMO - ' . $requirement,
                'description' => 'Persyaratan simulasi saja. Tidak menjadi dasar pengumpulan dokumen atau data pribadi.',
                'is_required' => $order === 0 ? 1 : 0,
                'sort_order' => $order + 1,
            ]);
        }
        $created++;
    }
    $pdo->commit();
    fwrite(STDOUT, "Selesai: {$created} layanan DEMO baru. Data yang sudah ada tidak diubah.\n");
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Pengisian demo gagal: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
