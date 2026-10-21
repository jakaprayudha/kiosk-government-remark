<?php
declare(strict_types=1);

function submission_upload_dir(): string
{
    return dirname(__DIR__) . '/storage/uploads';
}

function delete_submission_photos(array $photos): void
{
    foreach ($photos as $photo) {
        $path = submission_upload_dir() . '/' . $photo['stored_name'];
        if (is_file($path) && !unlink($path)) {
            error_log('Pembersihan lampiran gagal; pengelola perlu memeriksa storage/uploads.');
            throw new RuntimeException('Berkas sementara gagal dibersihkan. Hubungi pengelola.');
        }
    }
}

function store_submission_photos(array $files, array $requirements, bool $enabled, bool $requiresFace = false): array
{
    if ($files === []) {
        if ($requiresFace) throw new DomainException('Foto wajah wajib diambil melalui kamera.');
        return [];
    }
    if (!$enabled && !$requiresFace) throw new DomainException('Foto tidak diaktifkan untuk layanan ini.');
    if (!isset($files['error']) || !is_array($files['error']) || count($files['error']) > ($requiresFace ? 4 : 3)) {
        throw new DomainException('Maksimal tiga foto dokumen dan satu foto wajah jika diminta.');
    }
    if (!extension_loaded('gd')) throw new RuntimeException('Ekstensi GD diperlukan untuk memproses foto.');
    $allowed = $enabled ? array_column($requirements, 'name', 'id') : [];
    if ($requiresFace) $allowed['face'] = 'Foto wajah pemohon';
    $photos = [];
    try {
        foreach ($files['error'] as $requirementId => $uploadError) {
            if ($uploadError === UPLOAD_ERR_NO_FILE) continue;
            if (!isset($allowed[$requirementId]) || $uploadError !== UPLOAD_ERR_OK) {
                throw new DomainException('Unggahan gagal atau jenis dokumen tidak sesuai layanan.');
            }
            $tmp = $files['tmp_name'][$requirementId] ?? '';
            if (!is_string($tmp) || !is_uploaded_file($tmp) || filesize($tmp) > 2 * 1024 * 1024) {
                throw new DomainException('Setiap foto maksimal 2 MB dan harus berasal dari unggahan formulir.');
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
            if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
                throw new DomainException('Gunakan foto JPEG/PNG, bukan jenis berkas lain.');
            }
            set_error_handler(static function (int $severity, string $message): never {
                throw new DomainException('Foto rusak atau tidak dapat dibaca. Ambil ulang foto.');
            }, E_WARNING | E_NOTICE);
            try {
                $size = getimagesize($tmp);
                if ($size === false || $size[0] < 1 || $size[1] < 1 || $size[0] > 2500
                    || $size[1] > 2500 || $size[0] * $size[1] > 4000000) {
                    throw new DomainException('Gunakan foto JPEG/PNG maksimal 2500 piksel per sisi dan 4 megapiksel.');
                }
                $image = $mime === 'image/jpeg' ? imagecreatefromjpeg($tmp) : imagecreatefrompng($tmp);
            } finally {
                restore_error_handler();
            }
            if ($image === false) throw new DomainException('Foto tidak dapat dibaca. Ambil ulang foto.');
            $directory = submission_upload_dir();
            if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                throw new RuntimeException('Penyimpanan dokumen tidak dapat dibuat.');
            }
            $filename = bin2hex(random_bytes(24)) . '.jpg';
            $path = $directory . '/' . $filename;
            $photo = ['requirement_id' => $requirementId === 'face' ? null : (int) $requirementId, 'document_name' => $allowed[$requirementId], 'stored_name' => $filename];
            $photos[] = $photo;
            // Re-encode to discard embedded metadata and non-image payloads.
            if (!imagejpeg($image, $path, 85) || !chmod($path, 0600)) {
                throw new RuntimeException('Foto tidak dapat disimpan dengan aman.');
            }
            $photos[array_key_last($photos)]['size_bytes'] = filesize($path);
        }
        if ($requiresFace && !in_array('Foto wajah pemohon', array_column($photos, 'document_name'), true)) throw new DomainException('Foto wajah wajib diambil dan digunakan.');
        return $photos;
    } catch (Throwable $error) {
        delete_submission_photos($photos);
        throw $error;
    }
}
