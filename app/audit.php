<?php
declare(strict_types=1);

require_once __DIR__ . '/reports.php';

function audit_action_labels(): array
{
    return [
        'login' => 'Login', 'logout' => 'Logout', 'password_reset' => 'Reset password',
        'service_settings' => 'Pengaturan layanan', 'submission_status' => 'Status pengajuan',
        'document_guide_saved' => 'Persyaratan & biaya', 'document_guide_status' => 'Status panduan', 'document_guide_deleted' => 'Panduan dihapus',
        'complaint_status' => 'Tindak lanjut pengaduan', 'queue_update' => 'Antrean DEMO',
        'category_saved' => 'Kategori disimpan', 'category_deleted' => 'Kategori dihapus',
        'service_saved' => 'Layanan disimpan', 'service_deleted' => 'Layanan dihapus', 'service_status' => 'Status layanan',
        'announcement_save' => 'Pengumuman disimpan', 'announcement_toggle' => 'Status pengumuman', 'announcement_delete' => 'Pengumuman dihapus',
        'office_master' => 'Profil/desa/sarana', 'office_device' => 'Penetapan KIOSK',
    ];
}

function audit_list(PDO $pdo, mixed $from, mixed $until, mixed $action, mixed $search, mixed $order, mixed $page): array
{
    $params = report_period($from, $until);
    if (!is_string($action) || strlen($action) > 100 || !is_string($search) || strlen($search) > 200
        || !is_string($order) || !in_array($order, ['desc', 'asc'], true)
        || !is_scalar($page) || !preg_match('/^[1-9][0-9]{0,8}$/', (string) $page)) {
        throw new DomainException('Filter audit tidak valid. Periksa pencarian, urutan, dan nomor halaman.');
    }
    $where = 'a.created_at >= ? AND a.created_at < ?';
    if ($action !== '') {
        $where .= ' AND a.action = ?';
        $params[] = $action;
    }
    if (trim($search) !== '') {
        $where .= " AND (instr(lower(COALESCE(u.name,'')),lower(?)) > 0
            OR instr(lower(COALESCE(u.username,'')),lower(?)) > 0
            OR instr(lower(a.summary),lower(?)) > 0 OR instr(lower(a.entity_type),lower(?)) > 0)";
        array_push($params, $search, $search, $search, $search);
    }
    $pdo->beginTransaction();
    try {
        $count = $pdo->prepare("SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id WHERE $where");
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / 20));
        $page = min((int) $page, $pages);
        $direction = $order === 'asc' ? 'ASC' : 'DESC';
        $query = $pdo->prepare("SELECT a.id,a.action,a.entity_type,a.entity_id,a.summary,a.created_at,
            u.name,u.username FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id
            WHERE $where ORDER BY a.created_at $direction,a.id $direction LIMIT 20 OFFSET " . (($page - 1) * 20));
        $query->execute($params);
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);
        $actions = $pdo->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
        $pdo->commit();
        return compact('rows', 'total', 'page', 'pages', 'actions');
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}
