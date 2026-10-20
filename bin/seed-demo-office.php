<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once dirname(__DIR__) . '/app/database.php';
require_once dirname(__DIR__) . '/app/migrations.php';
try {
    $pdo = open_database(true);
    run_migrations($pdo);
    $sql = file_get_contents(dirname(__DIR__) . '/database/seeds/demo-sei-rampah.sql');
    if ($sql === false) throw new RuntimeException('Seed profil kecamatan tidak dapat dibaca.');
    $pdo->beginTransaction();
    $pdo->exec($sql);
    $facilities = file_get_contents(dirname(__DIR__) . '/database/seeds/demo-office-facilities.sql');
    if ($facilities === false) throw new RuntimeException('Seed fasilitas tidak dapat dibaca.');
    $pdo->exec($facilities);
    $pdo->commit();
    fwrite(STDOUT, "Seed DEMO profil Sei Rampah, 17 desa dan 8 fasilitas selesai. Semua fasilitas/lokasi contoh hanya simulasi. Data lama tidak ditimpa.\n");
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, 'Seed gagal: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
