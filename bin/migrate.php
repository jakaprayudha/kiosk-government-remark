<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/app/database.php';
require_once dirname(__DIR__) . '/app/migrations.php';

$pdo = open_database(true);
run_migrations($pdo);
fwrite(STDOUT, "Migrasi database selesai.\n");
