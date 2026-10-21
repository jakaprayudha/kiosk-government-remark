<?php
declare(strict_types=1);

function public_service_catalog(PDO $pdo, string $today, string $category = '', ?int $serviceId = null): array
{
    $sql = 'SELECT services.id, services.name, services.summary, services.description,
                   services.eligibility, services.fee_text, services.estimated_time,
                   services.office_unit, services.service_location, services.official_url,
                   services.valid_from, services.valid_until, services.updated_at,
                   services.accepts_submissions, services.requires_name, services.requires_contact,
                   services.camera_enabled, services.retention_days, services.is_demo,
                   services.requires_nik, services.requires_birth_date, services.requires_face,
                   service_categories.name AS category_name, service_categories.slug AS category_slug
            FROM services
            JOIN service_categories ON service_categories.id = services.category_id
            WHERE services.is_published = 1 AND service_categories.is_active = 1
              AND (services.valid_from IS NULL OR services.valid_from <= :today)
              AND (services.valid_until IS NULL OR services.valid_until >= :today)';
    $parameters = ['today' => $today];
    if ($category !== '') {
        $sql .= ' AND service_categories.slug = :category';
        $parameters['category'] = $category;
    }
    if ($serviceId !== null) {
        $sql .= ' AND services.id = :service_id';
        $parameters['service_id'] = $serviceId;
    }
    $sql .= ' ORDER BY service_categories.sort_order, service_categories.name, services.sort_order, services.name, services.id';
    $statement = $pdo->prepare($sql);
    $statement->execute($parameters);
    return $statement->fetchAll();
}

function public_service_requirements(PDO $pdo, int $serviceId): array
{
    $statement = $pdo->prepare(
        'SELECT id, name, description, is_required FROM service_requirements
         WHERE service_id = :service_id ORDER BY sort_order, id'
    );
    $statement->execute(['service_id' => $serviceId]);
    return $statement->fetchAll();
}

function service_official_url(string $url): ?string
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }
    $parts = parse_url($url);
    if (!is_array($parts) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
        || isset($parts['user']) || isset($parts['pass'])) {
        return null;
    }
    return $url;
}
