<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/public-session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !public_csrf_valid()) {
    http_response_code(403);
    exit;
}
http_response_code(204);
