<?php
declare(strict_types=1);

function public_announcements(PDO $pdo, DateTimeImmutable $now): array
{
    $query = $pdo->prepare('SELECT id,title,body,starts_at,ends_at,priority FROM announcements
        WHERE is_published=1 AND (starts_at IS NULL OR starts_at<=:now)
        AND (ends_at IS NULL OR ends_at>=:now)
        ORDER BY priority DESC,created_at DESC,id DESC');
    $query->execute(['now' => $now->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('Y-m-d H:i')]);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function validate_announcement(array $input): array
{
    $data = [];
    foreach (['title' => 180, 'body' => 10000] as $key => $limit) {
        if (!is_string($input[$key] ?? null)) throw new DomainException('Judul atau isi pengumuman tidak valid.');
        $data[$key] = trim($input[$key]);
        if ($data[$key] === '' || strlen($data[$key]) > $limit) throw new DomainException('Judul dan isi wajib diisi; judul maksimal 180 byte, isi 10000 byte.');
    }
    foreach (['starts_at', 'ends_at'] as $key) {
        $value = $input[$key] ?? '';
        if (!is_string($value)) throw new DomainException('Waktu tayang tidak valid.');
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new DateTimeZone('Asia/Jakarta'));
        if ($value !== '' && (!$date || $date->format('Y-m-d\TH:i') !== $value)) throw new DomainException('Waktu tayang harus berupa tanggal dan jam yang valid (WIB).');
        $data[$key] = $value === '' ? null : $date->format('Y-m-d H:i');
    }
    if ($data['starts_at'] !== null && $data['ends_at'] !== null && $data['ends_at'] < $data['starts_at']) throw new DomainException('Akhir tayang harus sama atau setelah mulai tayang.');
    $priority = filter_var($input['priority'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 9999]]);
    if ($priority === false || (isset($input['is_published']) && !in_array($input['is_published'], ['0', '1'], true))) throw new DomainException('Prioritas atau status tidak valid.');
    $data['priority'] = $priority;
    $data['is_published'] = ($input['is_published'] ?? '') === '1' ? 1 : 0;
    return $data;
}

function mutate_announcement(PDO $pdo, string $action, array $input, int $id, int $version, int $adminId): int
{
    if (!in_array($action, ['save', 'toggle', 'delete'], true) || $id < 0 || ($id > 0 && $version < 1) || ($action !== 'save' && $id === 0)) throw new DomainException('Aksi, ID atau versi pengumuman tidak valid.');
    $data = $action === 'save' ? validate_announcement($input) : [];
    if ($action === 'toggle' && !in_array($input['is_published'] ?? '0', ['0', '1'], true)) throw new DomainException('Status pengumuman tidak valid.');
    $pdo->beginTransaction();
    try {
        if ($action === 'save' && $id === 0) {
            $query = $pdo->prepare('INSERT INTO announcements(title,body,starts_at,ends_at,priority,is_published,created_by) VALUES(?,?,?,?,?,?,?)');
            $query->execute([...array_values($data), $adminId]);
            $id = (int) $pdo->lastInsertId();
        } else {
            if ($action === 'save') {
                $query = $pdo->prepare('UPDATE announcements SET title=?,body=?,starts_at=?,ends_at=?,priority=?,is_published=?,version=version+1,updated_at=CURRENT_TIMESTAMP WHERE id=? AND version=?');
                $query->execute([...array_values($data), $id, $version]);
            } elseif ($action === 'toggle') {
                $query = $pdo->prepare('UPDATE announcements SET is_published=?,version=version+1,updated_at=CURRENT_TIMESTAMP WHERE id=? AND version=?');
                $query->execute([(int) ($input['is_published'] ?? 0), $id, $version]);
            } else {
                $query = $pdo->prepare('DELETE FROM announcements WHERE id=? AND version=?');
                $query->execute([$id, $version]);
            }
            if ($query->rowCount() !== 1) throw new DomainException('Pengumuman berubah atau telah dihapus. Muat ulang terlebih dahulu.');
        }
        $pdo->prepare('INSERT INTO audit_logs(user_id,action,entity_type,entity_id,summary) VALUES(?,?,?,?,?)')->execute([
            $adminId, 'announcement_' . $action, 'announcement', $id,
            ['save' => 'Pengumuman disimpan.', 'toggle' => 'Status pengumuman diperbarui.', 'delete' => 'Pengumuman dihapus.'][$action],
        ]);
        $pdo->commit();
        return $id;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}
