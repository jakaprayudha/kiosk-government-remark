ALTER TABLE services ADD COLUMN requires_nik INTEGER NOT NULL DEFAULT 0 CHECK(requires_nik IN (0, 1));
ALTER TABLE services ADD COLUMN requires_birth_date INTEGER NOT NULL DEFAULT 0 CHECK(requires_birth_date IN (0, 1));
ALTER TABLE services ADD COLUMN requires_face INTEGER NOT NULL DEFAULT 0 CHECK(requires_face IN (0, 1));
ALTER TABLE submissions ADD COLUMN nik TEXT NOT NULL DEFAULT '';
ALTER TABLE submissions ADD COLUMN birth_date TEXT NOT NULL DEFAULT '';

CREATE TABLE service_pickup_sequences (
    service_id INTEGER PRIMARY KEY REFERENCES services(id) ON DELETE RESTRICT,
    last_number INTEGER NOT NULL CHECK(last_number > 0)
);
CREATE TABLE submission_letters (
    submission_id INTEGER PRIMARY KEY REFERENCES submissions(id) ON DELETE CASCADE,
    stored_name TEXT NOT NULL UNIQUE,
    size_bytes INTEGER NOT NULL,
    pickup_number TEXT NOT NULL UNIQUE,
    approved_by INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

UPDATE services SET requires_name = 1, requires_birth_date = 1, requires_face = 1
WHERE is_demo = 1 AND slug IN ('demo-legalisasi-dokumen', 'demo-rekomendasi-kegiatan',
 'demo-konsultasi-administrasi', 'demo-konsultasi-kanal-instansi', 'demo-permintaan-informasi', 'demo-informasi-fasilitas');
UPDATE services SET requires_nik = 1 WHERE is_demo = 1 AND slug = 'demo-legalisasi-dokumen';
