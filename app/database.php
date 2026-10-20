<?php
declare(strict_types=1);

function open_database(bool $create = false): PDO
{
    $databasePath = dirname(__DIR__) . '/database/kiosk.sqlite';

    if (!is_file($databasePath) && !$create) {
        throw new RuntimeException('Database belum tersedia. Jalankan php bin/setup-admin.php untuk menyiapkan aplikasi.');
    }

    if ($create && !is_dir(dirname($databasePath)) && !mkdir(dirname($databasePath), 0700, true) && !is_dir(dirname($databasePath))) {
        throw new RuntimeException('Direktori database tidak dapat dibuat.');
    }

    $pdo = new PDO('sqlite:' . $databasePath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');

    if ($create && !chmod($databasePath, 0600)) {
        throw new RuntimeException('Izin file database tidak dapat diamankan.');
    }

    return $pdo;
}
