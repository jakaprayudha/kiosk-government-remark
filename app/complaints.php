<?php
declare(strict_types=1);

function complaint_kinds(): array
{
    return ['complaint' => 'Pengaduan', 'aspiration' => 'Aspirasi'];
}

function complaint_categories(): array
{
    return ['service' => 'Pelayanan publik', 'infrastructure' => 'Sarana & infrastruktur',
        'environment' => 'Lingkungan', 'social' => 'Sosial & kemasyarakatan', 'other' => 'Lainnya'];
}

function complaint_statuses(): array
{
    return ['new' => 'Diterima', 'reviewing' => 'Ditinjau', 'in_progress' => 'Ditindaklanjuti',
        'completed' => 'Selesai', 'rejected' => 'Tidak dapat ditindaklanjuti'];
}

function complaint_transitions(string $status): array
{
    return match ($status) {
        'new' => ['reviewing', 'rejected'],
        'reviewing' => ['reviewing', 'in_progress', 'rejected'],
        'in_progress' => ['in_progress', 'completed', 'rejected'],
        'completed', 'rejected' => [],
        default => throw new InvalidArgumentException('Status pengaduan tidak dikenal.'),
    };
}

function validate_complaint(array $input): array
{
    $result = [];
    foreach (['kind' => 20, 'category' => 30, 'title' => 160, 'location' => 240,
        'description' => 3000, 'name' => 120, 'contact' => 120] as $key => $limit) {
        if (!is_string($input[$key] ?? null)) throw new DomainException('Isian laporan tidak valid.');
        $result[$key] = trim($input[$key]);
        if (strlen($result[$key]) > $limit) throw new DomainException('Isian terlalu panjang. Persingkat judul, lokasi, atau uraian.');
    }
    if (!isset(complaint_kinds()[$result['kind']]) || !isset(complaint_categories()[$result['category']])) {
        throw new DomainException('Pilih jenis laporan dan kategori yang tersedia.');
    }
    if ($result['title'] === '' || $result['description'] === '') throw new DomainException('Judul dan uraian wajib diisi.');
    if (($input['consent'] ?? '') !== '1' || ($input['confirmed'] ?? '') !== '1') {
        throw new DomainException('Setujui penggunaan data dan konfirmasikan isi laporan sebelum mengirim.');
    }
    return $result;
}

function create_complaint(PDO $pdo, array $input, string $key, string $pin): string
{
    $data = validate_complaint($input);
    if (!preg_match('/^[a-f0-9]{48}$/', $key) || !preg_match('/^[0-9]{6}$/', $pin)) {
        throw new DomainException('Sesi laporan tidak valid. Muat ulang formulir.');
    }
    $code = 'AD-' . strtoupper(bin2hex(random_bytes(10)));
    $pdo->beginTransaction();
    try {
        $insert = $pdo->prepare('INSERT INTO complaints(public_code,pin_hash,request_key,kind,category,title,location,description,name,contact,expires_at)
            VALUES(?,?,?,?,?,?,?,?,?,?,?) ON CONFLICT(request_key) DO NOTHING');
        $insert->execute([$code, password_hash($pin, PASSWORD_DEFAULT), $key, $data['kind'], $data['category'],
            $data['title'], $data['location'], $data['description'], $data['name'], $data['contact'],
            gmdate('Y-m-d H:i:s', time() + 180 * 86400)]);
        if ($insert->rowCount() === 1) {
            $id = (int) $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO complaint_events(complaint_id,new_status,public_message) VALUES(?,'new',?)")
                ->execute([$id, 'Laporan tersimpan dan menunggu peninjauan petugas.']);
        }
        $query = $pdo->prepare('SELECT public_code FROM complaints WHERE request_key=?');
        $query->execute([$key]);
        $code = (string) $query->fetchColumn();
        $pdo->commit();
        return $code;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function lookup_complaint(PDO $pdo, string $code, string $pin): array
{
    $code = strtoupper(trim($code));
    if (!preg_match('/^AD-[A-F0-9]{20}$/', $code) || !preg_match('/^[0-9]{6}$/', $pin)) {
        throw new DomainException('Kode laporan atau PIN tidak sesuai, atau laporan melewati masa retensi.');
    }
    $query = $pdo->prepare('SELECT id,public_code,pin_hash,kind,category,status,created_at,updated_at FROM complaints WHERE public_code=? AND expires_at>CURRENT_TIMESTAMP');
    $query->execute([$code]);
    $record = $query->fetch();
    $hash = $record ? $record['pin_hash'] : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
    if (!password_verify($pin, $hash) || !$record) {
        throw new DomainException('Kode laporan atau PIN tidak sesuai, atau laporan melewati masa retensi.');
    }
    unset($record['pin_hash']);
    $events = $pdo->prepare('SELECT new_status,public_message,created_at FROM complaint_events WHERE complaint_id=? ORDER BY id');
    $events->execute([$record['id']]);
    $record['events'] = $events->fetchAll();
    unset($record['id']);
    return $record;
}

function update_complaint(PDO $pdo, int $id, int $version, string $expected, string $next, string $reply, string $internal, int $adminId): void
{
    if (!in_array($next, complaint_transitions($expected), true)) throw new DomainException('Perubahan status tidak diizinkan.');
    $reply = trim($reply);
    $internal = trim($internal);
    if ($reply === '' || strlen($reply) > 3000 || strlen($internal) > 3000) {
        throw new DomainException('Tanggapan publik wajib diisi, maksimal 3000 byte. Catatan internal maksimal 3000 byte.');
    }
    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare('UPDATE complaints SET status=?,version=version+1,updated_at=CURRENT_TIMESTAMP WHERE id=? AND status=? AND version=? AND expires_at>CURRENT_TIMESTAMP');
        $query->execute([$next, $id, $expected, $version]);
        if ($query->rowCount() !== 1) throw new DomainException('Laporan berubah atau kedaluwarsa. Muat ulang sebelum menyimpan.');
        $pdo->prepare('INSERT INTO complaint_events(complaint_id,new_status,public_message,internal_note,changed_by) VALUES(?,?,?,?,?)')
            ->execute([$id, $next, $reply, $internal, $adminId]);
        $pdo->prepare("INSERT INTO audit_logs(user_id,action,entity_type,entity_id,summary) VALUES(?,'complaint_status','complaint',?,?)")
            ->execute([$adminId, $id, 'Tindak lanjut laporan: ' . $expected . ' -> ' . $next]);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function purge_expired_complaints(PDO $pdo): int
{
    return $pdo->exec('DELETE FROM complaints WHERE expires_at<=CURRENT_TIMESTAMP');
}
