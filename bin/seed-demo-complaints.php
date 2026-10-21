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
    $sql = file_get_contents(dirname(__DIR__) . '/database/seeds/demo-complaints.sql');
    if ($sql === false) throw new RuntimeException('Seed pengaduan DEMO tidak dapat dibaca.');
    $pdo->beginTransaction();
    $pdo->exec($sql);
    $pdo->commit();
    fwrite(STDOUT, "Seed 3 laporan DEMO selesai: dua pengaduan (termasuk anonim) dan satu aspirasi. PIN contoh: 123456. Data lama tidak ditimpa.\n");
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, 'Seed pengaduan gagal: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
