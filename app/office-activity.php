<?php
declare(strict_types=1);

function record_kiosk_activity(PDO $pdo, mixed $token): void
{
    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) return;
    $query = $pdo->prepare('UPDATE kiosk_devices SET last_seen_at=CURRENT_TIMESTAMP,page_views=page_views+1 WHERE token_hash=? AND is_active=1');
    $query->execute([hash('sha256', $token)]);
}

function office_activity_recap(PDO $pdo): array
{
    return $pdo->query("SELECT p.id,p.name,
        COALESCE(u.accounts,0) AS accounts,COALESCE(u.active_accounts,0) AS active_accounts,u.last_login_at,
        COALESCE(l.logins_today,0) AS logins_today,
        COALESCE(d.devices,0) AS devices,COALESCE(d.active_devices,0) AS active_devices,
        COALESCE(d.page_views,0) AS page_views,d.last_seen_at
        FROM office_profiles p
        LEFT JOIN (
            SELECT office_id,COUNT(*) AS accounts,SUM(is_active) AS active_accounts,MAX(last_login_at) AS last_login_at
            FROM users WHERE role='admin' AND office_id IS NOT NULL GROUP BY office_id
        ) u ON u.office_id=p.id
        LEFT JOIN (
            SELECT u.office_id,COUNT(*) AS logins_today FROM audit_logs a JOIN users u ON u.id=a.user_id
            WHERE a.action='login' AND u.role='admin' AND u.office_id IS NOT NULL
            AND date(a.created_at,'+7 hours')=date('now','+7 hours') GROUP BY u.office_id
        ) l ON l.office_id=p.id
        LEFT JOIN (
            SELECT office_id,COUNT(*) AS devices,SUM(is_active) AS active_devices,
                SUM(page_views) AS page_views,MAX(last_seen_at) AS last_seen_at
            FROM kiosk_devices GROUP BY office_id
        ) d ON d.office_id=p.id
        ORDER BY p.name,p.id")->fetchAll(PDO::FETCH_ASSOC);
}

function office_activity_time(?string $value): string
{
    if ($value === null) return 'Belum tercatat';
    return (new DateTimeImmutable($value, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('d/m/Y H:i:s') . ' WIB';
}
