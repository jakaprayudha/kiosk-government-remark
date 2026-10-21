<?php
declare(strict_types=1);

function report_period(mixed $start, mixed $end): array
{
    $zone = new DateTimeZone('Asia/Jakarta');
    $dates = [];
    foreach ([$start, $end] as $value) {
        if (!is_string($value) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value)) {
            throw new DomainException('Tanggal laporan harus berformat YYYY-MM-DD.');
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $zone);
        if (!$date || $date->format('Y-m-d') !== $value || $value < '1900-01-01' || $value > '9998-12-31') {
            throw new DomainException('Tanggal laporan tidak valid.');
        }
        $dates[] = $date;
    }
    if ($dates[0] > $dates[1]) throw new DomainException('Tanggal awal tidak boleh melewati tanggal akhir.');
    return [
        $dates[0]->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
        $dates[1]->modify('+1 day')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
    ];
}

function operational_report(PDO $pdo, mixed $start, mixed $end): array
{
    $period = report_period($start, $end);
    $result = [];
    $pdo->beginTransaction();
    try {
        foreach (['submissions', 'complaints', 'queue_bookings'] as $table) {
            $query = $pdo->prepare("SELECT status,COUNT(*) AS total FROM $table
                WHERE created_at>=? AND created_at<? AND expires_at>CURRENT_TIMESTAMP GROUP BY status");
            $query->execute($period);
            $result[$table] = array_map('intval', $query->fetchAll(PDO::FETCH_KEY_PAIR));
        }
        $query = $pdo->prepare("SELECT service_id,service_name,COUNT(*) AS total,
            SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) AS rejected,
            SUM(is_demo) AS demo
            FROM submissions WHERE created_at>=? AND created_at<? AND expires_at>CURRENT_TIMESTAMP
            GROUP BY service_id,service_name ORDER BY total DESC,service_name,service_id");
        $query->execute($period);
        $result['services'] = $query->fetchAll(PDO::FETCH_ASSOC);
        $pdo->commit();
        return $result;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}
