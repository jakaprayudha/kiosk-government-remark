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
    $sql = file_get_contents(dirname(__DIR__) . '/database/seeds/demo-document-guides.sql');
    if ($sql === false) throw new RuntimeException('Seed SQL tidak dapat dibaca.');
    $pdo->beginTransaction();
    $pdo->exec($sql);
    $pdo->commit();
    fwrite(STDOUT, "Data DEMO persyaratan & biaya Kabupaten Serdang Bedagai siap. Bukan tarif resmi; data yang sudah ada tidak ditimpa.\n");
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, 'Seed gagal: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
