<?php
declare(strict_types=1);

function run_migrations(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            version TEXT PRIMARY KEY,
            applied_at TEXT NOT NULL
        )'
    );

    $migrationFiles = glob(dirname(__DIR__) . '/database/migrations/*.sql');
    if ($migrationFiles === false) {
        throw new RuntimeException('Daftar migrasi database tidak dapat dibaca.');
    }
    sort($migrationFiles, SORT_STRING);

    foreach ($migrationFiles as $migrationFile) {
        $version = basename($migrationFile, '.sql');
        $check = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE version = :version');
        $check->execute(['version' => $version]);
        if ($check->fetchColumn()) {
            continue;
        }

        $sql = file_get_contents($migrationFile);
        if ($sql === false) {
            throw new RuntimeException('File migrasi ' . $version . ' tidak dapat dibaca.');
        }

        $pdo->beginTransaction();
        try {
            $pdo->exec($sql);
            $insert = $pdo->prepare(
                'INSERT INTO schema_migrations (version, applied_at)
                 VALUES (:version, CURRENT_TIMESTAMP)'
            );
            $insert->execute(['version' => $version]);
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
    }
}
