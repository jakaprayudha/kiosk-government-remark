<?php
declare(strict_types=1);

function public_office(PDO $pdo, string $slug): ?array
{
    $query = $pdo->prepare('SELECT * FROM office_profiles WHERE slug = ? AND is_published = 1');
    $query->execute([$slug]);
    $office = $query->fetch();
    if (!$office) return null;
    $query = $pdo->prepare('SELECT * FROM office_villages WHERE office_id = ? AND is_published = 1 ORDER BY sort_order, name, id');
    $query->execute([$office['id']]);
    $office['villages'] = $query->fetchAll();
    $query = $pdo->prepare('SELECT * FROM office_facilities WHERE office_id = ? AND is_published = 1 ORDER BY sort_order, name, id');
    $query->execute([$office['id']]);
    $office['facilities'] = $query->fetchAll();
    return $office;
}

function office_facility_categories(): array
{
    return [
        'health' => ['label' => 'Puskesmas / kesehatan', 'code' => 'KES'],
        'sd' => ['label' => 'Sekolah dasar', 'code' => 'SD'],
        'smp' => ['label' => 'SMP', 'code' => 'SMP'],
        'sma' => ['label' => 'SMA / sederajat', 'code' => 'SMA'],
        'government' => ['label' => 'Kantor pelayanan', 'code' => 'KTR'],
        'market' => ['label' => 'Pasar', 'code' => 'PSR'],
        'sport' => ['label' => 'Olahraga', 'code' => 'OR'],
    ];
}

function office_map_data(array $office): array
{
    return [
        'center' => [(float) $office['latitude'], (float) $office['longitude']],
        'villages' => array_map(static fn(array $village): array => [
            'id' => (int) $village['id'], 'name' => $village['name'],
            'latitude' => (float) $village['latitude'], 'longitude' => (float) $village['longitude'],
            'isDemo' => (int) $village['is_demo'] === 1,
        ], $office['villages']),
        'facilities' => array_map(static fn(array $facility): array => [
            'id' => (int) $facility['id'], 'name' => $facility['name'],
            'latitude' => (float) $facility['latitude'], 'longitude' => (float) $facility['longitude'],
            'category' => $facility['category'],
            'code' => office_facility_categories()[$facility['category']]['code'],
            'isDemo' => (int) $facility['is_demo'] === 1,
        ], $office['facilities']),
    ];
}
