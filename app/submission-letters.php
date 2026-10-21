<?php
declare(strict_types=1);
require_once __DIR__ . '/submission-photos.php';

function store_submission_letter(array $file): ?array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if (($file['error'] ?? null) !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null)
        || !is_uploaded_file($file['tmp_name'])) throw new DomainException('Unggahan surat gagal. Pilih ulang PDF final.');
    $tmp = $file['tmp_name'];
    $size = filesize($tmp);
    if ($size === false || $size < 5 || $size > 5 * 1024 * 1024
        || (new finfo(FILEINFO_MIME_TYPE))->file($tmp) !== 'application/pdf'
        || file_get_contents($tmp, false, null, 0, 5) !== '%PDF-') {
        throw new DomainException('Surat harus PDF valid maksimal 5 MB.');
    }
    $directory = submission_upload_dir();
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new RuntimeException('Penyimpanan surat tidak dapat dibuat.');
    $name = bin2hex(random_bytes(24)) . '.pdf';
    $path = $directory . '/' . $name;
    if (!move_uploaded_file($tmp, $path)) throw new RuntimeException('Surat gagal disimpan.');
    $letter = ['stored_name' => $name, 'size_bytes' => $size];
    if (!chmod($path, 0600)) {
        delete_submission_photos([$letter]);
        throw new RuntimeException('Izin penyimpanan surat gagal diatur.');
    }
    return $letter;
}

function public_submission_letter(PDO $pdo, string $code): ?array
{
    $query = $pdo->prepare("SELECT submission_letters.*, submissions.public_code FROM submission_letters
        JOIN submissions ON submissions.id = submission_letters.submission_id
        WHERE submissions.public_code = ? AND submissions.status = 'completed' AND submissions.expires_at > CURRENT_TIMESTAMP");
    $query->execute([$code]);
    return $query->fetch() ?: null;
}

function stream_submission_letter(array $letter, bool $download = false): never
{
    $name = $letter['stored_name'];
    if (!preg_match('/^[a-f0-9]{48}\.pdf$/', $name) || !is_file(submission_upload_dir() . '/' . $name)) {
        error_log('Berkas surat final tidak ditemukan pada storage.');
        http_response_code(404);
        exit('Surat tidak tersedia. Hubungi petugas.');
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="surat-layanan.pdf"');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: sandbox; default-src 'none'");
    readfile(submission_upload_dir() . '/' . $name);
    exit;
}
