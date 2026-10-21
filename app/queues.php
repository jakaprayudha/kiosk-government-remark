<?php
declare(strict_types=1);

function queue_now(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
}

function queue_kinds(): array
{
    return ['dukcapil' => 'Disdukcapil', 'health' => 'Puskesmas', 'hospital' => 'Rumah sakit', 'government' => 'Dinas / instansi lain'];
}

function queue_statuses(): array
{
    return ['waiting' => 'Menunggu', 'called' => 'Dipanggil', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan', 'missed' => 'Tidak hadir'];
}

function queue_number(array $booking): string
{
    return sprintf('Q%d-%03d', $booking['slot_id'], $booking['number']);
}

function queue_text(array $input, string $key, int $limit, bool $required = true): string
{
    if (!is_string($input[$key] ?? null)) throw new DomainException('Isian ' . $key . ' tidak valid.');
    $text = trim($input[$key]);
    if (($required && $text === '') || strlen($text) > $limit) throw new DomainException('Lengkapi ' . $key . ' (maksimal ' . $limit . ' byte).');
    return $text;
}

function queue_int(mixed $value, int $minimum = 1, int $maximum = PHP_INT_MAX): int
{
    $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $minimum, 'max_range' => $maximum]]);
    if ($number === false) throw new DomainException('Angka atau ID tidak valid.');
    return $number;
}

function queue_audit(PDO $pdo, int $adminId, string $entity, int $id): void
{
    $pdo->prepare("INSERT INTO audit_logs(user_id,action,entity_type,entity_id,summary) VALUES(?,'queue_update',?,?,?)")
        ->execute([$adminId, $entity, $id, 'Pengelolaan antrean DEMO: ' . $entity]);
}

function save_queue_agency(PDO $pdo, array $input, int $adminId): void
{
    $id = queue_int($input['id'] ?? 0, 0);
    $name = queue_text($input, 'name', 160);
    $kind = queue_text($input, 'kind', 20);
    if (!isset(queue_kinds()[$kind])) throw new DomainException('Jenis instansi tidak valid.');
    $values = [$name, $kind, queue_text($input, 'description', 1000, false), queue_text($input, 'address', 300, false), queue_int($input['is_active'] ?? '', 0, 1)];
    $pdo->beginTransaction();
    try {
        if ($id === 0) {
            $pdo->prepare('INSERT INTO queue_agencies(name,kind,description,address,is_active) VALUES(?,?,?,?,?)')->execute($values);
            $id = (int) $pdo->lastInsertId();
        } else {
            $q = $pdo->prepare('UPDATE queue_agencies SET name=?,kind=?,description=?,address=?,is_active=? WHERE id=?');
            $q->execute([...$values, $id]);
            if ($q->rowCount() !== 1) throw new DomainException('Instansi tidak ditemukan.');
        }
        queue_audit($pdo, $adminId, 'queue_agency', $id);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function save_queue_service(PDO $pdo, array $input, int $adminId): void
{
    $id = queue_int($input['id'] ?? 0, 0);
    $agency = queue_int($input['agency_id'] ?? '');
    $values = [$agency, queue_text($input, 'name', 160), queue_text($input, 'instructions', 1500, false), queue_int($input['is_active'] ?? '', 0, 1)];
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare('SELECT id FROM queue_agencies WHERE id=?');
        $q->execute([$agency]);
        if (!$q->fetchColumn()) throw new DomainException('Instansi tidak ditemukan.');
        if ($id === 0) {
            $pdo->prepare('INSERT INTO queue_services(agency_id,name,instructions,is_active) VALUES(?,?,?,?)')->execute($values);
            $id = (int) $pdo->lastInsertId();
        } else {
            // A service cannot move to another agency after schedules have been issued.
            $q = $pdo->prepare('UPDATE queue_services SET name=?,instructions=?,is_active=? WHERE id=? AND agency_id=?');
            $q->execute([$values[1], $values[2], $values[3], $id, $agency]);
            if ($q->rowCount() !== 1) throw new DomainException('Layanan tidak ditemukan pada instansi ini.');
        }
        queue_audit($pdo, $adminId, 'queue_service', $id);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function save_queue_slot(PDO $pdo, array $input, int $adminId): void
{
    $id = queue_int($input['id'] ?? 0, 0);
    $capacity = queue_int($input['capacity'] ?? '', 1, 500);
    $open = queue_int($input['is_open'] ?? '', 0, 1);
    $pdo->beginTransaction();
    try {
        if ($id > 0) {
            $version = queue_int($input['version'] ?? '');
            $q = $pdo->prepare('UPDATE queue_slots SET capacity=?,is_open=?,version=version+1 WHERE id=? AND version=? AND occupied<=?');
            $q->execute([$capacity, $open, $id, $version, $capacity]);
            if ($q->rowCount() !== 1) throw new DomainException('Jadwal berubah atau kuota kurang dari pemesanan terisi. Muat ulang.');
        } else {
            $service = queue_int($input['service_id'] ?? '');
            $date = queue_text($input, 'service_date', 10);
            $start = queue_text($input, 'start_time', 5);
            $end = queue_text($input, 'end_time', 5);
            $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('Asia/Jakarta'));
            if (!$parsed || $parsed->format('Y-m-d') !== $date || $date < queue_now()->format('Y-m-d')
                || $date > queue_now()->modify('+30 days')->format('Y-m-d')
                || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $start)
                || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $end) || $start >= $end
                || $date . ' ' . $end <= queue_now()->format('Y-m-d H:i')) {
                throw new DomainException('Tanggal/jam tidak valid. Jadwal maksimal 30 hari ke depan dan belum berakhir.');
            }
            $q = $pdo->prepare('SELECT id FROM queue_slots WHERE service_id=? AND service_date=? AND start_time<? AND end_time>?');
            $q->execute([$service, $date, $end, $start]);
            if ($q->fetchColumn()) throw new DomainException('Jadwal layanan bertumpang tindih.');
            $pdo->prepare('INSERT INTO queue_slots(service_id,service_date,start_time,end_time,capacity,is_open) VALUES(?,?,?,?,?,?)')
                ->execute([$service, $date, $start, $end, $capacity, $open]);
            $id = (int) $pdo->lastInsertId();
        }
        queue_audit($pdo, $adminId, 'queue_slot', $id);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function create_queue_booking(PDO $pdo, int $slotId, string $key, string $pin, bool $confirmed): string
{
    if (!$confirmed) throw new DomainException('Konfirmasikan bahwa nomor ini hanya simulasi DEMO.');
    if (!preg_match('/^[a-f0-9]{48}$/', $key) || !preg_match('/^[0-9]{6}$/', $pin)) throw new DomainException('Sesi pemesanan tidak valid.');
    $pdo->beginTransaction();
    try {
        // Take the SQLite write lock before checking availability or idempotency.
        $pdo->exec('UPDATE queue_slots SET last_number=last_number WHERE 0');
        $existing = $pdo->prepare('SELECT public_code FROM queue_bookings WHERE request_key=?');
        $existing->execute([$key]);
        $code = $existing->fetchColumn();
        if ($code !== false) {
            $pdo->commit();
            return $code;
        }
        $now = queue_now();
        $q = $pdo->prepare('UPDATE queue_slots SET occupied=occupied+1,last_number=last_number+1,version=version+1
            WHERE id=? AND is_open=1 AND occupied<capacity AND service_date>=? AND service_date<=?
            AND service_date||\' \'||end_time>?
            AND EXISTS(SELECT 1 FROM queue_services s JOIN queue_agencies a ON a.id=s.agency_id
                WHERE s.id=queue_slots.service_id AND s.is_active=1 AND a.is_active=1)');
        $q->execute([$slotId, $now->format('Y-m-d'), $now->modify('+30 days')->format('Y-m-d'), $now->format('Y-m-d H:i')]);
        if ($q->rowCount() !== 1) throw new DomainException('Jadwal penuh, ditutup, atau sudah berakhir. Pilih jadwal lain.');
        $q = $pdo->prepare('SELECT last_number,service_date FROM queue_slots WHERE id=?');
        $q->execute([$slotId]);
        $slot = $q->fetch();
        $code = 'AN-' . strtoupper(bin2hex(random_bytes(10)));
        $expiry = (new DateTimeImmutable($slot['service_date'] . ' 23:59:59', new DateTimeZone('Asia/Jakarta')))->modify('+30 days')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $pdo->prepare('INSERT INTO queue_bookings(slot_id,request_key,public_code,pin_hash,number,expires_at) VALUES(?,?,?,?,?,?)')
            ->execute([$slotId, $key, $code, password_hash($pin, PASSWORD_DEFAULT), $slot['last_number'], $expiry]);
        $pdo->prepare("INSERT INTO queue_events(booking_id,status) VALUES(?,'waiting')")->execute([(int) $pdo->lastInsertId()]);
        $pdo->commit();
        return $code;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function lookup_queue_booking(PDO $pdo, string $code, string $pin): array
{
    $code = strtoupper(trim($code));
    if (!preg_match('/^AN-[A-F0-9]{20}$/', $code) || !preg_match('/^[0-9]{6}$/', $pin)) throw new DomainException('Kode antrean atau PIN tidak sesuai.');
    $q = $pdo->prepare('SELECT b.*,t.service_date,t.start_time,t.end_time,s.name AS service_name,s.instructions,a.name AS agency_name,a.address
        FROM queue_bookings b JOIN queue_slots t ON t.id=b.slot_id JOIN queue_services s ON s.id=t.service_id JOIN queue_agencies a ON a.id=s.agency_id
        WHERE b.public_code=? AND b.expires_at>CURRENT_TIMESTAMP');
    $q->execute([$code]);
    $record = $q->fetch();
    $hash = $record ? $record['pin_hash'] : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
    if (!password_verify($pin, $hash) || !$record) throw new DomainException('Kode antrean atau PIN tidak sesuai, atau bukti telah kedaluwarsa.');
    unset($record['pin_hash'], $record['request_key']);
    $q = $pdo->prepare('SELECT status,created_at FROM queue_events WHERE booking_id=? ORDER BY id');
    $q->execute([$record['id']]);
    $record['events'] = $q->fetchAll();
    return $record;
}

function change_queue_booking(PDO $pdo, int $id, int $version, string $next, int $adminId): void
{
    if (!in_array($next, ['called','completed','cancelled','missed'], true)) throw new DomainException('Status antrean tidak valid.');
    $pdo->beginTransaction();
    try {
        $pdo->exec('UPDATE queue_bookings SET version=version WHERE 0');
        $q = $pdo->prepare('SELECT b.*,t.service_date,t.start_time FROM queue_bookings b JOIN queue_slots t ON t.id=b.slot_id WHERE b.id=? AND b.expires_at>CURRENT_TIMESTAMP');
        $q->execute([$id]);
        $booking = $q->fetch();
        if (!$booking || (int) $booking['version'] !== $version) throw new DomainException('Antrean berubah/kedaluwarsa. Muat ulang.');
        $allowed = $booking['status'] === 'waiting' ? ['called','cancelled'] : ($booking['status'] === 'called' ? ['completed','missed','cancelled'] : []);
        if (!in_array($next, $allowed, true)) throw new DomainException('Perubahan status antrean tidak diizinkan.');
        if ($next !== 'cancelled' && ($booking['service_date'] !== queue_now()->format('Y-m-d')
            || $booking['start_time'] > queue_now()->format('H:i'))) throw new DomainException('Panggilan/penyelesaian hanya pada tanggal layanan setelah jam mulai.');
        if ($next === 'called') {
            $q = $pdo->prepare("SELECT id FROM queue_bookings WHERE slot_id=? AND (status='called' OR (status='waiting' AND number<?)) LIMIT 1");
            $q->execute([$booking['slot_id'], $booking['number']]);
            if ($q->fetchColumn()) throw new DomainException('Selesaikan panggilan aktif dan panggil nomor menunggu paling awal.');
        }
        $pdo->prepare('UPDATE queue_bookings SET status=?,version=version+1,updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute([$next, $id]);
        if ($next === 'cancelled') $pdo->prepare('UPDATE queue_slots SET occupied=occupied-1,version=version+1 WHERE id=?')->execute([$booking['slot_id']]);
        $pdo->prepare('INSERT INTO queue_events(booking_id,status,changed_by) VALUES(?,?,?)')->execute([$id, $next, $adminId]);
        queue_audit($pdo, $adminId, 'queue_booking', $id);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}
