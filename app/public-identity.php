<?php
declare(strict_types=1);
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/office-management.php';

try {
    $publicIdentity = kiosk_settings(open_database());
    $publicOfficeName = '';
    $identityBindingError = '';
    try {
        $identitySlug = kiosk_office_slug(open_database(), $_COOKIE['kiosk_device'] ?? null);
        if ($identitySlug !== null) {
            $query = open_database()->prepare('SELECT name FROM office_profiles WHERE slug=?');
            $query->execute([$identitySlug]);
            $publicOfficeName = (string) $query->fetchColumn();
        }
    } catch (DomainException $error) {
        $identityBindingError = $error->getMessage();
    }
} catch (RuntimeException $error) {
    error_log('Identitas publik gagal dimuat: ' . $error->getMessage());
    http_response_code(503);
    exit('Identitas KIOSK gagal dimuat. Silakan hubungi petugas.');
}

function identity_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
