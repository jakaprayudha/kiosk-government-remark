<?php
declare(strict_types=1);
require_once __DIR__ . '/office.php';
require_once __DIR__ . '/service-catalog.php';

function office_actor(PDO $pdo, int $userId): array
{
    $q = $pdo->prepare("SELECT id,office_id FROM users WHERE id=? AND role='admin' AND is_active=1");
    $q->execute([$userId]);
    $actor = $q->fetch(PDO::FETCH_ASSOC);
    if (!$actor) throw new DomainException('Akses admin tidak tersedia.');
    return $actor;
}

function office_assert_scope(array $actor, int $officeId, bool $centralOnly = false): void
{
    if ($actor['office_id'] !== null && ($centralOnly || (int) $actor['office_id'] !== $officeId)) {
        throw new DomainException('Anda tidak memiliki akses ke kecamatan atau tindakan ini.');
    }
}

function office_master_definitions(): array
{
    $common = ['slug' => ['Kode unik', 'slug', 120], 'name' => ['Nama', 'text', 180]];
    $coordinates = ['latitude' => ['Latitude (-90 sampai 90)', 'latitude'], 'longitude' => ['Longitude (-180 sampai 180)', 'longitude']];
    $flags = ['is_demo' => ['Data DEMO / contoh', 'flag'], 'is_published' => ['Aktif / tampil di publik', 'flag']];
    return [
        'profiles' => ['table' => 'office_profiles', 'label' => 'Profil kecamatan', 'fields' => $common + [
            'regency' => ['Kabupaten / kota', 'required', 180], 'province' => ['Provinsi', 'required', 180],
            'description' => ['Tentang kecamatan', 'textarea', 10000], 'address' => ['Alamat kantor', 'textarea', 1500],
            'opening_hours' => ['Jam pelayanan', 'textarea', 1500], 'contact_note' => ['Kontak & bantuan', 'textarea', 1500],
            'source_url' => ['URL sumber resmi', 'url', 1000],
        ] + $coordinates + $flags],
        'villages' => ['table' => 'office_villages', 'label' => 'Desa', 'fields' => $common + [
            'head_name' => ['Nama kepala desa', 'text', 180], 'phone' => ['Telepon desa', 'text', 120],
            'population' => ['Jumlah penduduk', 'integer', 0, 100000000], 'population_year' => ['Tahun data penduduk', 'integer', 1900, 2200],
        ] + $coordinates + ['coordinate_note' => ['Catatan koordinat / sumber', 'textarea', 1500], 'sort_order' => ['Urutan', 'integer', 0, 9999]] + $flags],
        'facilities' => ['table' => 'office_facilities', 'label' => 'Sarana & prasarana', 'fields' => $common + [
            'category' => ['Jenis sarana', 'category'], 'description' => ['Penjelasan', 'textarea', 5000],
            'address' => ['Alamat', 'textarea', 1500], 'phone' => ['Telepon', 'text', 120],
            'opening_hours' => ['Jam pelayanan', 'textarea', 1500],
        ] + $coordinates + ['coordinate_note' => ['Catatan koordinat / sumber', 'textarea', 1500], 'sort_order' => ['Urutan', 'integer', 0, 9999]] + $flags],
        'accounts' => ['table' => 'users', 'label' => 'Admin kecamatan', 'fields' => [
            'name' => ['Nama admin', 'required', 180], 'username' => ['Username (3–50: huruf, angka, titik, garis bawah, hubung)', 'username', 50],
            'password' => ['Password (12–72 byte; kosong saat ubah berarti tidak diganti)', 'password', 72],
            'is_active' => ['Aktif / boleh login', 'flag'],
        ]],
        'devices' => ['table' => 'kiosk_devices', 'label' => 'Perangkat KIOSK', 'fields' => ['name' => ['Nama perangkat / lokasi', 'required', 180]]],
    ];
}

function office_master_defaults(string $tab): array
{
    $data = ['version' => 0];
    foreach (office_master_definitions()[$tab]['fields'] as $key => $field) {
        $data[$key] = match ($field[1]) {
            'flag', 'integer' => 0,
            'category' => 'health',
            default => '',
        };
    }
    if ($tab === 'villages') $data['population_year'] = (int) date('Y');
    if (isset($data['is_demo'])) $data['is_demo'] = 1;
    if ($tab === 'accounts') $data['is_active'] = 1;
    return $data;
}

function office_validate_master(string $tab, array $input, int $id): array
{
    $data = [];
    foreach (office_master_definitions()[$tab]['fields'] as $key => $field) {
        [$label, $type] = $field;
        $value = $input[$key] ?? ($type === 'flag' ? '0' : '');
        if (!is_string($value)) throw new DomainException('Isian tidak valid: ' . $label);
        if ($type !== 'password') $value = trim($value);
        if ($type === 'flag') {
            if (!in_array($value, ['0', '1'], true)) throw new DomainException('Status tidak valid.');
            $data[$key] = (int) $value;
        } elseif ($type === 'integer') {
            $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $field[2], 'max_range' => $field[3]]]);
            if ($number === false) throw new DomainException('Nilai tidak valid: ' . $label);
            $data[$key] = $number;
        } elseif ($type === 'latitude' || $type === 'longitude') {
            $number = filter_var($value, FILTER_VALIDATE_FLOAT);
            $limit = $type === 'latitude' ? 90 : 180;
            if ($number === false || !is_finite($number) || abs($number) > $limit) throw new DomainException('Koordinat tidak valid: ' . $label);
            $data[$key] = $number;
        } elseif ($type === 'category') {
            if (!isset(office_facility_categories()[$value])) throw new DomainException('Jenis sarana tidak valid.');
            $data[$key] = $value;
        } else {
            if (strlen($value) > $field[2]) throw new DomainException('Isian terlalu panjang: ' . $label);
            if (in_array($type, ['required', 'slug', 'username'], true) || $key === 'name') {
                if ($value === '') throw new DomainException('Isian wajib: ' . $label);
            }
            if ($type === 'slug' && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) throw new DomainException('Kode harus huruf kecil, angka dan tanda hubung antar kata.');
            if ($type === 'username' && !preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $value)) throw new DomainException('Username tidak valid.');
            if ($type === 'url' && $value !== '' && service_official_url($value) === null) throw new DomainException('URL resmi harus HTTP/HTTPS tanpa kredensial.');
            if ($type === 'password') {
                if ($value === '' && $id > 0) continue;
                if (str_contains($value, "\0")) throw new DomainException('Password tidak boleh mengandung karakter NUL.');
                if (strlen($value) < 12) throw new DomainException('Password minimal 12 dan maksimal 72 byte.');
                $data['password_hash'] = password_hash($value, PASSWORD_DEFAULT);
            } else $data[$key] = $value;
        }
    }
    return $data;
}

function mutate_office_master(PDO $pdo, int $userId, string $tab, int $officeId, int $id, int $version, string $action, array $input): int
{
    $definitions = office_master_definitions();
    if (!isset($definitions[$tab]) || !in_array($action, ['save', 'toggle', 'delete'], true)
        || $id < 0 || ($id > 0 && $version < 1) || ($action !== 'save' && $id === 0)) throw new DomainException('Aksi atau versi tidak valid.');
    $table = $definitions[$tab]['table'];
    $pdo->beginTransaction();
    try {
        $pdo->exec('UPDATE office_profiles SET version=version WHERE 0');
        $actor = office_actor($pdo, $userId);
        office_assert_scope($actor, $officeId, in_array($tab, ['accounts', 'devices'], true) || ($tab === 'profiles' && ($id === 0 || $action === 'delete')));
        if ($tab === 'profiles') {
            if ($id > 0 && $id !== $officeId) throw new DomainException('Kecamatan tidak sesuai.');
        } else {
            $q = $pdo->prepare('SELECT id FROM office_profiles WHERE id=?');
            $q->execute([$officeId]);
            if (!$q->fetchColumn()) throw new DomainException('Kecamatan tidak ditemukan.');
        }
        if ($id > 0) {
            $q = $pdo->prepare("SELECT * FROM $table WHERE id=?" . ($tab === 'profiles' ? '' : ' AND office_id=?'));
            $q->execute($tab === 'profiles' ? [$id] : [$id, $officeId]);
            $row = $q->fetch(PDO::FETCH_ASSOC);
            if (!$row || (int) $row['version'] !== $version || ($tab === 'accounts' && $row['role'] !== 'admin')) throw new DomainException('Data berubah, dihapus atau bukan milik kecamatan ini. Muat ulang.');
        }
        if ($action === 'delete' && $tab === 'profiles') {
            $q = $pdo->prepare('SELECT (SELECT COUNT(*) FROM office_villages WHERE office_id=?)+(SELECT COUNT(*) FROM office_facilities WHERE office_id=?)+(SELECT COUNT(*) FROM users WHERE office_id=?)+(SELECT COUNT(*) FROM kiosk_devices WHERE office_id=?)');
            $q->execute([$id, $id, $id, $id]);
            if ((int) $q->fetchColumn() > 0) throw new DomainException('Profil masih memiliki desa, sarana, admin atau perangkat. Nonaktifkan bila perlu.');
        }
        if ($action === 'save' && $tab === 'devices') throw new DomainException('Daftarkan perangkat melalui tombol Tetapkan browser ini.');
        if ($action === 'save') {
            $data = office_validate_master($tab, $input, $id);
            if (isset($data['slug']) || isset($data['username'])) {
                $key = $tab === 'accounts' ? 'username' : 'slug';
                $q = $pdo->prepare("SELECT id FROM $table WHERE $key=? AND id<>?" . (in_array($tab, ['villages', 'facilities'], true) ? ' AND office_id=?' : ''));
                $q->execute(in_array($tab, ['villages', 'facilities'], true) ? [$data[$key], $id, $officeId] : [$data[$key], $id]);
                if ($q->fetchColumn()) throw new DomainException('Kode/username sudah digunakan.');
            }
            if ($id === 0) {
                if ($tab !== 'profiles') $data['office_id'] = $officeId;
                if ($tab === 'accounts') $data['role'] = 'admin';
                $q = $pdo->prepare("INSERT INTO $table(" . implode(',', array_keys($data)) . ') VALUES(' . implode(',', array_fill(0, count($data), '?')) . ')');
                $q->execute(array_values($data));
                $id = (int) $pdo->lastInsertId();
            } else {
                $q = $pdo->prepare("UPDATE $table SET " . implode(',', array_map(static fn($key) => "$key=?", array_keys($data))) . ',version=version+1,updated_at=CURRENT_TIMESTAMP WHERE id=? AND version=?');
                $q->execute([...array_values($data), $id, $version]);
            }
        } elseif ($action === 'toggle') {
            $key = in_array($tab, ['accounts', 'devices'], true) ? 'is_active' : 'is_published';
            $value = $input[$key] ?? '0';
            if (!in_array($value, ['0', '1'], true)) throw new DomainException('Status tidak valid.');
            $q = $pdo->prepare("UPDATE $table SET $key=?,version=version+1 WHERE id=? AND version=?");
            $q->execute([(int) $value, $id, $version]);
        } else {
            $q = $pdo->prepare("DELETE FROM $table WHERE id=? AND version=?");
            $q->execute([$id, $version]);
        }
        $pdo->prepare("INSERT INTO audit_logs(user_id,action,entity_type,entity_id,summary) VALUES(?,'office_master',?,?,?)")->execute([$userId, $table, $id, 'Pengelolaan ' . $definitions[$tab]['label'] . ': ' . $action . '.']);
        $pdo->commit();
        return $id;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function register_kiosk_device(PDO $pdo, int $userId, int $officeId, string $name, mixed $previousToken = null): string
{
    $name = trim($name);
    if ($name === '' || strlen($name) > 180) throw new DomainException('Nama perangkat wajib diisi, maksimal 180 byte.');
    $token = bin2hex(random_bytes(32));
    $pdo->beginTransaction();
    try {
        $pdo->exec('UPDATE office_profiles SET version=version WHERE 0');
        office_assert_scope(office_actor($pdo, $userId), $officeId, true);
        $q = $pdo->prepare('SELECT id FROM office_profiles WHERE id=?');
        $q->execute([$officeId]);
        if (!$q->fetchColumn()) throw new DomainException('Kecamatan tidak ditemukan.');
        if (is_string($previousToken) && preg_match('/^[a-f0-9]{64}$/', $previousToken)) {
            $q = $pdo->prepare('SELECT id FROM kiosk_devices WHERE token_hash=? AND is_active=1');
            $q->execute([hash('sha256', $previousToken)]);
            $previousId = $q->fetchColumn();
            if ($previousId !== false) {
                $pdo->prepare('UPDATE kiosk_devices SET is_active=0,version=version+1 WHERE id=?')->execute([$previousId]);
                $pdo->prepare("INSERT INTO audit_logs(user_id,action,entity_type,entity_id,summary) VALUES(?,'office_device','kiosk_devices',?,'Penetapan lama browser dinonaktifkan saat penetapan ulang.')")->execute([$userId, $previousId]);
            }
        }
        $pdo->prepare('INSERT INTO kiosk_devices(office_id,name,token_hash) VALUES(?,?,?)')->execute([$officeId, $name, hash('sha256', $token)]);
        $pdo->prepare("INSERT INTO audit_logs(user_id,action,entity_type,entity_id,summary) VALUES(?,'office_device','kiosk_devices',?,'Browser KIOSK ditetapkan ke kecamatan.')")->execute([$userId, (int) $pdo->lastInsertId()]);
        $pdo->commit();
        return $token;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function kiosk_office_slug(PDO $pdo, mixed $token): ?string
{
    if ($token === null) return null;
    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) throw new DomainException('Penetapan perangkat KIOSK tidak valid. Hubungi admin pusat.');
    $q = $pdo->prepare('SELECT p.slug FROM kiosk_devices d JOIN office_profiles p ON p.id=d.office_id WHERE d.token_hash=? AND d.is_active=1');
    $q->execute([hash('sha256', $token)]);
    $slug = $q->fetchColumn();
    if ($slug === false) throw new DomainException('Perangkat KIOSK tidak terdaftar atau telah dinonaktifkan. Hubungi admin pusat.');
    return $slug;
}
