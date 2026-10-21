<?php
declare(strict_types=1);

function submission_statuses(): array
{
    return ['new' => 'Baru', 'reviewing' => 'Ditinjau', 'needs_info' => 'Perlu informasi',
        'in_progress' => 'Diproses', 'completed' => 'Selesai', 'rejected' => 'Ditolak'];
}

function submission_transitions(string $status): array
{
    return match ($status) {
        'new' => ['reviewing', 'rejected'],
        'reviewing' => ['needs_info', 'in_progress', 'rejected'],
        'needs_info' => ['reviewing', 'in_progress', 'rejected'],
        'in_progress' => ['needs_info', 'completed', 'rejected'],
        'completed', 'rejected' => [],
        default => throw new InvalidArgumentException('Status tidak dikenal.'),
    };
}

function public_attempt(PDO $pdo, string $action, string $identity): void
{
    $hash = hash('sha256', $identity);
    $pdo->exec("DELETE FROM public_attempts WHERE attempted_at < datetime('now', '-1 day')");
    $count = $pdo->prepare("SELECT COUNT(*) FROM public_attempts WHERE action = ? AND identity_hash = ? AND attempted_at >= datetime('now', '-15 minutes')");
    $count->execute([$action, $hash]);
    if ((int) $count->fetchColumn() >= ($action === 'lookup' ? 10 : 5)) {
        throw new DomainException('Terlalu banyak percobaan. Tunggu 15 menit atau hubungi petugas.');
    }
    $pdo->prepare('INSERT INTO public_attempts (action, identity_hash) VALUES (?, ?)')->execute([$action, $hash]);
}

function validate_submission(array $service, array $input): array
{
    $result = [];
    foreach (['name' => 120, 'contact' => 120, 'description' => 3000] as $key => $limit) {
        if (!is_string($input[$key] ?? null)) {
            throw new DomainException('Isian formulir tidak valid.');
        }
        $result[$key] = trim($input[$key]);
        if (strlen($result[$key]) > $limit) {
            throw new DomainException('Isian melebihi batas panjang yang diizinkan.');
        }
    }
    if ($result['description'] === '' || ((int) $service['requires_name'] === 1 && $result['name'] === '')
        || ((int) $service['requires_contact'] === 1 && $result['contact'] === '')) {
        throw new DomainException('Lengkapi uraian dan seluruh isian wajib.');
    }
    if (($input['consent'] ?? '') !== '1' || ($input['confirmed'] ?? '') !== '1') {
        throw new DomainException('Persetujuan privasi dan pemeriksaan ringkasan wajib dilakukan.');
    }
    foreach (['nik', 'birth_date'] as $field) {
        if (isset($input[$field]) && !is_string($input[$field])) throw new DomainException('Isian identitas tidak valid.');
        $result[$field] = trim($input[$field] ?? '');
    }
    if ((int) $service['requires_nik'] === 1) {
        if (!preg_match('/^[0-9]{16}$/', $result['nik'])) throw new DomainException('NIK wajib berisi tepat 16 digit. Format bukan verifikasi Dukcapil.');
    } elseif ($result['nik'] !== '') {
        throw new DomainException('Layanan ini tidak meminta NIK. Jangan kirim NIK.');
    }
    if ((int) $service['requires_birth_date'] === 1) {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $result['birth_date'], new DateTimeZone('Asia/Jakarta'));
        if (!$date || $date->format('Y-m-d') !== $result['birth_date'] || $result['birth_date'] < '1900-01-01'
            || $result['birth_date'] > (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d')) {
            throw new DomainException('Tanggal lahir harus valid (YYYY-MM-DD), tidak di masa depan.');
        }
    } elseif ($result['birth_date'] !== '') {
        throw new DomainException('Layanan ini tidak meminta tanggal lahir.');
    }
    return $result;
}

function create_submission(PDO $pdo, array $service, array $input, string $requestKey, string $pin, array $photos): string
{
    $existing = $pdo->prepare('SELECT public_code FROM submissions WHERE request_key = ?');
    $existing->execute([$requestKey]);
    $code = $existing->fetchColumn();
    if ($code !== false) {
        return $code;
    }
    $data = validate_submission($service, $input);
    $faceCount = count(array_filter($photos, static fn(array $photo): bool => ($photo['document_name'] ?? '') === 'Foto wajah pemohon' && $photo['requirement_id'] === null));
    if ((int) $service['requires_face'] === 1 && $faceCount !== 1) throw new DomainException('Ambil dan gunakan satu foto wajah sebelum mengirim.');
    if ((int) $service['requires_face'] !== 1 && $faceCount !== 0) throw new DomainException('Foto wajah tidak diminta untuk layanan ini.');
    $code = strtoupper(bin2hex(random_bytes(10)));
    $pdo->beginTransaction();
    try {
        $insert = $pdo->prepare(
            'INSERT INTO submissions (public_code, pin_hash, request_key, service_id, service_name, is_demo,
             name, contact, description, expires_at, nik, birth_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([$code, password_hash($pin, PASSWORD_DEFAULT), $requestKey, $service['id'], $service['name'],
            $service['is_demo'], $data['name'], $data['contact'], $data['description'],
            gmdate('Y-m-d H:i:s', time() + (int) $service['retention_days'] * 86400), $data['nik'], $data['birth_date']]);
        $id = (int) $pdo->lastInsertId();
        $pdo->prepare("INSERT INTO submission_events (submission_id, new_status, public_message) VALUES (?, 'new', ?)")
            ->execute([$id, 'Pengajuan tersimpan dan menunggu pemeriksaan petugas.']);
        $insertPhoto = $pdo->prepare('INSERT INTO submission_attachments (submission_id, requirement_id, document_name, stored_name, mime_type, size_bytes) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($photos as $photo) {
            $insertPhoto->execute([$id, $photo['requirement_id'], $photo['document_name'], $photo['stored_name'], 'image/jpeg', $photo['size_bytes']]);
        }
        $pdo->commit();
        return $code;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function change_submission_status(PDO $pdo, int $id, string $expected, string $next, string $publicNote, string $internalNote, int $adminId, ?array $letter = null): void
{
    if (!in_array($next, submission_transitions($expected), true)) {
        throw new DomainException('Perubahan status tidak diizinkan.');
    }
    if (strlen($publicNote) > 3000 || strlen($internalNote) > 3000) {
        throw new DomainException('Catatan maksimal 3000 byte.');
    }
    if (in_array($next, ['needs_info', 'rejected'], true) && trim($publicNote) === '') {
        throw new DomainException('Status ini wajib disertai alasan yang dapat dibaca pemohon.');
    }
    $pdo->beginTransaction();
    try {
        if ($next === 'completed' && $letter === null) throw new DomainException('Unggah PDF surat final yang telah disahkan untuk menyelesaikan layanan.');
        if ($next !== 'completed' && $letter !== null) throw new DomainException('Surat final hanya boleh diterbitkan saat status Selesai.');
        $update = $pdo->prepare('UPDATE submissions SET status = ?, public_note = ?, internal_note = ?, updated_at = CURRENT_TIMESTAMP,
            closed_at = CASE WHEN ? IN (\'completed\', \'rejected\') THEN CURRENT_TIMESTAMP ELSE NULL END
            WHERE id = ? AND status = ? AND expires_at > CURRENT_TIMESTAMP');
        $update->execute([$next, $publicNote, $internalNote, $next, $id, $expected]);
        if ($update->rowCount() !== 1) {
            throw new DomainException('Data berubah atau telah melewati retensi. Muat ulang sebelum memperbarui.');
        }
        if ($next === 'completed') {
            if (!preg_match('/^[a-f0-9]{48}\.pdf$/', $letter['stored_name']) || $letter['size_bytes'] < 5) throw new DomainException('Surat final tidak valid.');
            $serviceQuery = $pdo->prepare('SELECT service_id FROM submissions WHERE id = ?');
            $serviceQuery->execute([$id]);
            $serviceId = (int) $serviceQuery->fetchColumn();
            $pdo->prepare('INSERT INTO service_pickup_sequences (service_id, last_number) VALUES (?, 1) ON CONFLICT(service_id) DO UPDATE SET last_number = last_number + 1')->execute([$serviceId]);
            $numberQuery = $pdo->prepare('SELECT last_number FROM service_pickup_sequences WHERE service_id = ?');
            $numberQuery->execute([$serviceId]);
            $pickupNumber = sprintf('L%d-%06d', $serviceId, (int) $numberQuery->fetchColumn());
            $pdo->prepare('INSERT INTO submission_letters (submission_id, stored_name, size_bytes, pickup_number, approved_by) VALUES (?, ?, ?, ?, ?)')->execute([$id, $letter['stored_name'], $letter['size_bytes'], $pickupNumber, $adminId]);
        }
        $pdo->prepare('INSERT INTO submission_events (submission_id, old_status, new_status, public_message, internal_note, changed_by) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$id, $expected, $next, $publicNote, $internalNote, $adminId]);
        $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, summary) VALUES (?, 'submission_status', 'submission', ?, ?)")
            ->execute([$adminId, $id, 'Status pengajuan diperbarui: ' . $expected . ' -> ' . $next]);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}
